<?php

/**
 * Prepares a Backup (Backup) to be executed using Background Processing.
 *
 * @package WPStaging\Backup\BackgroundProcessing\Backup
 */

namespace WPStaging\Backup\BackgroundProcessing\Backup;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\Action;
use WPStaging\Framework\BackgroundProcessing\Exceptions\QueueException;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\BackgroundProcessing\QueueActionAware;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Backup\Ajax\Backup\PrepareBackup as AjaxPrepareBackup;
use Exception;
use WP_Error;
use WPStaging\Backup\BackupProcessLock;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Exceptions\ProcessLockedException;
use WPStaging\Backup\Job\JobBackupProvider;
use WPStaging\Backup\Job\Jobs\JobBackup;

use function WPStaging\functions\debug_log;

/**
 * Class PrepareBackup
 *
 * @package WPStaging\Backup\BackgroundProcessing\Backup
 */
class PrepareBackup
{
    use ResourceTrait;
    use QueueActionAware;

    /**
     * A reference to the class that handles Backup processing when triggered by AJAX actions.
     *
     * @var AjaxPrepareBackup
     */
    private $ajaxPrepareBackup;

    /** @var JobBackup */
    private $jobBackup;

    /**
     * A reference to the instance of the Queue manager the class should use for processing.
     *
     * @var Queue
     */
    private $queue;

    private $processLock;

    /**
     * The ID of the Action last inserted in the Queue by this class.
     *
     * Note: the Action ID is a transient, per-instance, value that will NOT be carried over from instance
     * to instance of this class during requests.
     *
     * @var int|null
     */
    private $lastQueuedActionId;

    /**
     * PrepareBackup constructor.
     *
     * @param AjaxPrepareBackup $ajaxPrepareBackup A reference to the object currently handling
     *                                             AJAX Backup preparation requests.
     */
    public function __construct(AjaxPrepareBackup $ajaxPrepareBackup, Queue $queue, BackupProcessLock $processLock)
    {
        $this->ajaxPrepareBackup = $ajaxPrepareBackup;
        $this->queue = $queue;
        $this->processLock = $processLock;
    }

    /**
     * @param array<string,mixed>|null $data Either a map of the data to prepare the Backup with, or
     *                                       `null` to use the default Backup settings.
     *
     * @return string|WP_Error Either the Background Processing Job identifier for this backup task, or
     *                         an error instance detailing the cause of the failure.
     */
    public function prepare($data = null)
    {
        $data = empty($data) ? [] : (array)$data;

        try {
            $data = (array)wp_parse_args((array)$data, $this->getDefaultDataConfiguration());
            $prepared = $this->ajaxPrepareBackup->validateAndSanitizeData($data);
            $name = isset($prepared['name']) ? $prepared['name'] : 'Background Processing Backup';
            $jobId = uniqid($name . '_', true);

            $data['jobId'] = $jobId;
            $data['name'] = $name;

            $this->queueAction($data);

            return $jobId;
        } catch (Exception $e) {
            return new WP_Error(400, $e->getMessage());
        }
    }

    /**
     * Queues the Background Processing Action required to move the Backup job forward.
     *
     * @param array $jobId The identifier of all the Actions part of this Backup processing.
     *
     * @throws QueueException If there is an issue enqueueing the background processing action required by the
     *                        job prepare.
     */
    private function queueAction($args)
    {
        if (!isset($args['jobId'])) {
            throw new \BadMethodCallException();
        }

        $action = $this->getCurrentAction();
        $priority = $action === null ? 0 : $action->priority - 1;
        $actionId = $this->queue->enqueueAction(self::class . '::' . 'act', $args, $args['jobId'], $priority);

        if ($actionId === false || !$this->queue->getAction($actionId) instanceof Action) {
            throw new QueueException('Backup background processing action could not be queued.');
        }

        $this->lastQueuedActionId = $actionId;
    }

