<?php

namespace WPStaging\Staging\BackgroundProcessing;

use Exception;
use WP_Error;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\Action;
use WPStaging\Framework\BackgroundProcessing\Exceptions\QueueException;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\BackgroundProcessing\QueueActionAware;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Utils\Times;
use WPStaging\Staging\Ajax\Delete\PrepareDelete as AjaxPrepareDelete;
use WPStaging\Staging\Jobs\StagingSiteDelete;

use function WPStaging\functions\debug_log;

/**
 * Class PrepareDelete
 * Prepares a Staging Site Delete Job to be executed using Background Processing.
 *
 * @package WPStaging\Staging\BackgroundProcessing
 */
class PrepareDelete
{
    use ResourceTrait;
    use QueueActionAware;

    /**
     * A reference to the class that handles staging site "Delete" processing when triggered by AJAX actions.
     *
     * @var AjaxPrepareDelete
     */
    private $ajaxPrepareDelete;

    /** @var StagingSiteDelete */
    private $jobDelete;

    /**
     * A reference to the instance of the Queue manager the class should use for processing.
     *
     * @var Queue
     */
    private $queue;

    /** @var ProcessLock */
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

    /** @var Times */
    private $times;

    /**
     * PrepareDelete constructor.
     *
     * @param AjaxPrepareDelete $ajaxPrepareDelete A reference to the object currently handling
     *                                             AJAX Delete preparation requests.
     * @param Queue             $queue             A reference to the instance of the Queue manager the class
     *                                             should use for processing.
     * @param ProcessLock       $processLock       A reference to the Process Lock manager the class should use
     *                                             to prevent concurrent processing of the job requests.
     * @param Times             $times             A reference to the Times utility class.
     */
    public function __construct(AjaxPrepareDelete $ajaxPrepareDelete, Queue $queue, ProcessLock $processLock, Times $times)
    {
        $this->ajaxPrepareDelete = $ajaxPrepareDelete;
        $this->queue             = $queue;
        $this->processLock       = $processLock;
        $this->times             = $times;
    }

    /**
     * @param array<string,mixed>|null $data Either a map of the data to prepare the Delete with, or
     *                                       `null` to use the default Delete settings.
     *
     * @return string|WP_Error Either the Background Processing Job identifier for this Delete task, or
     *                         an error instance detailing the cause of the failure.
     */
    public function prepare($data = null)
    {
        $data = empty($data) ? [] : (array)$data;

        try {
            $data     = (array)wp_parse_args((array)$data, $this->getDefaultDataConfiguration());
            $prepared = $this->ajaxPrepareDelete->validateAndSanitizeData($data);
            $name     = empty($prepared['name']) ? 'Background Processing Delete' : $prepared['name'];
            $jobId    = uniqid($name . '_', true);

            $data['jobId'] = $jobId;
            $data['name']  = $name;

            $this->queueAction($data);

            // Let convert stalled actions to canceled
            $this->queue->markDanglingAs(Queue::STATUS_CANCELED, $this->queue->getStalledBreakpointDate(), Queue::SET_UPDATED_AT_TO_NOW);

            return $jobId;
        } catch (Exception $e) {
            return new WP_Error(400, $e->getMessage());
        }
    }

    /**
     * Queues the Background Processing Action required to move the Delete job forward.
     *
     * @param array $jobId The identifier of all the Actions part of this Delete processing.
     *
     * @throws QueueException If there is an issue enqueueing the background processing action required by the
     *                        job prepare.
     */
    private function queueAction($args)
    {
        if (!isset($args['jobId'])) {
            throw new \BadMethodCallException();
        }

        $action   = $this->getCurrentAction();
        $priority = $action === null ? 0 : $action->priority - 1;
        $actionId = $this->queue->enqueueAction(self::class . '::' . 'act', $args, $args['jobId'], $priority);

        if ($actionId === false || !$this->queue->getAction($actionId) instanceof Action) {
            throw new QueueException('Delete background processing action could not be queued.');
        }

        $this->lastQueuedActionId = $actionId;
    }

