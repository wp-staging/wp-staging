<?php

/**
 * @var \WPStaging\Framework\Adapter\Directory $directories
 * @var string $urlAssets
 * @var bool $isProVersion
 * @var bool $hasSchedule
 * @var bool $isPersonalLicense
 * @var string $licenseType
 */

use WPStaging\Backup\BackupScheduler;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\Times;
use WPStaging\Basic\Ajax\ProCronsCleaner;
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\Facades\Hooks;

$timeFormatOption = get_option('time_format');

 
$time = WPStaging::make(Times::class);

$recurInterval   = (defined('WPSTG_IS_DEV') && WPSTG_IS_DEV) ? 'PT1M' : 'PT15M';
$recurInterval   = Hooks::applyFilters(BackupScheduler::FILTER_SCHEDULES_BACKUP_INTERVAL, $recurInterval);
$recurrenceTimes = $time->range('midnight', 'tomorrow - 1 minutes', $recurInterval);

$disabledProAttribute = $isProVersion ? '' : ' disabled';

$disabledClass = !$isProVersion ? 'wpstg-storage-settings-disabled' : '';

$haveProCrons = WPStaging::make(ProCronsCleaner::class)->haveProCrons();

$cronMessage = $haveProCrons ? __('There are backup plans created with WP Staging Pro. Delete them first to create a backup plan with the free version of WP Staging. ', 'wp-staging') :
    __('A backup is created every day at 12:00 noon!', 'wp-staging');

$storagesPrefix = 'storage-';
$isMultisite    = is_multisite();
$isMainSite     = $isMultisite && is_main_site();
$isSuperAdmin   = $isMultisite && is_super_admin();
$isEntireScope  = $isMultisite && $isSuperAdmin && $isMainSite;
$networkSiteCount           = $isMultisite ? (int)get_blog_count() : 1;
$selectedBackupType         = $isMultisite ? ($isEntireScope ? BackupMetadata::BACKUP_TYPE_MULTISITE : BackupMetadata::BACKUP_TYPE_NETWORK_SUBSITE) : BackupMetadata::BACKUP_TYPE_SINGLE;
$networkCurrentLabel        = $isMainSite ? __('Main site only', 'wp-staging') : __('Current subsite only', 'wp-staging');
$networkCurrentDesc         = $isMainSite ? __('Only the primary site.', 'wp-staging') : __('Only the active subsite.', 'wp-staging');
$networkCurrentSummaryDesc  = $isMainSite ? __('Primary site only', 'wp-staging') : __('Subsite only', 'wp-staging');
$networkCurrentFullBackupDesc = $isMainSite ? __('Everything needed to restore the main site.', 'wp-staging') : __('Everything needed to restore this subsite.', 'wp-staging');
$networkCurrentIncludedDesc   = $isMainSite ? __('Main site database, plugins, themes, media, and site files.', 'wp-staging') : __('Subsite database, media, and site files.', 'wp-staging');
$networkSiteCountLabel = sprintf(
    /* translators: %d: number of sites in the network */
    _n('%d site included', '%d sites included', $networkSiteCount, 'wp-staging'),
    $networkSiteCount
);
$backupDateLabel       = wp_date('M j, Y');
$networkBackupName     = sprintf(
    /* translators: 1: backup scope label, 2: current date */
    __('%1$s · %2$s', 'wp-staging'),
    __('Network Backup', 'wp-staging'),
    $backupDateLabel
);
$currentSiteBackupName = sprintf(
    /* translators: 1: backup scope label, 2: current date */
    __('%1$s · %2$s', 'wp-staging'),
    $isMainSite ? __('Main Site Backup', 'wp-staging') : __('Subsite Backup', 'wp-staging'),
    $backupDateLabel
);
if ($isMultisite) {
    $defaultBackupName = $isEntireScope ? $networkBackupName : $currentSiteBackupName;
} else {
    $defaultBackupName = sprintf(
        /* translators: %s: current date */
        __('Backup — %s', 'wp-staging'),
        $backupDateLabel
    );
}