    /**
     * This method is the one the Queue will invoke to move the Backup processing forward.
     *
     * This method will either end the Backup background processing (on completion or failure), or
     * enqueue a new Action in the background processing system to keep running the Backup.
     *
     * @param string $jobId The identifier of all the Actions part of this Backup processing.
     *
     * @return WP_Error|TaskResponseDto Either a reference to the updated Backup task status, or a reference
     *                                  to the Error instance detailing the reasons of the failure.
     * @throws QueueException
     */
    public function act($args)
    {
        try {
            $this->processLock->checkProcessLocked();
        } catch (ProcessLockedException $e) {
            $this->queueAction($args);

            return new WP_Error(400, $e->getMessage());
        }

        if ($args['isInit']) {
            debug_log('[Schedule] Configuring JOB DATA DTO');
            $prepareBackup = WPStaging::make(\WPStaging\Backup\Ajax\Backup\PrepareBackup::class);
            $prepareBackup->prepare($args);
            $this->jobBackup = $prepareBackup->getJobBackup();
        } else {
            $this->jobBackup = WPStaging::make(JobBackupProvider::class)->getJob();
        }

        $args['isInit'] = false;

        $taskResponseDto = null;

        debug_log('[Schedule Job Data DTO]: ' . json_encode($this->jobBackup->getJobDataDto()));

        do {
            try {
                /** @see WPStaging\Backup\Job\AbstractJob::prepareAndExecute() */
                $taskResponseDto = $this->jobBackup->prepareAndExecute();
                $this->jobBackup->persist();
                $this->persistDtoToAction($this->getCurrentAction(), $taskResponseDto);
            } catch (Exception $e) {
                error_log('Action for ' . $args['jobId'] . ' failed: ' . $e->getMessage());
                debug_log('Action for ' . $args['jobId'] . ' failed: ' . $e->getMessage());
                $this->persistDtoToAction($this->getCurrentAction(), $taskResponseDto);
                $this->processLock->unlockProcess();

                return new WP_Error(400, $e->getMessage());
            }

            $errorMessage = $this->getLastErrorMessage();
            if ($errorMessage !== false) {
                $this->processLock->unlockProcess();
                /** @var BackupScheduler */
                $backupScheduler = WPStaging::make(BackupScheduler::class);
                $backupScheduler->sendErrorReport("[Errors in scheduled backups]: " . $errorMessage);

                return new WP_Error(400, $errorMessage);
            }

            if (!$taskResponseDto->isRunning()) {
                // Cleanup the pending/ready actions for this scheduleId.
                $this->queue->cleanupActionsByScheduleId($args['scheduleId'], [Queue::STATUS_READY]);

                // We're finished, get out and bail.
                return $taskResponseDto;
            }
        } while (!$this->isThreshold());

        // We're not done, queue a new Action to keep processing this job.
        $this->queueAction($args);

        return $taskResponseDto;
    }

    /**
     * Returns the ID of the last Background Processing Action queued by this class, if any.
     *
     * @return int|null The ID of the last Background Processing Action queued by this class, if any.
     */
    public function getLastQueuedActionId()
    {
        return $this->lastQueuedActionId;
    }

    /**
     * Commits the current Backup Job status to the database.
     *
     * This method is a proxy to the Ajax Backup Prepare handler own `commit` method.
     *
     * @return bool Whether the commit was successful, in terms of intended state, or not.
     */
    public function persist()
    {
        return $this->ajaxPrepareBackup->persist();
    }

    /**
     * Returns the Job ID of the last Queue action queued by the Job.
     *
     * @return string|null Either the Job ID of the last Action queued by the Job, or `null` if the
     *                     Job did not queue any Action yet.
     */
    public function getQueuedJobId()
    {
        if (empty($this->lastQueuedActionId)) {
            return null;
        }

        try {
            return $this->queue->getAction($this->lastQueuedActionId)->jobId;
        } catch (QueueException $e) {
            return null;
        }
    }

    /**
     * Returns the default data configuration that will be used to prepare a Backup using
     * default settings.
     *
     * @return array<string,bool> The Backup preparation default settings.
     */
    public function getDefaultDataConfiguration()
    {
        return [
            'isExportingPlugins' => true,
            'isExportingMuPlugins' => true,
            'isExportingThemes' => true,
            'isExportingUploads' => true,
            'isExportingOtherWpContentFiles' => true,
            'isExportingDatabase' => true,
            'isAutomatedBackup' => true,
            // Prevent this scheduled backup from generating another schedule.
            'repeatBackupOnSchedule' => false,
            'sitesToBackup' => [],
            'storages' => ['localStorage'],
            'isInit' => true,
            'isSmartExclusion' => false,
            'isExcludingSpamComments' => false,
            'isExcludingPostRevision' => false,
            'isExcludingDeactivatedPlugins' => false,
            'isExcludingUnusedThemes' => false,
            'isExcludingLogs' => false,
            'isExcludingCaches' => false,
            'backupType' => is_multisite() ? BackupMetadata::BACKUP_TYPE_MULTISITE : BackupMetadata::BACKUP_TYPE_SINGLE,
            'subsiteBlogId' => null,
        ];
    }

    /**
     * Persists the response DTO to the Action custom field, if possible.
     *
     * @param Action|null          $action A reference to the Action object currently being processed, or `null` if
     *                                     the current Action being processed is not available.
     * @param TaskResponseDto|null $dto    A reference to the current task DTO, or `null` if not available.
     *
     * @return void The method does not return any value and will have the side effect of
     *              persisting the task DTO to the Action custom field.
     */
    private function persistDtoToAction(Action $action = null, TaskResponseDto $dto = null)
    {
        try {
            if ($action === null || $dto === null) {
                return;
            }

            $logFile = $this->jobBackup->getCurrentTask()->getLogger()->getFileName();
            $this->queue->updateActionFields($action->id, ['custom' => $logFile, 'response' => serialize($dto)], true);

            $errorMessage = $this->getLastErrorMessage();
            if ($errorMessage !== false) {
                debug_log($errorMessage);
            }
        } catch (Exception $e) {
            // We could be doing this in the context of Exception handling, let's not throw one more.
        }
    }

    /**
     * @return string|false Return error message. If there is no error message, return false
     */
    private function getLastErrorMessage()
    {
        $error = $this->jobBackup->getCurrentTask()->getLogger()->getLastErrorMsg();

        if ($error === false) {
            return false;
        }

        if (is_array($error) && key_exists('message', $error)) {
            $error = $error['message'];
        }

        if (!is_string($error)) {
            $error = json_encode($error);
        }

        debug_log('[Schedule Last Error Message]: ' . $error);
        return $error;
    }
}
