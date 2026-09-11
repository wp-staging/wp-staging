<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;









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




    protected function beforeMarkedStalled()
    {
        $this->failureReason = UpdateProtectionHealth::REASON_STALLED;
    }










    protected function getFailureReport(string $reason): array
    {
        return [
            'reason'  => $this->failureReason !== '' ? $this->failureReason : $this->health->classify($reason),
            'message' => substr($reason, 0, self::MAX_REPORTED_MESSAGE_LENGTH),
        ];
    }
}
