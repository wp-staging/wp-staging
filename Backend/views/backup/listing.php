<?php

use WPStaging\Core\WPStaging;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Backup\Ajax\ScheduleList;
use WPStaging\Backup\BackupProcessLock;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Backup\Exceptions\ProcessLockedException;

/**
 * @see \WPStaging\Backup\Ajax\Listing::render
 *
 * @var TemplateEngine              $this
 * @var array                       $directories
 * @var string                      $urlAssets
 * @var Directory                   $directory
 * @var bool                        $isValidLicense
 * @var bool                        $isProVersion
 * @var bool                        $hasSchedule
 */

$disabledProperty = $isValidLicense ? '' : 'disabled';

$backupProcessLock = WPStaging::make(BackupProcessLock::class);
try {
    $backupProcessLock->checkProcessLocked();
    $isLocked = false;
    $disabledPropertyCreateBackup = '';
} catch (ProcessLockedException $e) {
    $isLocked = true;
    $disabledPropertyCreateBackup = 'disabled';
}
?>

<?php
/** @var BackupScheduler */
$backupScheduler = WPStaging::make(BackupScheduler::class);
$cronStatus  = $backupScheduler->checkCronStatus();
$cronMessage = $backupScheduler->getCronMessage();
if ($cronMessage !== '') { ?>
    <div class="notice <?php echo $cronStatus === true ? 'notice-warning' : 'notice-error'; ?>" style="margin-bottom: 10px;">
        <p><strong><?php esc_html_e('WP STAGING Notice:', 'wp-staging') ?></strong></p>
        <p><?php echo Escape::escapeHtml($cronMessage); ?></p>
    </div>
<?php } ?>

<?php if ($isLocked) : ?>
    <div id="wpstg-backup-locked">
        <div class="icon"><img width="20" src="<?php echo esc_url(WPSTG_PLUGIN_URL . "assets/img/wpstaging-icon.png"); ?>"></div>
        <div class="text"><?php esc_html_e('There is a backup work in progress...', 'wp-staging'); ?></div>
    </div>
<?php endif; ?>
<div id="wpstg-did-you-know" style="margin-bottom:12px">
        <strong><?php echo sprintf(
            Escape::escapeHtml(__('Did you know? You can upload backup files to another site to transfer a website. <a href="%s" target="_blank">Read more</a>', 'wp-staging')),
            'https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/'
        ); ?></strong>
</div>

<div id="wpstg-step-1">
    <button id="wpstg-new-backup" class="wpstg-next-step-link wpstg-blue-primary wpstg-button" <?php echo esc_attr($disabledProperty); ?> <?php echo esc_attr($disabledPropertyCreateBackup) ?>>
        <?php esc_html_e('Create Backup', 'wp-staging') ?>
    </button>
    <button type="button" id="wpstg-upload-backup" class="wpstg-button wpstg-border-thin-button" <?php echo esc_attr($disabledProperty) ?>>
        <?php esc_html_e('Upload Backup', 'wp-staging') ?>
    </button>
    <button id="wpstg-manage-backup-schedules" class="wpstg-button wpstg-border-thin-button" <?php echo esc_attr($disabledProperty) ?>>
        <?php esc_html_e('Edit Backup Plans', 'wp-staging') ?>
    </button>
    <div id="wpstg-report-issue-wrapper">
        <button type="button" id="wpstg-report-issue-button" class="wpstg-button">
            <i class="wpstg-icon-issue"></i><?php echo esc_html__("Contact Us", "wp-staging"); ?>
        </button>
        <?php require_once($this->views . '_main/report-issue.php'); ?>
    </div>
</div>

<div id="wpstg-backup-runs-info">
    <?php WPStaging::make(ScheduleList::class)->renderNextBackupSnippet(); ?>
</div>

<div id="wpstg-existing-backups">
        <div id="backup-messages"></div>
        <div class="wpstg-backup-list">
            <ul>
                <li><?php esc_html_e('Searching for existing backups...', 'wp-staging') ?></li>
            </ul>
        </div>
</div>

<?php include(__DIR__ . '/modal/backup.php'); ?>
<?php include(__DIR__ . '/modal/progress.php'); ?>
<?php include(__DIR__ . '/modal/download.php'); ?>
<?php include(__DIR__ . '/modal/download-modal.php'); ?>
<?php include(__DIR__ . '/modal/upload.php'); ?>
<?php include(__DIR__ . '/modal/manage-schedules.php'); ?>
<?php include(__DIR__ . '/modal/edit-schedule-modal.php'); ?>
<?php include(__DIR__ . '/modal/restore.php'); ?>

<?php include(__DIR__ . '/restore-wait.php'); ?>

<div
    id="wpstg--js--translations"
    style="display:none;"
    data-modal-txt-critical="<?php esc_attr_e('Critical', 'wp-staging') ?>"
    data-modal-txt-errors="<?php esc_attr_e('Error(s)', 'wp-staging') ?>"
    data-modal-txt-warnings="<?php esc_attr_e('Warning(s)', 'wp-staging') ?>"
    data-modal-txt-and="<?php esc_attr_e('and', 'wp-staging') ?>"
    data-modal-txt-found="<?php esc_attr_e('Found', 'wp-staging') ?>"
    data-modal-txt-show-logs="<?php esc_attr_e('Show Logs', 'wp-staging') ?>"
    data-modal-logs-title="<?php esc_attr_e(
        '{critical} Critical, {errors} Error(s) and {warnings} Warning(s) Found',
        'wp-staging'
    ) ?>"
></div>

<div id="wpstg-delete-confirmation"></div>
