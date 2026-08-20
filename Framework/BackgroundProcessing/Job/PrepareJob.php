<?php







namespace WPStaging\Framework\BackgroundProcessing\Job;

use Exception;
use WP_Error;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\Action;
use WPStaging\Framework\BackgroundProcessing\Exceptions\QueueException;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\BackgroundProcessing\QueueActionAware;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Job\Ajax\PrepareJob as AjaxPrepareJob;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Utils\Times;

use function WPStaging\functions\debug_log;







abstract class PrepareJob
{
    use ResourceTrait;
    use QueueActionAware;






    const ACTION_JOB_FAILURE = 'wpstg_background_job_failure';

 
    protected $job;




    private $ajaxPrepareJob;






    private $queue;

 
    private $processLock;









    private $lastQueuedActionId;

 
    private $times;




    private $handlingError = false;










    public function __construct(AjaxPrepareJob $ajaxPrepareJob, Queue $queue, ProcessLock $processLock, Times $times)
    {
        $this->ajaxPrepareJob = $ajaxPrepareJob;
        $this->queue          = $queue;
        $this->processLock    = $processLock;
        $this->times          = $times;
    }








    public function prepare($data = null)
    {
        $data = empty($data) ? [] : (array)$data;

        try {
            $data     = (array)wp_parse_args((array)$data, $this->getDefaultDataConfiguration());
            $prepared = $this->ajaxPrepareJob->validateAndSanitizeData($data);
            $name     = empty($prepared['name']) ? $this->getJobDefaultName() : $prepared['name'];
            $jobId    = empty($data['id']) ? uniqid($name . '_', true) : $data['id'];

            $data['jobId'] = $jobId;
            $data['name']  = $name;

            $this->queueAction($data);

 
            $this->queue->markDanglingAs(Queue::STATUS_CANCELED, $this->queue->getStalledBreakpointDate(), Queue::SET_UPDATED_AT_TO_NOW);

            return $jobId;
        } catch (Exception $e) {
            return new WP_Error(400, $e->getMessage());
        }
    }









    private function queueAction($args)
    {
        if (!isset($args['jobId'])) {
            throw new \BadMethodCallException();
        }

        $action   = $this->getCurrentAction();
        $priority = $action === null ? 0 : $action->priority - 1;
        $actionId = $this->queue->enqueueAction(static::class . '::' . 'act', $args, $args['jobId'], $priority);

        if ($actionId === false || !$this->queue->getAction($actionId) instanceof Action) {
            throw new QueueException('Background processing action could not be queued.');
        }

        $this->lastQueuedActionId = $actionId;
    }













    public function act($args)
    {
        $jobIdForLog = isset($args['jobId']) ? (string)$args['jobId'] : 'unknown';
        debug_log('[BG Queue] act() start: jobId=' . $jobIdForLog . ' class=' . static::class, 'info', false);

        try {
 
 
 
            $this->processLock->lockProcess();
        } catch (ProcessLockedException $e) {
            $this->queueAction($args);

            debug_log('[BG Queue] act() end: jobId=' . $jobIdForLog . ' outcome=process-locked (re-queued)', 'info', false);
            return new WP_Error(400, $e->getMessage());
        }

        $this->maybeInitJob($args);

        $args['isInit']  = false;
        $taskResponseDto = null;

 
 
 
 
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            debug_log('[Schedule Job Data DTO]: ' . json_encode($this->job->getJobDataDto()), 'debug', false);
        }

        do {
            try {
 
                $taskResponseDto = $this->job->prepareAndExecute();
                $this->job->persist();
                $this->persistDtoToAction($this->getCurrentAction(), $taskResponseDto);
            } catch (ProcessLockedException $e) {
 
 
 
                $this->queueAction($args);

                debug_log('[BG Queue] act() end: jobId=' . $jobIdForLog . ' outcome=process-locked-midrun (re-queued)', 'info', false);
                return new WP_Error(400, $e->getMessage());
            } catch (Exception $e) {
                debug_log('Action for ' . $args['jobId'] . ' failed: ' . $e->getMessage());
                $this->handlingError = true;
                $this->persistDtoToAction($this->getCurrentAction(), $taskResponseDto);

                $this->handleError($e->getMessage(), $args);

                return new WP_Error(400, $e->getMessage());
            }

            if ($this->isJobCancelled($args)) {
                return new WP_Error(499, 'Job cancelled by user.'); 
            }

            $errorMessage = $this->getLastErrorMessage();
            if ($errorMessage !== false) {
                $this->handleError($errorMessage, $args);

                debug_log('[BG Queue] act() end: jobId=' . $jobIdForLog . ' outcome=error', 'info', false);
                return new WP_Error(400, $errorMessage);
            }

            if (!$taskResponseDto->isRunning()) {
 
                if (array_key_exists('scheduleId', $args)) {
                    $this->queue->cleanupActionsByScheduleId($args['scheduleId'], [Queue::STATUS_READY]);
                }

 
                return $taskResponseDto;
            }

            $this->job->commitLogs();
        } while (!$this->isThreshold());

 
        if ($this->isJobCancelled($args)) {
            return new WP_Error(499, 'Job cancelled by user.'); 
        }

 
        $this->queueAction($args);
        $this->processLock->unlockProcess();

