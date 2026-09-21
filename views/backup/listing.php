<?php

use WPStaging\Backup\BackupDownload;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Language\Language;
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

 
$backupScheduler = WPStaging::make(BackupScheduler::class);
$cronStatus      = $backupScheduler->checkCronStatus();

 
require WPSTG_VIEWS_DIR . 'notices/cron-warning-notice.php';

 
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
        sprintf(
            /* translators: 1: link to the restore documentation, 2: link to the Pro upgrade page */
            esc_html__('%1$s or %2$s', 'wp-staging'),
            '<a href="' . esc_url('https://wp-staging.com/docs/wp-staging-restore/') . '" target="_blank" rel="noopener">' . esc_html__('Read More', 'wp-staging') . '</a>',
            '<a href="' . esc_url(Language::getUpgradeUrl('backup_restore_tool')) . '" target="_blank" rel="noopener">' . esc_html__('Upgrade to Pro', 'wp-staging') . '</a>'
        )
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

    <!-- Remote Sync: Sync with Remote Site (Pro Upsell) -->
    <div class="wpstg-relative wpstg--tooltip">
        <button
            id="wpstg-remote-sync"
            class="wpstg-btn wpstg-btn-lg wpstg-btn-tint wpstg-opacity-60 wpstg-cursor-not-allowed"
            disabled
        >
            <?php $this->getAssets()->renderSvg('remote-sync', 'wpstg-btn-icon'); ?>
            <?php esc_html_e('Sync with Remote Site', 'wp-staging'); ?>
            <span class="wpstg-badge wpstg-badge-blue">
                <?php esc_html_e('Pro', 'wp-staging'); ?>
            </span>
        </button>
        <span class="wpstg--tooltiptext wpstg-remote-sync-tooltip" style="width: 350px; line-height: 1.5; margin-top: -1px; white-space: normal;">
            <?php require WPSTG_VIEWS_DIR . 'backup/_partials/remote-sync-tooltip-thumb.php'; ?>
            <span class="wpstg-remote-sync-tooltip-cta">
                <?php esc_html_e('Watch Remote Sync demo', 'wp-staging'); ?>
            </span>
            <span class="wpstg-remote-sync-tooltip-privacy">
                <?php esc_html_e('Video hosted on Vimeo. Loaded only after click.', 'wp-staging'); ?>
            </span>
        </span>
    </div>
</div>

<?php include(__DIR__ . '/partials/scheduled-backups-section.php'); ?>
<div class="wpstg-backup-listing-container">
    <?php require WPSTG_VIEWS_DIR . 'backup/_partials/local-backups-list.php'; ?>
</div>

<?php
require WPSTG_VIEWS_DIR . 'backup/_partials/listing-modals.php';
include(WPSTG_VIEWS_DIR . 'notices/review-prompt-handlers.php');
?>
<div id="wpstg-delete-confirmation"></div>
