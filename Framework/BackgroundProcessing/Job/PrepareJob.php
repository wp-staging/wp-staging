<?php







namespace WPStaging\Framework\BackgroundProcessing\Job;

use Exception;
use WP_Error;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsGenericEvent;
use WPStaging\Framework\Analytics\AnalyticsEventDto;
use WPStaging\Framework\Analytics\ErrorCode;
use WPStaging\Framework\BackgroundProcessing\Action;
use WPStaging\Framework\BackgroundProcessing\Exceptions\QueueException;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\BackgroundProcessing\QueueActionAware;
use WPStaging\Framework\BackgroundProcessing\QueueProcessor;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Job\Ajax\PrepareJob as AjaxPrepareJob;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Job\Task\AbstractTask;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Utils\Times;

use function WPStaging\functions\debug_log;







abstract class PrepareJob
{
    use ResourceTrait;
    use QueueActionAware;







    const ACTION_JOB_FAILURE = 'wpstg_background_job_failure';

 
    const ARG_STEP_RETRIED_AFTER_WORKER_STOPPED = 'stepRetriedAfterWorkerStopped';

 
    const WORKER_STOPPED_RETRY_DELAY_SECONDS = 30;

 
    const ANALYTICS_GROUP_JOB = 'background_job';

 
    const EVENT_JOB_FAILED = 'job_failed';

 
    const MAX_REPORTED_MESSAGE_LENGTH = 200;

 
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









    private function queueAction($args, int $notBefore = 0)
    {
        if (!isset($args['jobId'])) {
            throw new \BadMethodCallException();
        }

        $action   = $this->getCurrentAction();
        $priority = $action === null ? 0 : $action->priority - 1;
        $actionId = $this->queue->enqueueAction(static::class . '::' . 'act', $args, $args['jobId'], $priority, $notBefore);

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

        if ($this->isJobCancelled($args)) {
            $this->processLock->unlockProcess();
            return new WP_Error(499, 'Job cancelled by user.');
        }

        if ($this->queue->hasDeferredForegroundUpload()) {
            $this->queueAction($args);
            $this->processLock->unlockProcess();
            return new WP_Error(423, 'A foreground job is waiting before its next request.');
        }

        try {
            $this->maybeInitJob($args);
        } catch (Exception $e) {
            return $this->abortJobThatFailedToInitialize($args, $e, $jobIdForLog);
        }

 
        $this->job->setOwnerJobId(isset($args['jobId']) ? (string)$args['jobId'] : '');

        if (empty($args['isInit']) && !$this->job->hasPersistedData()) {
            return $this->abortLostJob($args, $jobIdForLog);
        }

        $args['isInit']  = false;
        $taskResponseDto = null;

 
 
 
 
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            debug_log('[Schedule Job Data DTO]: ' . json_encode($this->job->getJobDataDto()), 'debug', false);
        }

        $isStepRunning = false;
        if ($this->getIsBackupJob()) {
            add_action('shutdown', function () use (&$isStepRunning, &$args) {
                if ($isStepRunning) {
                    $this->retryOrFailStepWhoseWorkerStopped($args);
                }
            });
        }

