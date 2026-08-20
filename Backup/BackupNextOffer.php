<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Onboarding\QueuedBackup;
use WPStaging\Framework\SiteInfo;













class BackupNextOffer
{
 
    private $queuedBackup;

 
    private $backupsFinder;

 
    private $backupScheduler;

 
    private $siteInfo;

 
    private $isEligible = null;

    public function __construct(
        QueuedBackup $queuedBackup,
        BackupsFinder $backupsFinder,
        BackupScheduler $backupScheduler,
        SiteInfo $siteInfo
    ) {
        $this->queuedBackup    = $queuedBackup;
        $this->backupsFinder   = $backupsFinder;
        $this->backupScheduler = $backupScheduler;
        $this->siteInfo        = $siteInfo;
    }







    public static function resolve()
    {
        try {
            return WPStaging::make(self::class);
        } catch (\Throwable $e) {
            return null;
        }
    }







    public function isEligible(): bool
    {
        if ($this->isEligible === null) {
            $this->isEligible = $this->check();
        }

        return $this->isEligible;
    }




    public function getStatus(): string
    {
        return $this->queuedBackup->getStatus();
    }

    private function check(): bool
    {
        if (!current_user_can('manage_options') || $this->siteInfo->isStagingSite()) {
            return false;
        }

 
        if ($this->queuedBackup->isPending()) {
            return true;
        }

        return !$this->hasSchedules() || !$this->hasBackups();
    }

    private function hasBackups(): bool
    {
        try {
            return $this->backupsFinder->findBackups() !== [];
        } catch (\Throwable $e) {
 
 
            return true;
        }
    }

    private function hasSchedules(): bool
    {
        try {
            return $this->backupScheduler->getSchedules() !== [];
        } catch (\Throwable $e) {
            return true;
        }
    }
}
