<?php

use WPStaging\Backup\Ajax\ScheduleList;
use WPStaging\Backup\BackupDownload;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

/**
 * @see \WPStaging\Backup\Ajax\Listing::render
 *
 * @var TemplateEngine              $this
 * @var array                       $directories
 * @var string                      $urlAssets
 * @var Directory                   $directory
 * @var bool                        $hasSchedule
 * @var bool                        $isProVersion
 * @var bool                        $isValidLicense
 * @var bool                        $isPersonalLicense
 * @var string                      $licenseType
 */

WPStaging::make(BackupDownload::class)->deleteUnfinishedDownloads();

/** @var BackupScheduler */
$backupScheduler = WPStaging::make(BackupScheduler::class);
$cronStatus      = $backupScheduler->checkCronStatus();
$cronMessage     = $backupScheduler->getCronMessage();

// Render cron warning notice using modern callout design
require WPSTG_VIEWS_DIR . 'notices/cron-warning-notice.php';

// Will show a locked message if the process is locked
require WPSTG_VIEWS_DIR . 'job/locked.php';

$disabledPropertyCreateBackup = $isLocked ? 'disabled' : '';

?>

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
        '<span style="font-weight: bold">' . esc_html__('Download WP Staging Restore and Extraction Tool:', 'wp-staging') . '</span>',
        '<a href="https://wp-staging.com/docs/wp-staging-restore/">' . esc_html__('Read More or Upgrade to Pro', 'wp-staging') . '</a>'
    );
    ?>
</div>

<!-- Navigation Bar -->
<div id="wpstg-step-1" class="wpstg-flex wpstg-flex-wrap wpstg-items-center wpstg-gap-3 wpstg-mb-6">
    <!-- Primary: Create Backup -->
    <button
        id="wpstg-new-backup"
        class="wpstg-btn wpstg-btn-lg wpstg-btn-primary wpstg-next-step-link"
        <?php echo esc_attr($disabledPropertyCreateBackup); ?>
    >
        <svg class="wpstg-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <?php esc_html_e('Create Backup', 'wp-staging'); ?>
    </button>

    <!-- Secondary: Upload Backup -->
    <button
        type="button"
        id="wpstg-upload-backup"
        class="wpstg-btn wpstg-btn-lg wpstg-btn-secondary"
    >
        <svg class="wpstg-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
        </svg>
        <?php esc_html_e('Upload Backup', 'wp-staging'); ?>
    </button>

    <!-- Secondary: Manage Plans -->
    <button
        id="wpstg-manage-backup-schedules"
        class="wpstg-btn wpstg-btn-lg wpstg-btn-secondary"
    >
        <svg class="wpstg-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
        <?php esc_html_e('Manage Plans', 'wp-staging'); ?>
    </button>

    <!-- Remote Sync: Sync from Remote Site (Pro Upsell) -->
    <div class="wpstg-relative wpstg--tooltip">
        <button
            id="wpstg-remote-sync"
            class="wpstg-btn wpstg-btn-lg wpstg-btn-tint wpstg-opacity-60 wpstg-cursor-not-allowed"
            disabled
        >
            <svg class="wpstg-btn-icon" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" x2="12" y1="15" y2="3"/>
            </svg>
            <?php esc_html_e('Sync from Remote Site', 'wp-staging'); ?>
            <span class="wpstg-badge wpstg-badge-blue">
                <?php esc_html_e('Pro', 'wp-staging'); ?>
            </span>
        </button>
        <span class="wpstg--tooltiptext wpstg-remote-sync-tooltip" style="width: 350px; line-height: 1.5; margin-top: -1px; white-space: normal;">
            <span class="wpstg-remote-sync-tooltip-thumb"
                  role="button" tabindex="0"
                  aria-label="<?php echo esc_attr__('Play demo video', 'wp-staging'); ?>"
                  data-vimeo-id="1162852843"
                  data-img="<?php echo esc_url($urlAssets); ?>img/thumbnail-small-dark.webp">
                <img class="wpstg-remote-sync-tooltip-thumb-img"
                     src="<?php echo esc_url($urlAssets); ?>img/thumbnail-small-dark.webp"
                     alt="<?php echo esc_attr__('Remote Sync demo', 'wp-staging'); ?>"
                     width="320" height="180" loading="lazy" />
                <span class="wpstg-remote-sync-tooltip-duration">46s</span>
            </span>
            <span class="wpstg-remote-sync-tooltip-cta">
                <?php esc_html_e('Watch Remote Sync demo', 'wp-staging'); ?>
            </span>
            <span class="wpstg-remote-sync-tooltip-privacy">
                <?php esc_html_e('Video hosted on Vimeo. Loaded only after click.', 'wp-staging'); ?>
            </span>
        </span>
    </div>
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
                <li><?php esc_html_e('Searching for existing backups...', 'wp-staging'); ?></li>
            </ul>
        </div>
    </div>
</div>

<?php
include(WPSTG_VIEWS_DIR . 'job/modal/process.php');
include(WPSTG_VIEWS_DIR . 'job/modal/success.php');
include(WPSTG_VIEWS_DIR . 'otp/overlay.php');
include(WPSTG_VIEWS_DIR . 'backup/modal/partials/backup-success.php');
include(__DIR__ . '/modal/backup.php');
include(__DIR__ . '/modal/download-modal.php');
include(__DIR__ . '/modal/upload.php');
include(__DIR__ . '/modal/manage-schedules.php');
include(__DIR__ . '/modal/remote-upload.php');
include(__DIR__ . '/modal/edit-schedule-modal.php');
include(__DIR__ . '/modal/restore.php');
include(__DIR__ . '/restore-wait.php');
?>
<div id="wpstg-delete-confirmation"></div>
