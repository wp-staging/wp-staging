<?php

use WPStaging\Backup\Ajax\ScheduleList;
use WPStaging\Backup\BackupDownload;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

/**
 * @see \WPStaging\Backup\Ajax\Listing::render
 *
 * @var TemplateEngine              $this
 * @var array                       $directories
 * @var string                      $urlAssets
 * @var Directory                   $directory
 * @var bool                        $hasSchedule
 */

$backupProcessLock = WPStaging::make(ProcessLock::class);
WPStaging::make(BackupDownload::class)->deleteUnfinishedDownloads();
try {
    $backupProcessLock->checkProcessLocked();
    $isLocked                     = false;
    $disabledPropertyCreateBackup = '';
} catch (ProcessLockedException $e) {
    $isLocked                     = true;
    $disabledPropertyCreateBackup = 'disabled';
}

?>

<?php
/** @var BackupScheduler */
$backupScheduler = WPStaging::make(BackupScheduler::class);
$cronStatus      = $backupScheduler->checkCronStatus();
$cronMessage     = $backupScheduler->getCronMessage();
if ($cronMessage !== '') { ?>
    <div class="notice <?php echo $cronStatus === true ? 'notice-warning' : 'notice-error'; ?>" style="margin-bottom: 10px;">
        <p><?php echo Escape::escapeHtml($cronMessage); ?></p>
    </div>
<?php } ?>

<?php if ($isLocked) : ?>
    <div id="wpstg-backup-locked">
        <div class="wpstg-locked-backup-loader"></div>
        <div class="text"><?php esc_html_e('There is a backup work in progress...', 'wp-staging'); ?></div>
    </div>
<?php endif; ?>

<div class="wpstg-did-you-know">
    <?php
    echo Escape::escapeHtml(
        __('<strong>New:</strong> One-click backup restore and migration even if WordPress is down?', 'wp-staging')
    );
    ?>
    </br>
    <?php
    printf(
        '%s %s',
        '<span style="font-weight: bold">' . esc_html_e('Download WP Staging Restore and Extraction Tool:', 'wp-staging') . '</span>',
        '<a href="https://wp-staging.com/docs/wp-staging-restore/">' . esc_html__('Read More or Upgrade to Pro', 'wp-staging') . '</a>'
    );
    ?>
</div>

<div id="wpstg-step-1">
    <button id="wpstg-new-backup" class="wpstg-next-step-link wpstg-blue-primary wpstg-button" <?php echo esc_attr($disabledPropertyCreateBackup) ?>>
        <?php esc_html_e('Create Backup', 'wp-staging') ?>
    </button>
    <button type="button" id="wpstg-upload-backup" class="wpstg-button wpstg-border-thin-button">
        <?php esc_html_e('Upload Backup', 'wp-staging') ?>
    </button>
    <button id="wpstg-manage-backup-schedules" class="wpstg-button wpstg-border-thin-button">
        <?php esc_html_e('Edit Backup Plans', 'wp-staging') ?>
    </button>
</div>

<div id="wpstg-backup-runs-info">
    <?php WPStaging::make(ScheduleList::class)->renderNextBackupSnippet(); ?>
</div>
<div class="wpstg-backup-listing-container">
    <div id="wpstg-existing-backups">
        <div id="backup-messages"></div>
        <div class="wpstg-backup-list">
            <span id="local-backup-title"><?php echo esc_html__('Local Backups:', 'wp-staging'); ?></span>
            <ul id="wpstg-backup-list-ul">
                <li><?php esc_html_e('Searching for existing backups...', 'wp-staging') ?></li>
            </ul>
        </div>
    </div>
</div>

<?php include(WPSTG_VIEWS_DIR . 'job/modal/process.php'); ?>
<?php include(WPSTG_VIEWS_DIR . 'job/modal/success.php'); ?>
<?php include(WPSTG_VIEWS_DIR . 'otp/overlay.php'); ?>
<?php include(WPSTG_VIEWS_DIR . 'backup/modal/partials/backup-success.php'); ?>

<?php include(__DIR__ . '/modal/backup.php'); ?>
<?php include(__DIR__ . '/modal/download-modal.php'); ?>
<?php include(__DIR__ . '/modal/upload.php'); ?>
<?php include(__DIR__ . '/modal/manage-schedules.php'); ?>
<?php include(__DIR__ . '/modal/remote-upload.php'); ?>
<?php include(__DIR__ . '/modal/edit-schedule-modal.php'); ?>
<?php include(__DIR__ . '/modal/restore.php'); ?>

<?php include(__DIR__ . '/restore-wait.php'); ?>

<div id="wpstg-delete-confirmation"></div>