?>
<div id="wpstg--modal--backup--new" data-confirmButtonText="<?php esc_attr_e('Start Backup', 'wp-staging'); ?>" style="display: none">
    <div class="wpstg-create-backup-modal wpstg-flex wpstg-max-h-[calc(100vh-7rem)] wpstg-w-full wpstg-flex-col wpstg-rounded-xl wpstg-bg-white wpstg-font-sans wpstg-text-gray-800 wpstg-shadow-2xl wpstg-ring-1 wpstg-ring-black/5 wpstg-tracking-normal dark:wpstg-bg-slate-900 dark:wpstg-text-slate-100 dark:wpstg-ring-white/10 max-[640px]:wpstg-max-h-[calc(100vh-24px)]">
        <header class="wpstg-box-border wpstg-flex wpstg-shrink-0 wpstg-items-start wpstg-gap-3 wpstg-border-0 wpstg-border-b wpstg-border-solid wpstg-border-gray-100 wpstg-px-6 wpstg-py-5 dark:wpstg-border-slate-800 max-[640px]:wpstg-px-5 max-[640px]:wpstg-py-4">
            <div class="wpstg-create-backup-modal__brand-icon wpstg-inline-flex wpstg-h-10 wpstg-w-10 wpstg-shrink-0 wpstg-items-center wpstg-justify-center wpstg-rounded-lg wpstg-bg-blue-50 wpstg-text-blue-600 dark:wpstg-bg-blue-500/15 dark:wpstg-text-blue-300" aria-hidden="true">
                <?php if ($isMultisite) : ?>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"></path></svg>
                <?php else : ?>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                <?php endif; ?>
            </div>
            <div class="wpstg-min-w-0 wpstg-flex-1">
                <div class="wpstg-flex wpstg-flex-wrap wpstg-items-center wpstg-gap-3">
                    <h3 class="wpstg-m-0 wpstg-text-[20px] wpstg-font-bold wpstg-leading-tight wpstg-tracking-normal wpstg-text-gray-900 dark:wpstg-text-slate-100"><?php esc_html_e('Create Backup', 'wp-staging'); ?></h3>
                    <?php if ($isMultisite) : ?>
                    <span class="wpstg-badge wpstg-bg-blue-50 dark:!wpstg-bg-blue-500/30 !wpstg-border-blue-400 dark:!wpstg-text-blue-300 dark:!wpstg-ring-blue-500/30 wpstg-text-blue-600 wpstg-ml-0"><?php esc_html_e('Multisite', 'wp-staging'); ?></span>
                    <?php endif; ?>
                </div>
                <p class="wpstg-m-0 wpstg-text-[13px] wpstg-leading-5 wpstg-tracking-normal wpstg-text-gray-400 dark:wpstg-text-gray-400">
                    <?php echo esc_html($isMultisite ? __('Choose the network scope, backup contents, schedule and storage location.', 'wp-staging') : __('Choose what to back up, where to store it, and when to run it.', 'wp-staging')); ?>
                </p>
            </div>
            <button
                type="button"
                id="wpstg--backup-modal--close"
                class="wpstg-inline-flex wpstg-h-8 wpstg-w-8 wpstg-shrink-0 wpstg-cursor-pointer wpstg-items-center wpstg-justify-center wpstg-rounded-md wpstg-border-0 wpstg-bg-transparent wpstg-p-0 wpstg-text-gray-400 hover:wpstg-bg-gray-100 hover:wpstg-text-gray-600 focus-visible:wpstg-outline-none focus-visible:wpstg-ring-2 focus-visible:wpstg-ring-blue-600 dark:wpstg-text-slate-400 dark:hover:wpstg-bg-slate-800 dark:hover:wpstg-text-slate-200"
                data-action="close-modal"
                aria-label="<?php echo esc_attr__('Close', 'wp-staging'); ?>"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18"/>
                    <path d="m6 6 12 12"/>
                </svg>
            </button>
        </header>

        <div class="wpstg-grid wpstg-min-h-0 wpstg-flex-1 wpstg-grid-cols-[minmax(0,1fr)_300px] max-[760px]:wpstg-block max-[760px]:wpstg-overflow-y-auto">
            <main class="wpstg-create-backup-modal__main wpstg-advanced-options-site wpstg-box-border wpstg-min-h-0 wpstg-overflow-y-auto wpstg-overflow-x-hidden wpstg-px-6 max-[760px]:wpstg-overflow-visible max-[640px]:wpstg-px-5">
                <?php if ($isMultisite) : ?>
                    <?php require_once WPSTG_VIEWS_DIR . 'pro/backup/modal/network-options.php'; ?>
                <?php endif; ?>
                <section class="wpstg-section">
                    <div class="wpstg-section-title">
                        <span><?php esc_html_e('Backup contents', 'wp-staging'); ?></span>
                    </div>
                    <div class="wpstg-grid-columns-2">
                        <label for="wpstg-backup-type--full" class="!wpstg-flex wpstg-justify-between wpstg-radio-card">
                            <div class="wpstg-min-w-0">
                                <div class="wpstg-flex wpstg-gap-2 wpstg-items-center">
                                    <svg class="wpstg-radio-card-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                    <strong class="wpstg-radio-card-heading wpstg-text-sm"><?php esc_html_e('Full Site Backup', 'wp-staging'); ?></strong>
                                </div>
                                <p class="wpstg-backup-type-full-description wpstg-form-description wpstg-whitespace-nowrap" data-summary-description="<?php echo esc_attr($isMultisite ? ($isEntireScope ? __('Database, plugins, themes, media, and site files.', 'wp-staging') : $networkCurrentIncludedDesc) : __('Database, plugins, themes, media, wp-content files, and other WP root folders.', 'wp-staging')); ?>">
                                    <?php echo esc_html($isMultisite ? ($isEntireScope ? __('Everything needed to restore the network.', 'wp-staging') : $networkCurrentFullBackupDesc) : __('Everything needed to restore this site.', 'wp-staging')); ?>
                                </p>
                            </div>
                            <input name="wpstg_backup_type_mode" id="wpstg-backup-type--full" type="radio" value="" class="wpstg-radio" checked />
                        </label>
                        <label for="wpstg-backup-type--custom" class="!wpstg-flex wpstg-justify-between wpstg-radio-card">
                            <div class="wpstg-min-w-0">
                                <div class="wpstg-flex wpstg-gap-2 wpstg-items-center">
                                    <svg class="wpstg-radio-card-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"></path></svg>
                                    <strong class="wpstg-radio-card-heading wpstg-text-sm"><?php esc_html_e('Custom Backup', 'wp-staging'); ?></strong>
                                </div>
                                <p class="wpstg-form-description"><?php esc_html_e('Choose exactly what to include.', 'wp-staging'); ?></p>
                            </div>
                            <input name="wpstg_backup_type_mode" id="wpstg-backup-type--custom" type="radio" value="" class="wpstg-radio" />
                        </label>
                    </div>

                    <div id="wpstg-advanced-backup-parts-selection" class="wpstg-create-backup-modal__custom-parts wpstg-content-panel wpstg-hidden wpstg-mt-3 wpstg-px-[15px] wpstg-py-4 dark:wpstg-bg-slate-800/70">
                        <div class="wpstg-mb-3 wpstg-text-[13px] wpstg-font-bold wpstg-leading-tight wpstg-text-gray-800 dark:wpstg-text-slate-100">
                            <strong><?php esc_html_e('Included Components', 'wp-staging'); ?></strong>
                        </div>
                        <div class="wpstg-grid-columns-2 wpstg-gap-x-7 wpstg-gap-y-3">
                            <div class="wpstg-contents">
                                <label class="wpstg-form-label">
                                    <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox" id="includeDatabaseInBackup" name="backup_database" value="true" data-summary-kind="component" data-summary-label="<?php esc_attr_e('Database', 'wp-staging'); ?>" checked>
                                    <span>
                                        <?php esc_html_e('Database', 'wp-staging'); ?>
                                        <span id="includeDatabaseInBackupSize"></span>
                                        <span class="wpstg--tooltip wpstg-backup-modal-tooltip">
                                            <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info" />
                                            <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                                                <?php
                                                printf(
                                                    esc_html__('This will backup all database tables starting with the prefix "%s". To backup a staging site, run the backup function again on the staging site', 'wp-staging'),
                                                    isset($GLOBALS['wpdb']->prefix) ? esc_html($GLOBALS['wpdb']->prefix) : 'wp_'
                                                );
                                                ?>
                                            </span>
                                        </span>
                                    </span>
                                </label>
                                <label class="wpstg-form-label">
                                    <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox" id="includePluginsInBackup" name="includedDirectories[]" value="<?php echo esc_attr($directories['plugins']); ?>" data-summary-kind="component" data-summary-label="<?php esc_attr_e('Plugins', 'wp-staging'); ?>" checked>
                                    <span><?php esc_html_e('Plugins', 'wp-staging'); ?> <span id="includePluginsInBackupSize"></span></span>
                                </label>
                                <label class="wpstg-form-label">
                                    <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox" id="includeThemesInBackup" name="includedDirectories[]" value="<?php echo esc_attr($directories['themes']); ?>" data-summary-kind="component" data-summary-label="<?php esc_attr_e('Themes', 'wp-staging'); ?>" checked>
                                    <span><?php esc_html_e('Themes', 'wp-staging'); ?> <span id="includeThemesInBackupSize"></span></span>
                                </label>
                                <label class="wpstg-form-label">
                                    <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox" id="includeMediaLibraryInBackup" name="includedDirectories[]" value="<?php echo esc_attr($directories['uploads']); ?>" data-summary-kind="component" data-summary-label="<?php esc_attr_e('Media Library', 'wp-staging'); ?>" checked>
                                    <span><?php esc_html_e('Media Library', 'wp-staging'); ?> <span id="includeMediaLibraryInBackupSize"></span></span>
                                </label>
                            </div>
                            <div class="wpstg-contents">
                                <label class="wpstg-form-label">
                                    <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox" id="includeOtherFilesInWpContent" name="includeOtherFilesInWpContent" value="true" data-summary-kind="component" data-summary-label="<?php esc_attr_e('Other wp-content files', 'wp-staging'); ?>" checked>
                                    <span>
                                        <?php esc_html_e('Other wp-content files', 'wp-staging'); ?>
                                        <span id="includeOtherFilesInWpContentSize"></span>
                                        <span class="wpstg--tooltip wpstg-backup-modal-tooltip">
                                            <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info" />
                                            <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                                                <?php esc_html_e('All files in folder wp-content that are not plugins, themes, mu-plugins and uploads. Recommended for full-site backups.', 'wp-staging'); ?>
                                            </span>
                                        </span>
                                    </span>
                                </label>
                                <label class="wpstg-form-label">
                                    <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox" id="includeMuPluginsInBackup" name="includedDirectories[]" value="<?php echo esc_attr($directories['muPlugins']); ?>" data-summary-kind="component" data-summary-label="<?php esc_attr_e('Must-Use Plugins', 'wp-staging'); ?>" checked>
                                    <span><?php esc_html_e('Must-Use Plugins', 'wp-staging'); ?> <span id="includeMuPluginsInBackupSize"></span></span>
                                </label>
                                <div class="wpstg-create-backup-modal__root-files-option">
                                    <div class="wpstg-create-backup-modal__root-files-row">
                                        <span class="wpstg--wproot-expand-folder">
                                            <img class="wpstg--dashicons wpstg-dashicons-14 wpstg--expand-folder-img" src="<?php echo esc_url($urlAssets); ?>svg/folder-expand-chevron.svg" alt="info" />
                                        </span>
                                        <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox" id="wpstgIncludeOtherFilesInWpRoot" name="includeOtherFilesInWpRoot" value="" data-summary-kind="component" data-summary-label="<?php esc_attr_e('Other WP Root Folders', 'wp-staging'); ?>" checked>
                                        <label class="wpstg-create-backup-modal__root-files-label wpstg-whitespace-nowrap" for="wpstgIncludeOtherFilesInWpRoot">
                                            <span id="wpstg-wproot-other-files-span" data-id="#wpstg-wproot-scanning-files">
                                                <?php esc_html_e('Other WP Root Folders', 'wp-staging'); ?>
                                            </span>
                                            <span id="wpstgIncludeOtherFilesInWpRootSize"></span> <!-- used to show the size of the files in the root folder -->
                                            <span class="wpstg--tooltip wpstg-wproot-tooltip">
                                                <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info" />
                                                <span class="wpstg--tooltiptext wpstg--tooltiptext-backups wpstg-whitespace-normal">
                                                    <?php echo sprintf(esc_html__('Only folders are backed up; files like %s are excluded and must be saved manually if needed. The following folders are also not included in the backup: %s, %s, and those containing WP Staging sites. To back up a staging site, open WP Staging on that site and create a backup directly from there.', 'wp-staging'), '<code>wp-config.php</code>', '<code>wp-admin</code>', '<code>wp-includes</code>'); ?>
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                    <fieldset class="wpstg-wproot-files-selection-section wpstg-wproot-files-selection wpstg-mt-1.5 wpstg-py-0 wpstg-pr-0 !wpstg-pl-0" id="wpstg-wproot-scanning-files">
                                        <?php require(WPSTG_VIEWS_DIR . 'backup/backup-files.php'); ?>
                                    </fieldset>
                                </div>
                            </div>
                            <input type="hidden" name="wpContentDir" value="<?php echo esc_attr($directories['wpContent']); ?>" />
                            <input type="hidden" name="wpStagingDir" value="<?php echo esc_attr($directories['wpStaging']); ?>" />
                            <?php unset($directories['wpContent'], $directories['wpStaging']) ?>
                            <input type="hidden" name="availableDirectories" value="<?php echo esc_attr(implode('|', array_map('strval', (array)$directories))); ?>"/>
                        </div>
                        <div id="backupUploadsWithoutDatabaseWarning" class="wpstg-create-backup-modal__warning wpstg-mt-2.5" style="display:none;">
                            <div class="wpstg-callout wpstg-callout-warning">
                                <svg xmlns="http://www.w3.org/2000/svg" class="wpstg-h-5 wpstg-w-5 wpstg-flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                                <div>
                                    <?php esc_html_e('When backing up the Media Library without the Database, the attachments will be migrated but won\'t show up in the media library after restore.', 'wp-staging'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$isMultisite) : ?>
                        <input type="hidden" name="backupType" value="<?php echo esc_attr(BackupMetadata::BACKUP_TYPE_SINGLE); ?>" />
                    <?php endif; ?>

                    <?php require_once WPSTG_VIEWS_DIR . 'backup/modal/partials/backup-filters-notice.php'; ?>
                </section>

                <section class="wpstg-section">
                    <div id="wpstg-backup-times-header" class="wpstg-section-title">
                        <span><?php esc_html_e('Schedule', 'wp-staging'); ?></span>
                    </div>
                    <div id="wpstg-backup-times-section" class="wpstg-create-backup-modal__schedule-section">
                        <div class="wpstg-backup-scheduling-options">
                            <?php $recurringDisabled = ($hasSchedule && !$isProVersion); ?>
                            <div class="wpstg-schedule-mode-group wpstg-grid-columns-2" role="radiogroup" aria-label="<?php esc_attr_e('Backup schedule mode', 'wp-staging'); ?>">
                                <label for="wpstg-schedule-mode-input--one-time"
                                    class="!wpstg-flex wpstg-justify-between wpstg-radio-card !wpstg-m-0 wpstg-schedule-mode-card"
                                    data-mode="one_time"
                                    id="wpstg-schedule-mode--one-time">
                                    <div class="wpstg-min-w-0">
                                        <div class="wpstg-flex wpstg-gap-2 wpstg-items-center">
                                            <svg class="wpstg-radio-card-icon wpstg-h-4 wpstg-w-4" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <polygon points="5 3 19 12 5 21 5 3"/>
                                            </svg>
                                            <strong class="wpstg-schedule-mode-card-title wpstg-radio-card-heading wpstg-text-sm"><?php esc_html_e('Run once now', 'wp-staging'); ?></strong>
                                        </div>
                                        <p class="wpstg-form-description"><?php esc_html_e('Create a backup immediately.', 'wp-staging'); ?></p>
                                    </div>
                                    <input name="wpstg_schedule_mode" id="wpstg-schedule-mode-input--one-time" type="radio" value="one_time" class="wpstg-radio" checked />
                                </label>
                                <label for="wpstg-schedule-mode-input--recurring"
                                    class="!wpstg-flex wpstg-justify-between wpstg-radio-card !wpstg-m-0 wpstg-schedule-mode-card <?php echo $recurringDisabled ? 'wpstg-radio-card--disabled' : ''; ?>"
                                    data-mode="recurring"
                                    <?php echo $recurringDisabled ? 'aria-disabled="true"' : ''; ?>
                                    id="wpstg-schedule-mode--recurring">
                                    <div class="wpstg-min-w-0">
                                        <div class="wpstg-flex wpstg-gap-2 wpstg-items-center">
                                            <svg class="wpstg-radio-card-icon wpstg-h-4 wpstg-w-4" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <polyline points="23 4 23 10 17 10"/>
                                                <polyline points="1 20 1 14 7 14"/>
                                                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                                            </svg>
                                            <strong class="wpstg-schedule-mode-card-title wpstg-radio-card-heading wpstg-text-sm"><?php esc_html_e('Schedule recurring', 'wp-staging'); ?></strong>
                                        </div>
                                        <p class="wpstg-form-description"><?php esc_html_e('Run automatically on a schedule.', 'wp-staging'); ?></p>
                                    </div>
                                    <input name="wpstg_schedule_mode" id="wpstg-schedule-mode-input--recurring" type="radio" value="recurring" class="wpstg-radio" <?php disabled($recurringDisabled); ?> />
                                </label>
                            </div>
                            <div class="wpstg-mt-4 wpstg-upgrade-callout wpstg-basic-schedule-notice <?php echo $isProVersion ? 'wpstg-is-pro' : 'wpstg-is-basic'; ?>" style="display: <?php echo ($hasSchedule && !$isProVersion) ? 'block' : 'none'; ?>">
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
                                            <?php esc_html_e('Free schedules use default settings', 'wp-staging'); ?>
                                            <span class="wpstg-badge-pro"><?php esc_html_e('Pro', 'wp-staging'); ?></span>
                                        </div>
                                        <p class="wpstg-upgrade-callout-description">
                                            <?php echo esc_html($cronMessage); ?>
                                            <?php esc_html_e('Upgrade to Pro to create unlimited backup plans, choose the start time, and upload scheduled backups to cloud storage.', 'wp-staging'); ?>
                                        </p>
                                        <div class="wpstg-upgrade-callout-actions">
                                            <a href="<?php echo esc_url(Language::getUpgradeUrl('backup_schedule')); ?>" target="_blank" rel="noopener noreferrer" class="wpstg-btn wpstg-btn-md wpstg-btn-primary"><?php esc_html_e('Upgrade to Pro', 'wp-staging'); ?></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php require_once WPSTG_VIEWS_DIR . 'backup/modal/backup-scheduling-options.php'; ?>
                        </div>
                    </div>
                </section>

                <section class="wpstg-section">
                    <div id="wpstg-backup-storages-header" class="wpstg-section-title">
                        <span><?php esc_html_e('Storage location', 'wp-staging'); ?></span>
                    </div>
                    <div id="wpstg-storages-section">
                        <?php require WPSTG_VIEWS_DIR . 'backup/modal/backup-storages.php'; ?>
                    </div>
                </section>

                <section class="wpstg-section">
                    <div id="wpstg-backup-advance-section-header" class="wpstg-create-backup-modal__advanced-toggle wpstg-flex wpstg-cursor-pointer wpstg-items-center wpstg-justify-between wpstg-gap-3 wpstg-text-[11px] wpstg-font-extrabold wpstg-leading-[1.2] wpstg-tracking-[0.12em]" data-id="#wpstg-backup-advance-section">
                        <span><?php esc_html_e('ADVANCED', 'wp-staging'); ?></span>
                        <button type="button" class="wpstg-inline-flex wpstg-cursor-pointer wpstg-items-center wpstg-gap-[5px] wpstg-border-0 wpstg-bg-transparent wpstg-p-0 wpstg-text-xs wpstg-font-bold wpstg-tracking-normal wpstg-text-royal-600 focus-visible:wpstg-outline-none focus-visible:wpstg-ring-2 focus-visible:wpstg-ring-royal-600/30 dark:wpstg-text-blue-400 dark:hover:wpstg-text-blue-300 dark:focus-visible:wpstg-ring-blue-500/40" data-show-label="<?php esc_attr_e('Show options', 'wp-staging'); ?>" data-hide-label="<?php esc_attr_e('Hide options', 'wp-staging'); ?>" aria-expanded="false" aria-controls="wpstg-backup-advance-section">
                            <span><?php esc_html_e('Show options', 'wp-staging'); ?></span>
                            <svg class="wpstg-create-backup-modal__advanced-chevron wpstg-shrink-0 wpstg-text-current wpstg-transition-transform" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                    </div>
                    <div class="wpstg-create-backup-modal__advanced-summary wpstg-mt-[17px] wpstg-text-xs wpstg-leading-[1.4] wpstg-text-slate-450 dark:wpstg-text-slate-500">
                        <?php esc_html_e('Exclusions · recommended defaults', 'wp-staging'); ?>
                    </div>
                    <div class="wpstg-backup-options-section hidden" id="wpstg-backup-advance-section">
                        <div class="!wpstg-mt-3">
                            <div>
                                <label for="wpstgSmartExclusion" class="wpstg-form-label wpstg-with-tooltip" id="wpstg-add-exclusions-label">
                                    <span class="wpstg-inline-flex wpstg-items-center wpstg-gap-1">
                                        <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox <?php echo $isProVersion ? 'wpstg-is-pro' : 'wpstg-is-basic';?>" id="wpstgSmartExclusion" name="smartExclusion" <?php echo !$isProVersion ? "disabled" : "";?> value="" onChange='WPStaging.handleDisplayDependencies(this)'>
                                    </span>
                                    <span class="wpstg-inline-flex wpstg-min-w-0 wpstg-flex-wrap wpstg-items-center wpstg-gap-1.5">
                                        <span class="<?php echo esc_attr($disabledClass); ?> wpstg-add-exclusions-label-inner" id="wpstg-add-exclusions-span">
                                            <?php esc_html_e('Add Exclusions', 'wp-staging'); ?>
                                        </span>
                                        <span class="wpstg--tooltip wpstg-add-exclusions-tooltip">
                                            <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info" />
                                            <span class="wpstg--tooltiptext wpstg-left-4">
                                                <?php esc_html_e('To keep backups fast and efficient, we automatically exclude files over 200MB and system files like .wpstg, .gz, and .tmp', 'wp-staging'); ?>
                                                <br /><?php printf(esc_html__('Want to change this? %s', 'wp-staging'), '<a href="https://wp-staging.com/docs/actions-and-filters/#Exclude_a_file_extension_from_backup" target="_blank" rel="noopener noreferrer">' . esc_html__('Learn how to customize exclusions.', 'wp-staging') . '</a>'); ?>
                                            </span>
                                        </span>
                                        <?php if (!$isProVersion) : ?>
                                            <a href="<?php echo esc_url(Language::getUpgradeUrl('backup_exclude')); ?>" target="_blank" rel="noopener noreferrer" class="wpstg-pro-feature-link wpstg-inline-flex wpstg-items-center"><span class="wpstg-badge-pro"><?php esc_html_e('Upgrade', 'wp-staging'); ?></span></a>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            </div>
                            <?php require_once WPSTG_VIEWS_DIR . 'backup/modal/advanced-exclude-options.php'; ?>
                        </div>
                    </div>
                </section>
            </main>

            <aside class="wpstg-create-backup-modal__summary wpstg-box-border wpstg-min-h-0 wpstg-overflow-y-auto wpstg-bg-slate-50 dark:wpstg-bg-slate-750/20 wpstg-px-5 wpstg-py-5 max-[760px]:wpstg-w-full max-[760px]:wpstg-overflow-visible max-[760px]:wpstg-border-l-0 max-[760px]:wpstg-border-t max-[640px]:wpstg-px-5" aria-label="<?php esc_attr_e('Backup Summary', 'wp-staging'); ?>">
                <div class="wpstg-create-backup-modal__summary-title wpstg-mb-4 wpstg-flex wpstg-items-center wpstg-gap-2 wpstg-text-gray-800 dark:wpstg-text-slate-100">
                    <svg class="wpstg-h-4 wpstg-w-4 wpstg-text-blue-600 dark:wpstg-text-blue-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <path d="M3.27 6.96 12 12.01l8.73-5.05"/>
                        <path d="M12 22.08V12"/>
                    </svg>
                    <strong class="wpstg-text-[13px] wpstg-font-bold wpstg-leading-tight wpstg-text-inherit"><?php esc_html_e('Backup Summary', 'wp-staging'); ?></strong>
                </div>
                <?php if ($isMultisite) : ?>
                <div class="wpstg-create-backup-modal__summary-block wpstg-summary-block">
                    <span class="wpstg-create-backup-modal__summary-label wpstg-summary-label"><?php esc_html_e('SCOPE', 'wp-staging'); ?></span>
                    <div id="wpstg-summary-scope-card" class="wpstg-flex wpstg-items-center wpstg-gap-3 wpstg-rounded-lg wpstg-border wpstg-border-solid wpstg-border-blue-100 wpstg-bg-blue-50 wpstg-px-3.5 wpstg-py-3 dark:wpstg-border-blue-700/50 dark:wpstg-bg-blue-950/35">
                        <span class="wpstg-inline-flex wpstg-h-6 wpstg-w-6 wpstg-shrink-0 wpstg-items-center wpstg-justify-center wpstg-text-blue-600 dark:wpstg-text-blue-300" aria-hidden="true">
                            <svg class="wpstg-summary-scope-icon wpstg-summary-scope-icon--network <?php echo $isEntireScope ? '' : 'wpstg-hidden'; ?> wpstg-h-5 wpstg-w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
                                <path d="M2 12h20"/>
                            </svg>
                            <svg class="wpstg-summary-scope-icon wpstg-summary-scope-icon--site <?php echo $isEntireScope ? 'wpstg-hidden' : ''; ?> wpstg-h-5 wpstg-w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 10.5 12 3l9 7.5"/>
                                <path d="M5 10v10h14V10"/>
                                <path d="M9 20v-6h6v6"/>
                            </svg>
                        </span>
                        <span class="wpstg-min-w-0">
                            <strong id="wpstg-summary-scope-title" class="wpstg-block wpstg-text-[13px] wpstg-font-semibold wpstg-leading-tight wpstg-text-gray-800 dark:wpstg-text-slate-100"><?php echo esc_html($isEntireScope ? __('Entire network', 'wp-staging') : $networkCurrentLabel); ?></strong>
                            <span id="wpstg-summary-scope-description" class="wpstg-mt-1 wpstg-block wpstg-text-[12px] wpstg-leading-snug wpstg-text-slate-500 dark:wpstg-text-slate-400"><?php echo esc_html($isEntireScope ? $networkSiteCountLabel : $networkCurrentSummaryDesc); ?></span>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
                <div class="wpstg-create-backup-modal__summary-block wpstg-summary-block">
                    <span class="wpstg-create-backup-modal__summary-label wpstg-summary-label"><?php esc_html_e('INCLUDED', 'wp-staging'); ?></span>
                    <ul id="wpstg-backup-summary-included" class="wpstg-create-backup-modal__included-list wpstg-m-0 wpstg-grid wpstg-list-none wpstg-gap-2 wpstg-p-0 [&_li]:wpstg-m-0 [&_li]:wpstg-text-gray-600 dark:[&_li]:wpstg-text-slate-300" aria-live="polite"></ul>
                    <button type="button" id="wpstg-backup-summary-more" class="wpstg-create-backup-modal__included-more wpstg-mb-0 wpstg-mt-2 wpstg-cursor-pointer wpstg-border-0 wpstg-bg-transparent wpstg-p-0 wpstg-text-[12px] wpstg-font-semibold wpstg-leading-tight wpstg-text-blue-600 hover:wpstg-underline focus-visible:wpstg-outline-none focus-visible:wpstg-ring-2 focus-visible:wpstg-ring-blue-600 dark:wpstg-text-blue-300" data-more-label="<?php esc_attr_e('more', 'wp-staging'); ?>" data-show-less-label="<?php esc_attr_e('Show less', 'wp-staging'); ?>" hidden></button>
                </div>
                <div id="wpstg-create-backup-modal__exclusion-summary" class="wpstg-create-backup-modal__summary-block wpstg-summary-block wpstg-hidden">
                    <span class="wpstg-create-backup-modal__summary-label wpstg-summary-label"><?php esc_html_e('EXCLUSIONS', 'wp-staging'); ?></span>
                    <p id="wpstg-backup-summary-exclusions" class="wpstg-m-0 wpstg-text-[12.5px] wpstg-leading-snug wpstg-text-amber-700 dark:wpstg-text-amber-300"><?php esc_html_e('No exclusions applied', 'wp-staging'); ?></p>
                </div>
                <div class="wpstg-create-backup-modal__summary-divider wpstg-summary-divider"></div>
                <div class="wpstg-create-backup-modal__summary-block wpstg-summary-block">
                    <span class="wpstg-create-backup-modal__summary-label wpstg-summary-label"><?php esc_html_e('ESTIMATED SIZE', 'wp-staging'); ?></span>
                    <div class="wpstg-backup-size-row wpstg-m-0 wpstg-block wpstg-w-full wpstg-text-left" data-state="idle">
                        <div class="wpstg-flex wpstg-flex-wrap wpstg-items-center wpstg-gap-1.5 wpstg-text-[12.5px] wpstg-leading-snug">
                            <span class="wpstg-create-backup-modal__size-placeholder wpstg-text-slate-500 dark:wpstg-text-slate-400"><?php esc_html_e('Not calculated yet', 'wp-staging'); ?></span>
                            <span class="wpstg-create-backup-modal__size-placeholder wpstg-text-slate-500 dark:wpstg-text-slate-500" aria-hidden="true">&middot;</span>
                            <button type="button"
                                    id="wpstg-calculate-backup-size"
                                    class="wpstg-backup-size-main-btn wpstg-inline-flex wpstg-min-h-0 wpstg-cursor-pointer wpstg-items-center wpstg-justify-center wpstg-border-0 wpstg-bg-transparent wpstg-p-0 wpstg-text-[12.5px] wpstg-font-semibold wpstg-leading-snug !wpstg-text-blue-600 wpstg-shadow-none hover:wpstg-underline focus-visible:wpstg-outline-none focus-visible:wpstg-ring-2 focus-visible:wpstg-ring-blue-600 disabled:wpstg-cursor-wait disabled:wpstg-opacity-70 dark:wpstg-text-blue-300"
                                    data-state="idle"
                                    data-label-idle="<?php esc_attr_e('Calculate size', 'wp-staging'); ?>"
                                    data-label-recalculate="<?php esc_attr_e('Recalculate', 'wp-staging'); ?>"
                                    aria-label="<?php esc_attr_e('Calculate size', 'wp-staging'); ?>">
                                <span class="wpstg-backup-size-btn-label">
                                    <?php esc_html_e('Calculate size', 'wp-staging'); ?>
                                </span>
                            </button>
                        </div>

                        <div id="wpstg-backup-size-card" class="wpstg-backup-size-card wpstg-block wpstg-w-full wpstg-box-border wpstg-bg-transparent wpstg-p-0 wpstg-text-left" role="group" aria-labelledby="wpstg-backup-size-card-label" hidden>
                            <div class="wpstg-backup-size-card-main wpstg-min-w-0">
                                <strong class="wpstg-block wpstg-text-[18px] wpstg-font-extrabold wpstg-leading-[1.12] wpstg-text-gray-900 dark:wpstg-text-slate-100">
                                    <span aria-hidden="true">~</span><span id="wpstg-total-estimated-backup-size" class="wpstg-backup-size-card-value">—</span><span class="wpstg-text-sm wpstg-font-bold wpstg-text-slate-550 dark:wpstg-text-slate-400"> <?php esc_html_e('total', 'wp-staging'); ?></span>
                                </strong>
                                <div class="wpstg-backup-size-card-sub wpstg-mt-1.5 wpstg-flex wpstg-flex-wrap wpstg-items-center wpstg-gap-1.5 wpstg-text-xs wpstg-leading-[1.3] wpstg-text-slate-550 dark:wpstg-text-slate-400">
                                    <span id="wpstg-backup-size-card-label" class="wpstg-backup-size-card-caption"><?php esc_html_e('Estimated size', 'wp-staging'); ?></span>
                                    <span class="wpstg-backup-size-card-dot wpstg-backup-size-card-stale-dot wpstg-text-slate-550/60 dark:wpstg-text-slate-500" aria-hidden="true" hidden>&middot;</span>
                                    <span class="wpstg-backup-size-card-stale wpstg-font-medium wpstg-text-amber-600 dark:wpstg-text-amber-300" hidden><?php esc_html_e('Outdated', 'wp-staging'); ?></span>
                                </div>
                            </div>
                            <button type="button"
                                    id="wpstg-recalculate-backup-size"
                                    class="wpstg-backup-size-recalculate-link wpstg-mt-[9px] wpstg-inline-flex wpstg-cursor-pointer wpstg-items-center wpstg-gap-1 wpstg-border-0 wpstg-bg-transparent wpstg-p-0 wpstg-text-xs wpstg-font-bold wpstg-leading-[1.2] !wpstg-text-royal-600 hover:wpstg-text-royal-700 focus-visible:wpstg-outline-none focus-visible:wpstg-ring-2 focus-visible:wpstg-ring-royal-600/30 dark:wpstg-text-blue-400 dark:hover:wpstg-text-blue-300 dark:focus-visible:wpstg-ring-blue-500/40"
                                    aria-label="<?php esc_attr_e('Recalculate backup size', 'wp-staging'); ?>">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="23 4 23 10 17 10"/>
                                    <polyline points="1 20 1 14 7 14"/>
                                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                                </svg>
                                <?php esc_html_e('Recalculate', 'wp-staging'); ?>
                            </button>
                        </div>
                        <span id="wpstg-backup-size-caption" class="wpstg-backup-size-caption wpstg-text-[13px] wpstg-leading-[1.4] wpstg-text-slate-550 empty:wpstg-hidden dark:wpstg-text-slate-400" aria-live="polite"></span>
                    </div>
                </div>
                <div class="wpstg-create-backup-modal__summary-divider wpstg-summary-divider"></div>
                <dl class="wpstg-create-backup-modal__summary-meta wpstg-summary-meta">
                    <div class="wpstg-summary-meta-row">
                        <dt class="wpstg-summary-meta-term">
                            <svg class="wpstg-h-[13px] wpstg-w-[13px] wpstg-shrink-0 wpstg-text-slate-500 dark:wpstg-text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 6v6l4 2"/>
                            </svg>
                            <?php esc_html_e('Schedule', 'wp-staging'); ?>
                        </dt>
                        <dd id="wpstg-summary-schedule" class="wpstg-summary-meta-value"><?php esc_html_e('Run once now', 'wp-staging'); ?></dd>
                    </div>
                    <div class="wpstg-summary-meta-row">
                        <dt class="wpstg-summary-meta-term">
                            <svg class="wpstg-h-[13px] wpstg-w-[13px] wpstg-shrink-0 wpstg-text-slate-500 dark:wpstg-text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M22 12H2"/>
                                <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>
                            </svg>
                            <?php esc_html_e('Storage', 'wp-staging'); ?>
                        </dt>
                        <dd id="wpstg-summary-storage" class="wpstg-summary-meta-value"><?php esc_html_e('Local Storage', 'wp-staging'); ?></dd>
                    </div>
                    <div class="wpstg-summary-meta-row">
                        <dt class="wpstg-summary-meta-term">
                            <svg class="wpstg-h-[13px] wpstg-w-[13px] wpstg-shrink-0 wpstg-text-slate-500 dark:wpstg-text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <path d="M14 2v6h6"/>
                            </svg>
                            <?php esc_html_e('Name', 'wp-staging'); ?>
                        </dt>
                        <div class="wpstg-flex wpstg-items-center wpstg-gap-3">
                            <dd id="wpstg-summary-name" class="wpstg-summary-meta-value wpstg-max-w-[150px]"><?php esc_html_e('Auto', 'wp-staging'); ?></dd>
                            <button type="button" id="wpstg-backup-name-edit-btn" class="wpstg-cursor-pointer wpstg-text-slate-400 wpstg-border-0 wpstg-bg-transparent wpstg-p-0 hover:wpstg-text-royal-600 focus-visible:wpstg-outline-none focus-visible:wpstg-ring-2 focus-visible:wpstg-ring-royal-600/30 dark:wpstg-text-slate-400 dark:hover:wpstg-text-blue-300 dark:focus-visible:wpstg-ring-blue-500/40">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil-icon lucide-pencil"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                            </button>
                        </div>
                    </div>
                    <input id="wpstg-backup-name-input" name="backup_name" class="wpstg-input wpstg-input-md wpstg-create-backup-modal__name-input !wpstg-mt-2.5 !wpstg-max-w-full dark:!wpstg-border-slate-600 dark:!wpstg-text-slate-100 dark:placeholder:!wpstg-text-slate-500 focus-visible:!wpstg-border-royal-600 focus-visible:wpstg-outline focus-visible:wpstg-outline-2 focus-visible:wpstg-outline-offset-2 focus-visible:wpstg-outline-royal-600/35 dark:focus-visible:wpstg-outline-blue-500/40" value="<?php echo esc_attr($defaultBackupName); ?>" placeholder="<?php echo esc_attr($defaultBackupName); ?>" data-auto-label="<?php esc_attr_e('Auto', 'wp-staging'); ?>" data-name-edited="false" hidden>

                </dl>
            </aside>
        </div>
        <footer class="wpstg-backup-modal-footer wpstg-create-backup-modal__footer wpstg-box-border wpstg-flex wpstg-shrink-0 wpstg-items-center wpstg-justify-between wpstg-gap-3 wpstg-border-0 wpstg-border-t !wpstg-rounded-t-unset wpstg-border-solid wpstg-border-gray-100 wpstg-px-6 wpstg-py-4 dark:wpstg-border-slate-800 max-[640px]:wpstg-flex-col max-[640px]:wpstg-items-stretch max-[640px]:wpstg-px-5">
            <div>
                <label class="wpstg-create-backup-modal__background-run !wpstg-m-0 !wpstg-inline-flex wpstg-cursor-pointer wpstg-items-center wpstg-gap-2.5 wpstg-text-[13.5px] !wpstg-leading-tight wpstg-text-gray-700 dark:wpstg-text-slate-200 max-[640px]:wpstg-text-sm" for="wpstg-run-in-background">
                    <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox" id="wpstg-run-in-background" name="runInBackground" value="">
                    <span><?php esc_html_e('Continue backup in background', 'wp-staging'); ?></span>
                    <span class="wpstg--tooltip">
                        <img class="wpstg--dashicons wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info" />
                        <span class="wpstg--tooltiptext wpstg-bottom-0">
                            <?php esc_html_e('This runs the backup in the background and means you can close the window or open another WordPress page and the backup process will not stop.', 'wp-staging'); ?>
                            <br /><?php esc_html_e('You will be notified by e-mail or slack if the backup fails. (If activated in WP Staging settings)', 'wp-staging'); ?>
                        </span>
                    </span>
                </label>
            </div>
            <div class="wpstg-create-backup-modal__footer-actions wpstg-ml-auto wpstg-inline-flex wpstg-items-center wpstg-gap-3 max-[640px]:wpstg-w-full max-[640px]:wpstg-justify-end">
                <button type="button" class="wpstg--create-backup-cancel wpstg-btn wpstg-btn-md wpstg-h-11 wpstg-rounded-lg wpstg-py-0 wpstg-leading-none wpstg-btn-secondary wpstg-px-5">
                    <?php esc_html_e('Cancel', 'wp-staging'); ?>
                </button>
                <button type="button" class="wpstg--create-backup-confirm wpstg-setup-cta wpstg-setup-cta--blue">
                    <span id="wpstg-backup-type-btn"><?php esc_html_e('Start Backup Now', 'wp-staging'); ?></span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14"/>
                        <path d="m12 5 7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </footer>
    </div>
</div>
