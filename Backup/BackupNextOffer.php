<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Onboarding\QueuedBackup;
use WPStaging\Framework\SiteInfo;

/**
 * Decides whether to offer "Create Backup Next" while a staging site is being
 * created, outside the first run.
 *
 * The offer is worth making to someone who is not covered, and only noise to
 * someone who is. "Covered" is read from what the site already records — the
 * backups on disk and the schedules WP STAGING keeps — rather than from a flag
 * of its own, so it stays true as the user's habits change.
 *
 * @see \WPStaging\Framework\Onboarding\QueuedBackup for the queue itself, which
 *      is shared with the first run.
 */
class BackupNextOffer
{
    /** @var QueuedBackup */
    private $queuedBackup;

    /** @var BackupsFinder */
    private $backupsFinder;

    /** @var BackupScheduler */
    private $backupScheduler;

    /** @var SiteInfo */
    private $siteInfo;

    /** @var bool|null */
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

    /**
     * Resolved defensively: this runs on the staging page of every install, and
     * a missing scheduler dependency must not take that page down.
     *
     * @return BackupNextOffer|null
     */
    public static function resolve()
    {
        try {
            return WPStaging::make(self::class);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Suppressed only for an established backup workflow, which means both
     * halves: backups that exist and a schedule that keeps making them. One
     * without the other is not protection — a folder of old manual backups goes
     * stale, and a schedule that has never produced anything has not been proven.
     */
    public function isEligible(): bool
    {
        if ($this->isEligible === null) {
            $this->isEligible = $this->check();
        }

        return $this->isEligible;
    }

    /**
     * @return string One of the QueuedBackup STATUS_* values, or empty.
     */
    public function getStatus(): string
    {
        return $this->queuedBackup->getStatus();
    }

    private function check(): bool
    {
        if (!current_user_can('manage_options') || $this->siteInfo->isStagingSite()) {
            return false;
        }

        // Already asked for: the confirmed state renders from the status instead.
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
            // An unreadable backup directory says nothing about the user's
            // habits, so it must not silently turn the offer on.
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