        do {
            $isStepRunning = true;
            try {
 
                $taskResponseDto = $this->job->prepareAndExecute();
                $this->job->persist();
                $this->persistDtoToAction($this->getCurrentAction(), $taskResponseDto);
                if ($taskResponseDto->getJobStatus() !== 'JOB_RETRY') {
                    unset($args[self::ARG_STEP_RETRIED_AFTER_WORKER_STOPPED]);
                }
            } catch (ProcessLockedException $e) {
 
 
 
                $this->queueAction($args);

                debug_log('[BG Queue] act() end: jobId=' . $jobIdForLog . ' outcome=process-locked-midrun (re-queued)', 'info', false);
                return new WP_Error(400, $e->getMessage());
            } catch (\Throwable $e) {
                debug_log('Action for ' . $args['jobId'] . ' failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
                $this->handlingError = true;
                $this->handleError($e->getMessage(), $args, ErrorCode::fromThrowable($e));
                $this->recordFailedResponseOnCurrentAction($e->getMessage());

                return new WP_Error(400, $e->getMessage());
            } finally {
                $isStepRunning = false;
            }

            if ($this->isJobCancelled($args)) {
                return new WP_Error(499, 'Job cancelled by user.'); 
            }

            $errorMessage = $this->getLastErrorMessage();
            if ($errorMessage !== false) {
                $this->handleError($errorMessage, $args, $this->getLastErrorCode());
                $this->recordFailedResponseOnCurrentAction($errorMessage, $taskResponseDto);

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
            if ($taskResponseDto->getRetryAt() > 0) {
                $this->rewriteLapsedCurrentJobTransient($args);
                $this->queueAction($args, $taskResponseDto->getRetryAt());
                wp_schedule_single_event($taskResponseDto->getRetryAt(), \WPStaging\Framework\BackgroundProcessing\QueueProcessor::ACTION_QUEUE_PROCESS, ['uploadRetry' => $args['jobId']]);
                $this->processLock->unlockProcess();
                return $taskResponseDto;
            }
        } while (!$this->isThreshold() && !$taskResponseDto->endsRequest());

 
        if ($this->isJobCancelled($args)) {
            return new WP_Error(499, 'Job cancelled by user.'); 
        }

        $this->rewriteLapsedCurrentJobTransient($args);

        if ($taskResponseDto->endsRequest()) {
            WPStaging::make(QueueProcessor::class)->stopProcessing();
        }

 
        $this->queueAction($args);
        $this->processLock->unlockProcess();

        debug_log('[BG Queue] act() end: jobId=' . $jobIdForLog . ' outcome=chunk-done (re-queued)', 'info', false);
        return $taskResponseDto;
    }













    private function abortLostJob(array $args, string $jobIdForLog)
    {
        $message = 'Background job data was lost or could not be verified (jobId=' . $jobIdForLog . '). Aborting to avoid an unsafe restart.';
        $jobId = (string)($args['jobId'] ?? '');
        $currentJob = $this->job->getTransientCache()->getJob(true);
        $currentJobId = !empty($currentJob['queueId']) ? $currentJob['queueId'] : ($currentJob['jobId'] ?? '');
        $isTransientOfThisJob = $currentJobId !== '' && $currentJobId === $jobId;
 
        $hasThisJobEnded = ($isTransientOfThisJob && ($currentJob['status'] ?? '') !== JobTransientCache::STATUS_RUNNING) || $this->hasJobRecordedFinalResponse($jobId);
        if (!$hasThisJobEnded) {
            if ($isTransientOfThisJob) {
                $this->handleError($message, $args, ErrorCode::JOB_DATA_LOST);
            }

 
            $response = $this->job->getJobFailResponse($message, '');
            $response->setJobId($isTransientOfThisJob ? (string)($currentJob['jobId'] ?? '') : '');
            $this->persistDtoToAction($this->getCurrentAction(), $response);
        }

        $this->job->skipPersistOnShutdown();
        $this->processLock->unlockProcess();

        debug_log('[BG Queue] act() end: jobId=' . $jobIdForLog . ' outcome=aborted (data lost)', 'info', true);

        return new WP_Error(410, $message);
    }








    private function retryOrFailStepWhoseWorkerStopped(array $args)
    {
        try {
            $task = $this->job->getCurrentTask();
            $usesTaskQueue = $task instanceof AbstractTask && $task->usesTaskQueue();
            if (empty($args[self::ARG_STEP_RETRIED_AFTER_WORKER_STOPPED]) && !$usesTaskQueue) {
                $args[self::ARG_STEP_RETRIED_AFTER_WORKER_STOPPED] = true;
                $retryAt = time() + self::WORKER_STOPPED_RETRY_DELAY_SECONDS;
                $this->queueAction($args, $retryAt);
                wp_schedule_single_event($retryAt, QueueProcessor::ACTION_QUEUE_PROCESS, ['workerStoppedRetry', $args['jobId']]);
                debug_log('[BG Queue] act() end: jobId=' . $args['jobId'] . ' outcome=worker stopped (step re-queued once)', 'info', true);
                return;
            }

            $message = 'The background process stopped while running a backup step, for example on a PHP fatal error, a timeout or the memory limit.';
            $lastError = error_get_last();
            if (is_array($lastError) && ($lastError['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)) !== 0) {
                $message .= ' Last PHP error: ' . $lastError['message'];
            }

            $this->handleError($message, $args, ErrorCode::WORKER_STOPPED);
            $this->recordFailedResponseOnCurrentAction($message);
        } catch (\Throwable $e) {
            debug_log('The backup step whose background process stopped could not be retried or failed: ' . $e->getMessage());
        }
    }











    private function abortJobThatFailedToInitialize(array $args, Exception $exception, string $jobIdForLog)
    {
        if ($this->job === null) {
            throw $exception;
        }

        $this->job->skipPersistOnShutdown();
        $this->handleError($exception->getMessage(), $args, ErrorCode::fromThrowable($exception));
        $this->recordFailedResponseOnCurrentAction($exception->getMessage());
        $this->processLock->unlockProcess();

        debug_log('[BG Queue] act() end: jobId=' . $jobIdForLog . ' outcome=aborted (preparation failed): ' . $exception->getMessage(), 'info', false);

        return new WP_Error(400, $exception->getMessage());
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








    protected function rewriteLapsedCurrentJobTransient(array $args)
    {
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







    protected function handleError(string $errorMessage, array $args = [], string $errorCode = '')
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

        $this->sendJobFailureReport($body);

        $jobTransientCache = $this->job->getTransientCache();

        $failure = [
            'jobTransientCache' => $jobTransientCache,
            'errorMessage'      => $errorMessage,
            'errorCode'         => $errorCode,
            'jobDataDto'        => $this->job->getJobDataDto(),
        ];

        $this->notifyJobFailureListeners($failure);

        $this->recordBackupFailureInAnalytics($jobDataDto, $errorMessage, $errorCode);
        $this->recordFailureCodeInAnalytics($errorCode, $errorMessage, $args);

        $jobTransientCache->failJob('', $errorMessage);
    }










    private function recordBackupFailureInAnalytics(JobDataDto $jobDataDto, string $errorMessage, string $errorCode)
    {
        if (!$this->getIsBackupJob() || !$jobDataDto->hasId() || $jobDataDto->getIsSyncRequest()) {
            return;
        }

        try {
            AnalyticsEventDto::enqueueErrorEvent($jobDataDto->getId(), $errorMessage, $errorCode);
        } catch (\Throwable $e) {
            debug_log('The analytics error event of the failed backup could not be recorded: ' . $e->getMessage());
        }
    }








    private function recordFailedResponseOnCurrentAction(string $errorMessage, $lastResponse = null)
    {
        if ($lastResponse instanceof TaskResponseDto && $lastResponse->getJobStatus() === 'JOB_FAIL') {
            return;
        }

        $this->persistDtoToAction($this->getCurrentAction(), $this->job->getJobFailResponse($errorMessage, ''));
    }










    private function recordFailureCodeInAnalytics(string $errorCode, string $errorMessage, array $args)
    {
        try {
            AnalyticsGenericEvent::logEvent(
                self::EVENT_JOB_FAILED,
                self::ANALYTICS_GROUP_JOB,
                [
                    'error_code' => ErrorCode::sanitize($errorCode),
                    'job_type'   => $this->getIsBackupJob() ? 'backup' : 'job',
                    'scheduled'  => array_key_exists('scheduleId', $args) ? 'yes' : 'no',
                    'job_id'     => isset($args['jobId']) ? (string)$args['jobId'] : '',
                    'message'    => substr($errorMessage, 0, self::MAX_REPORTED_MESSAGE_LENGTH),
                ]
            );
        } catch (\Throwable $e) {
            debug_log('The analytics event of the failed job could not be recorded: ' . $e->getMessage());
        }
    }










    private function sendJobFailureReport(string $body)
    {
        try {
 
            $backupScheduler = WPStaging::make(BackupScheduler::class);
            $title = $this->getIsBackupJob() ? '' : esc_html__('WP Staging - Error Report', 'wp-staging');
            $backupScheduler->sendErrorReport($body, $title);
        } catch (\Throwable $e) {
            debug_log('The error report for the failed job could not be sent: ' . $e->getMessage());
        }
    }








    private function notifyJobFailureListeners(array $failure)
    {
        try {
            Hooks::callInternalHook(self::ACTION_JOB_FAILURE, [$failure]);
        } catch (\Throwable $e) {
            debug_log('The internal listener of ' . self::ACTION_JOB_FAILURE . ' threw: ' . $e->getMessage());
        }

        try {
            Hooks::doAction(self::ACTION_JOB_FAILURE, $failure);
        } catch (\Throwable $e) {
            debug_log('A listener of ' . self::ACTION_JOB_FAILURE . ' threw: ' . $e->getMessage());
        }
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

    private function getLastErrorCode(): string
    {
        $currentTask = $this->job === null ? null : $this->job->getCurrentTask();
        if (empty($currentTask)) {
            return '';
        }

        $error = $currentTask->getLogger()->getLastErrorMsg();

        return is_array($error) && isset($error['errorCode']) ? (string)$error['errorCode'] : '';
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

    private function hasJobRecordedFinalResponse(string $jobId): bool
    {
        try {
            $action = $this->queue->getLatestActionWithResponse($jobId);
        } catch (QueueException $e) {
            return false;
        }

        return $action instanceof Action && $action->response instanceof TaskResponseDto && !$action->response->isRunning();
    }
}