    /**
     * This method is the one the Queue will invoke to move the Delete processing forward.
     *
     * This method will either end the Delete background processing (on completion or failure), or
     * enqueue a new Action in the background processing system to keep running the Delete.
     *
     * @param string $jobId The identifier of all the Actions part of this Delete processing.
     *
     * @return WP_Error|TaskResponseDto Either a reference to the updated Delete task status, or a reference
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
            debug_log('[Schedule] Configuring JOB DATA DTO', 'info', false);
            $prepareDelete = WPStaging::make(AjaxPrepareDelete::class);
            $prepareDelete->prepare($args);
            $this->jobDelete = $prepareDelete->getStagingSiteDelete();
        } else {
            $this->jobDelete = WPStaging::make(StagingSiteDelete::class);
        }

        $args['isInit'] = false;

        $taskResponseDto = null;

        debug_log('[Schedule Job Data DTO]: ' . json_encode($this->jobDelete->getJobDataDto()), 'info', false);

        do {
            try {
                /** @see WPStaging\Framework\Job\AbstractJob::prepareAndExecute() */
                $taskResponseDto = $this->jobDelete->prepareAndExecute();
                $this->jobDelete->persist();
                $this->persistDtoToAction($this->getCurrentAction(), $taskResponseDto);
            } catch (Exception $e) {
                error_log('Action for ' . $args['jobId'] . ' failed: ' . $e->getMessage());
                debug_log('Action for ' . $args['jobId'] . ' failed: ' . $e->getMessage());
                $this->persistDtoToAction($this->getCurrentAction(), $taskResponseDto);
                $this->processLock->unlockProcess();

                return new WP_Error(400, $e->getMessage());
            }


            $errorMessage    = $this->getLastErrorMessage();
            if ($errorMessage !== false) {
                $this->processLock->unlockProcess();
                $body = '';
                if (array_key_exists('scheduleId', $args)) {
                    $body .= 'Error in scheduled Delete' . PHP_EOL . PHP_EOL;
                } else {
                    $body .= 'Error in background Delete' . PHP_EOL . PHP_EOL;
                }

                $jobDataDto = $this->jobDelete->getJobDataDto();
                $date = new \DateTime();
                $date->setTimestamp($jobDataDto->getStartTime());
                $jobDuration = str_replace(['minutes', 'seconds'], ['min', 'sec'], $this->times->getHumanReadableDuration(gmdate('i:s', $jobDataDto->getDuration())));

                $body .= 'Started at: ' .  $date->format('H:i:s') . PHP_EOL ;
                $body .= 'Duration: ' . $jobDuration . PHP_EOL;
                $body .= 'Job ID: ' . $args['jobId'] . PHP_EOL . PHP_EOL;
                $body .= 'Error Message: ' . $errorMessage;

                return new WP_Error(400, $errorMessage);
            }

            if (!$taskResponseDto->isRunning()) {
                // Cleanup the pending/ready actions for this scheduleId.
                if (array_key_exists('scheduleId', $args)) {
                    $this->queue->cleanupActionsByScheduleId($args['scheduleId'], [Queue::STATUS_READY]);
                }

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
     * Commits the current Delete Job status to the database.
     *
     * This method is a proxy to the Ajax Delete Prepare handler own `commit` method.
     *
     * @return bool Whether the commit was successful, in terms of intended state, or not.
     */
    public function persist()
    {
        return $this->ajaxPrepareDelete->persist();
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
     * Returns the default data configuration that will be used to prepare a Delete using
     * default settings.
     *
     * @return array<string,bool> The Delete preparation default settings.
     */
    public function getDefaultDataConfiguration()
    {
        return [
            'isDeletingTables' => false,
            'isDeletingFiles'  => false,
            'excludedTables'   => [],
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

            $logFile = $this->jobDelete->getCurrentTask()->getLogger()->getFileName();
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
        $error = $this->jobDelete->getCurrentTask()->getLogger()->getLastErrorMsg();

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
