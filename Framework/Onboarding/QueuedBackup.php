<?php

namespace WPStaging\Framework\Onboarding;

use WPStaging\Backup\Service\AbstractBackgroundBackupRequest;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Auth;








class QueuedBackup extends AbstractBackgroundBackupRequest
{
 
    const OPTION_STATE = 'wpstg_onboarding_queued_backup';

 
    const TRANSIENT_NUDGE_LOCK = 'wpstg_onboarding_backup_nudge';

    const EVENT_STARTED   = 'queued_backup_started';
    const EVENT_COMPLETED = 'queued_backup_completed';
    const EVENT_FAILED    = 'queued_backup_failed';









    public function queue(string $stagingJobId): bool
    {
        if ($this->isPending()) {
            return false;
        }

        $this->write([
            'status'         => self::STATUS_QUEUED,
            'staging_job_id' => $stagingJobId,
            'queued_at'      => time(),
            'backup_job_id'  => '',
        ]);

        return true;
    }










    public function getReportedStatus(): string
    {
        if ($this->isPending()) {
            return self::STATUS_RUNNING;
        }

        $reason = WPStaging::make(OnboardingJourney::class)->getCompletionReason();

        return $reason === OnboardingJourney::REASON_TWO_CAPABILITIES ? self::STATUS_COMPLETED : $this->getStatus();
    }







    public function startAfterStagingCreated()
    {
        if ($this->getStatus() !== self::STATUS_QUEUED) {
            return;
        }

        $this->start(['isAutomatedBackup' => false]);
    }








    public function discardOnStagingRequest()
    {
        if (!WPStaging::make(Auth::class)->isAuthenticatedRequest('', 'manage_options')) {
            return;
        }

        $this->discard();
    }







    public function discard()
    {
        if ($this->getStatus() !== self::STATUS_QUEUED) {
            return;
        }

        $this->clear();

        WPStaging::make(OnboardingJourney::class)->cancelSecondAction();
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
        return FreeOnboarding::ANALYTICS_GROUP;
    }




    protected function getLogContext(): string
    {
        return 'WP STAGING Onboarding';
    }






    protected function afterStarted()
    {
        WPStaging::make(OnboardingJourney::class)->startAction();
    }




    protected function afterFailed(string $reason = '')
    {
        WPStaging::make(OnboardingJourney::class)->cancelSecondAction();
    }
}
