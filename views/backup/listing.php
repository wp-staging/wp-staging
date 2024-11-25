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
 * @var bool                        $isValidLicense
 * @var bool                        $isProVersion
 * @var bool                        $hasSchedule
 */

$disabledProperty = !$isProVersion || $isValidLicense ? '' : 'disabled';

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

$storages              = WPStaging::make(\WPStaging\Backup\Storage\Providers::class);
$isEnabledCloudStorage = false;
foreach ($storages->getStorages(true) as $storage) {
    $isActivated = $storages->isActivated($storage['authClass']);
    if ($isActivated) {
        $isEnabledCloudStorage = true;
        break;
    }
}
?>

<?php
/** @var BackupScheduler */
$backupScheduler = WPStaging::make(BackupScheduler::class);
$cronStatus      = $backupScheduler->checkCronStatus();
$cronMessage     = $backupScheduler->getCronMessage();
if ($cronMessage !== '') { ?>
    <div class="notice <?php echo $cronStatus === true ? 'notice-warning' : 'notice-error'; ?>" style="margin-bottom: 10px;">
        <p><strong><?php esc_html_e('WP STAGING Notice:', 'wp-staging') ?></strong></p>
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

    $downloadText = __('Read More or Upgrade to Pro', 'wp-staging');
    $downloadLink = 'https://wp-staging.com/docs/wp-staging-restore/';

    if (defined('WPSTGPRO_VERSION')) {
        $downloadText = __('Download Now', 'wp-staging');
        $downloadLink = get_admin_url() . 'admin.php?page=wpstg-restorer';
    }

    printf(
        Escape::escapeHtml(
            __('Get the new standalone tool %s <a href="%s">%s</a>', 'wp-staging')
        ),
        '<span style="font-weight: bold">WP Staging | Restore:</span>',
        esc_url($downloadLink),
        esc_html($downloadText)
    );
    ?>
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
    <?php if ($isEnabledCloudStorage && $isValidLicense) : ?>
    <button id="wpstg-show-cloud-backup" class="wpstg-next-step-link wpstg-button wpstg-border-thin-button wpstg-ml-4" <?php echo esc_attr($disabledProperty); ?> <?php echo esc_attr($disabledPropertyCreateBackup) ?>>
        <?php esc_html_e('Load Remote Backups', 'wp-staging') ?>
    </button>
    <?php endif; ?>
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
    <div id="wpstg-existing-cloud-backups">
        <div class="wpstg-existing-cloud-backups-header">
            <span id="remote-backup-title"><?php echo esc_html__('Remote Backups:', 'wp-staging'); ?></span>
        </div>
        <div class="wpstg-cloud-backup-list">
            <ul id="wpstg-cloud-backup-list-ul">
                <li><?php esc_html_e('Searching for remote backups...', 'wp-staging') ?></li>
            </ul>
            <ul class="wpstg-cloud-backup-empty-message">
                <li id="wpstg-cloud-backup-no-results" class="wpstg-clone wpstg-backup-no-results-cloud-backup wpstg-backup-list-ul">
                    <img class="wpstg--dashicons" src="<?php echo esc_url($urlAssets); ?>svg/cloud.svg" alt="cloud">
                    <div class="no-backups-found-text">
                        <?php esc_html_e('No remote Backups found. Create your first Backup above!', 'wp-staging'); ?>
                    </div>
                </li>
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
