<?php

/**
 * Free-only callout telling the reader a backup plan is already scheduled.
 *
 * @var bool $isProVersion
 * @var bool $hasSchedule
 * @see src/views/backup/modal/backup.php
 */

use WPStaging\Backup\Task\Tasks\JobBackup\ScheduleBackupTask;
use WPStaging\Basic\Ajax\ProCronsCleaner;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\Utils\Times;

if ($isProVersion) {
    return;
}

$haveProCrons = WPStaging::make(ProCronsCleaner::class)->haveProCrons();

$noticeTitle = $haveProCrons
    ? __('A backup plan from WP Staging Pro is still scheduled', 'wp-staging')
    : __('A daily backup plan is already scheduled', 'wp-staging');

$noticeMessage = $haveProCrons
    ? __('The free version cannot run it. Delete it under Manage Plans to schedule a backup plan with the free version of WP Staging.', 'wp-staging')
    : sprintf(
        /* translators: %s: time of day the free version runs the backup plan, for example 12:00 am. */
        __('It runs every day at %s and keeps only the latest backup. The free version allows one backup plan, so delete it under Manage Plans to schedule a different one.', 'wp-staging'),
        WPStaging::make(Times::class)->formatTimeOfDay(ScheduleBackupTask::BASIC_SCHEDULE_TIME)
    );
?>
<div class="wpstg-mt-4 wpstg-upgrade-callout wpstg-basic-schedule-notice wpstg-is-basic" style="display: <?php echo $hasSchedule ? 'block !important' : 'none'; ?>">
    <div class="wpstg-upgrade-callout-header">
        <div class="wpstg-upgrade-callout-icon" aria-hidden="true">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <path d="M16 2v4"/>
                <path d="M8 2v4"/>
                <path d="M3 10h18"/>
                <path d="M12 14v3"/>
                <path d="M10.5 15.5h3"/>
            </svg>
        </div>
        <div class="wpstg-upgrade-callout-content">
            <div class="wpstg-upgrade-callout-title">
                <?php echo esc_html($noticeTitle); ?>
                <span class="wpstg-badge-pro"><?php esc_html_e('Pro', 'wp-staging'); ?></span>
            </div>
            <p class="wpstg-upgrade-callout-description">
                <?php echo esc_html($noticeMessage); ?>
                <?php esc_html_e('Upgrade to Pro to create unlimited backup plans, choose the start time, and upload scheduled backups to cloud storage.', 'wp-staging'); ?>
            </p>
            <div class="wpstg-upgrade-callout-actions">
                <a href="<?php echo esc_url(Language::getUpgradeUrl('backup_schedule')); ?>" target="_blank" rel="noopener noreferrer" class="wpstg-btn wpstg-btn-md wpstg-btn-primary"><?php esc_html_e('Upgrade to Pro', 'wp-staging'); ?></a>
            </div>
        </div>
    </div>
</div>
