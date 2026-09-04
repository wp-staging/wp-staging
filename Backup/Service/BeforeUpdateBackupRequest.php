<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Job\JobTransientCache;









class BeforeUpdateBackupRequest extends AbstractBackgroundBackupRequest
{
 
    const OPTION_STATE = 'wpstg_backup_before_update_request';







    const STALL_GRACE_IN_SECONDS = 5 * MINUTE_IN_SECONDS;

 
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











    public function queuePlugin(string $pluginFile)
    {
        $waiting = $this->getPendingPluginFiles();

        if ($pluginFile === '' || !$this->isPending() || in_array($pluginFile, $waiting, true)) {
            return;
        }

        $waiting[] = $pluginFile;

        $this->write(array_merge($this->read(), ['plugin_files' => $waiting]));
    }















    public function failIfStalled(): bool
    {
        if ($this->getStatus() !== self::STATUS_RUNNING || !$this->isOlderThanStallGrace()) {
            return false;
        }

        if ($this->isBackupJobStillKnown()) {
            return false;
        }

        $this->failureReason = UpdateProtectionHealth::REASON_STALLED;
        $this->markFailed();

        return true;
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










    protected function getFailureReport(string $reason): array
    {
        return [
            'reason'  => $this->failureReason !== '' ? $this->failureReason : $this->health->classify($reason),
            'message' => substr($reason, 0, self::MAX_REPORTED_MESSAGE_LENGTH),
        ];
    }




    private function isOlderThanStallGrace(): bool
    {
        $state     = $this->read();
        $startedAt = isset($state['started_at']) ? (int)$state['started_at'] : 0;

        return $startedAt > 0 && $startedAt < time() - self::STALL_GRACE_IN_SECONDS;
    }




    private function isBackupJobStillKnown(): bool
    {
        $state = $this->read();
        if (empty($state['backup_job_id'])) {
            return false;
        }

        try {
            $job = WPStaging::make(JobTransientCache::class)->getJob();
        } catch (\Throwable $e) {
 
 
            return true;
        }

        return is_array($job) && isset($job['queueId']) && $job['queueId'] === $state['backup_job_id'];
    }
}