        debug_log('[BG Queue] act() end: jobId=' . $jobIdForLog . ' outcome=chunk-done (re-queued)', 'info', false);
        return $taskResponseDto;
    }






    public function getLastQueuedActionId()
    {
        return $this->lastQueuedActionId;
    }








    public function persist()
    {
        return $this->ajaxPrepareJob->persist();
    }







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

    abstract public function getDefaultDataConfiguration(): array;

    abstract protected function maybeInitJob(array $args);

    protected function getIsBackupJob(): bool
    {
        return false;
    }

    protected function getJobDefaultName(): string
    {
        return 'BackgroundJob';
    }











    private function persistDtoToAction($action = null, $dto = null)
    {
        try {
            if ($action === null || $dto === null) {
                return;
            }

            $this->queue->updateActionFields($action->id, ['custom' => $this->getLogFile(), 'response' => serialize($dto)], true);

            $errorMessage = $this->getLastErrorMessage();
            if ($errorMessage !== false) {
                debug_log($errorMessage);
            }
        } catch (Exception $e) {
 
        }
    }






    protected function handleError(string $errorMessage, array $args = [])
    {
        $body = '';
        $job  = $this->getIsBackupJob() ? 'backup' : 'job';
        if (array_key_exists('scheduleId', $args)) {
            $body .= 'Error in scheduled ' . $job . PHP_EOL . PHP_EOL;
        } else {
            $body .= 'Error in background ' . $job . PHP_EOL . PHP_EOL;
        }

        $jobDataDto = $this->job->getJobDataDto();
        $date = new \DateTime();
        if (!empty($jobDataDto->getStartTime())) {
            $date->setTimestamp($jobDataDto->getStartTime());
        }

 
 
 
        try {
            $jobDuration = str_replace(['minutes', 'seconds'], ['min', 'sec'], $this->times->getHumanReadableDuration(gmdate('i:s', $jobDataDto->getDuration())));
        } catch (\Throwable $e) {
            $jobDuration = $jobDataDto->getDuration() . 's';
        }

        $body .= 'Started at: ' .  $date->format('H:i:s') . PHP_EOL ;
        $body .= 'Duration: ' . $jobDuration . PHP_EOL;
        $body .= 'Job ID: ' . $args['jobId'] . PHP_EOL . PHP_EOL;
        $body .= 'Error Message: ' . $errorMessage;





        $backupScheduler = WPStaging::make(BackupScheduler::class);
        $title = $this->getIsBackupJob() ? '' : esc_html__('WP Staging - Error Report', 'wp-staging');
        $backupScheduler->sendErrorReport($body, $title);

        $jobTransientCache = $this->job->getTransientCache();

        $failure = [
            'jobTransientCache' => $jobTransientCache,
            'errorMessage'      => $errorMessage,
            'jobDataDto'        => $this->job->getJobDataDto(),
        ];

        Hooks::callInternalHook(self::ACTION_JOB_FAILURE, $failure);
        Hooks::doAction(self::ACTION_JOB_FAILURE, $failure);

        $jobTransientCache->failJob('', $errorMessage);
    }




    private function getLastErrorMessage()
    {
        $currentTask = $this->job === null ? null : $this->job->getCurrentTask();
        if (empty($currentTask) && $this->handlingError) {
            return 'Current task is not available';
        }

        if (empty($currentTask)) {
            return false;
        }

        $error = $currentTask->getLogger()->getLastErrorMsg();
        if (empty($error)) {
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

    private function getLogFile(): string
    {
        if ($this->job === null || $this->job->getCurrentTask() === null) {
            return '';
        }

        if ($this->job->getCurrentTask()->getLogger() === null) {
            return '';
        }

 
 
 
        return (string)$this->job->getCurrentTask()->getLogger()->getFileName();
    }

    private function isJobCancelled(array $args): bool
    {
        return $this->queue->count(Queue::STATUS_CANCELED, $args['jobId']) > 0;
    }
}
