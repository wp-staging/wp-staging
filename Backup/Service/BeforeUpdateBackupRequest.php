<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Job\Jobs\JobBackup;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\Exceptions\QueueException;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Task\AbstractTask;
use WPStaging\Framework\Utils\Cache\Cache;









class BeforeUpdateBackupRequest extends AbstractBackgroundBackupRequest
{
 
    const OPTION_STATE = 'wpstg_backup_before_update_request';

 
    const MAX_REPORTED_MESSAGE_LENGTH = 200;

 
    const OUTCOME_STARTED         = 'started';
    const OUTCOME_ALREADY_RUNNING = 'already_running';
    const OUTCOME_FAILED          = 'failed';

 
    private $health;

 
    private $failureReason = '';




    public function __construct(UpdateProtectionHealth $health)
    {
        $this->health = $health;
    }

    const TRANSIENT_NUDGE_LOCK = 'wpstg_backup_before_update_nudge';

    const ANALYTICS_GROUP = 'backup_before_update';

    const EVENT_STARTED   = 'before_update_backup_started';
    const EVENT_COMPLETED = 'before_update_backup_completed';
    const EVENT_FAILED    = 'before_update_backup_failed';











    public function startForUpdate(array $backupData, string $pluginFile = ''): string
    {
        if ($this->isPending()) {
            $this->queuePlugin($pluginFile);

            return self::OUTCOME_ALREADY_RUNNING;
        }

        $this->write([
            'status'        => self::STATUS_QUEUED,
            'plugin_files'  => $pluginFile === '' ? [] : [$pluginFile],
            'queued_at'     => time(),
            'backup_job_id' => '',
        ]);

        if (!$this->start($backupData)) {
            return self::OUTCOME_FAILED;
        }

        return self::OUTCOME_STARTED;
    }





    public function onBackgroundJobFailure($failure = [])
    {
        $errorCode = is_array($failure) && isset($failure['errorCode']) ? (string)$failure['errorCode'] : '';

        $this->failureReason = $this->health->reasonForErrorCode($errorCode);
        parent::onBackgroundJobFailure($failure);
        $this->failureReason = '';
    }












    public function cancel(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        if ($this->getStatus() === self::STATUS_RUNNING && !$this->cancelRunningBackup()) {
            return false;
        }

        $this->clear();

        return true;
    }











    public function queuePlugin(string $pluginFile)
    {
        $waiting = $this->getPendingPluginFiles();

        if ($pluginFile === '' || !$this->isPending() || in_array($pluginFile, $waiting, true)) {
            return;
        }

        $waiting[] = $pluginFile;

        $this->write(array_merge($this->read(), ['plugin_files' => $waiting]));
    }











    protected function jobDataIdentifiesOwnBackup($jobDataDto, $job): bool
    {
        if (!$jobDataDto instanceof JobBackupDataDto || !$jobDataDto->getIsBeforeUpdateBackup()) {
            return false;
        }

        return $job === null;
    }





    public function getPendingPluginFiles(): array
    {
        if (!$this->isPending()) {
            return [];
        }

        $state = $this->read();

        return (isset($state['plugin_files']) && is_array($state['plugin_files'])) ? $state['plugin_files'] : [];
    }




    protected function getOptionName(): string
    {
        return self::OPTION_STATE;
    }




    protected function getNudgeTransientName(): string
    {
        return self::TRANSIENT_NUDGE_LOCK;
    }




    protected function getEventNames(): array
    {
        return [
            'started'   => self::EVENT_STARTED,
            'completed' => self::EVENT_COMPLETED,
            'failed'    => self::EVENT_FAILED,
        ];
    }




    protected function getAnalyticsGroup(): string
    {
        return self::ANALYTICS_GROUP;
    }




    protected function getLogContext(): string
    {
        return 'WP STAGING Backup Before Update';
    }









    protected function afterFailed(string $reason = '')
    {
        $this->health->recordFailure($reason, $this->failureReason);
        $this->failureReason = '';
    }

 
    protected function classifyStallAndDescribeWhereItStopped(): string
    {
        $this->failureReason = UpdateProtectionHealth::REASON_STALLED;

        $queueId   = (string)($this->read()['backup_job_id'] ?? '');
        $jobData   = $this->readJobDataSavedFor($queueId);
        $taskQueue = isset($jobData['taskQueue']) && is_array($jobData['taskQueue']) ? $jobData['taskQueue'] : [];
        $taskIndex = (int)($jobData['currentTaskIndex'] ?? 0);
        $taskClass = AbstractJob::splitTaskQueueEntry((string)($taskQueue[$taskIndex] ?? ''))[0];

        if (!is_subclass_of($taskClass, AbstractTask::class)) {
            return '';
        }

        return sprintf(
            /* translators: 1: number of the backup step that stopped, 2: number of steps in the backup, 3: name of that step, 4: how much of that step was done, in percent. */
            __('The recovery backup stopped responding during step %1$d of %2$d: %3$s (%4$d%%).', 'wp-staging'),
            $taskIndex + 1,
            count($taskQueue),
            $taskClass::getTaskTitle(),
            $this->readPercentageTaskReported($queueId, $taskClass)
        );
    }

 
    private function readJobDataSavedFor(string $queueId): array
    {
        $cache = WPStaging::make(Cache::class);
        $cache->setLifetime(-1);
        $cache->setFilename(AbstractJob::JOB_DATA_CACHE_PREFIX . JobBackup::getJobName());
        $jobData = $cache->get([]);

        return is_array($jobData) && ($jobData[AbstractJob::CACHE_OWNER_KEY] ?? null) === $queueId ? $jobData : [];
    }

 
    private function readPercentageTaskReported(string $queueId, string $taskClass): int
    {
        try {
            $action = WPStaging::make(Queue::class)->getLatestActionWithResponse($queueId);
        } catch (QueueException $e) {
            return 0;
        }

        $response = $action === null ? null : $action->response;

        return $response instanceof TaskResponseDto && $response->getTask() === $taskClass::getTaskName() ? (int)$response->getPercentage() : 0;
    }










    protected function getFailureReport(string $reason): array
    {
        return [
            'reason'  => $this->failureReason !== '' ? $this->failureReason : $this->health->classify($reason),
            'message' => substr($reason, 0, self::MAX_REPORTED_MESSAGE_LENGTH),
        ];
    }
}
