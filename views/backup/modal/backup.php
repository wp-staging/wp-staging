<?php

/**
 * @var \WPStaging\Framework\Adapter\Directory $directories
 * @var string $urlAssets
 * @var bool $isProVersion
 * @var bool $hasSchedule
 * @var bool $isPersonalLicense
 */

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\UI\Checkbox;
use WPStaging\Framework\Utils\Times;
use WPStaging\Basic\Ajax\ProCronsCleaner;

$timeFormatOption = get_option('time_format');

/** @var Times */
$time = WPStaging::make(Times::class);

$recurInterval   = (defined('WPSTG_IS_DEV') && WPSTG_IS_DEV) ? 'PT1M' : 'PT15M';
$recurInterval   = apply_filters('wpstg.schedulesBackup.interval', $recurInterval);
$recurrenceTimes = $time->range('midnight', 'tomorrow - 1 minutes', $recurInterval);

$disabledProAttribute = $isProVersion ? '' : ' disabled';

$disabledClass = !$isProVersion ? 'wpstg-storage-settings-disabled' : '';

$classPropertyHasScheduleAndIsFree = ($hasSchedule && !$isProVersion) ? 'wpstg-free-has-schedule-message ' : '';

$haveProCrons = WPStaging::make(ProCronsCleaner::class)->haveProCrons();

$cronMessage = $haveProCrons ? __('There are backup plans created with WP Staging Pro. Delete them first to create a backup plan with the free version of WP Staging. ', 'wp-staging') :
    __('A backup is created every day at 12:00 noon!', 'wp-staging');

$storagesPrefix = 'storage-';
?>
<div id="wpstg--modal--backup--new" data-confirmButtonText="<?php esc_attr_e('Start Backup', 'wp-staging'); ?>" style="display: none">
    <h3 class="wpstg--swal2-title wpstg-w-100" for="wpstg-backup-name-input"><?php esc_html_e('Create Site Backup', 'wp-staging'); ?></h3>
    <input id="wpstg-backup-name-input" name="backup_name" class="wpstg--swal2-input" placeholder="<?php esc_attr_e('Backup Name (Optional)', 'wp-staging'); ?>">

    <div class="wpstg-advanced-options" style="text-align: left;">

        <!-- BACKUP CHECKBOXES -->
        <div class="wpstg-advanced-options-site">
            <label>
                <?php Checkbox::render('includeMediaLibraryInBackup', 'includedDirectories[]', $directories['uploads'], true); ?>
                <?php esc_html_e('Backup Media Library', 'wp-staging'); ?>
                <span id="includeMediaLibraryInBackupSize"></span>
            </label>
            <label>
                <?php Checkbox::render('includeThemesInBackup', 'includedDirectories[]', $directories['themes'], true); ?>
                <?php esc_html_e('Backup Themes', 'wp-staging'); ?>
                <span id="includeThemesInBackupSize"></span>
            </label>
            <label>
                <?php Checkbox::render('includeMuPluginsInBackup', 'includedDirectories[]', $directories['muPlugins'], true); ?>
                <?php esc_html_e('Backup Must-Use Plugins', 'wp-staging'); ?>
                <span id="includeMuPluginsInBackupSize"></span>
            </label>
            <label>
                <?php Checkbox::render('includePluginsInBackup', 'includedDirectories[]', $directories['plugins'], true); ?>
                <?php esc_html_e('Backup Plugins', 'wp-staging'); ?>
                <span id="includePluginsInBackupSize"></span>
            </label>
            <div>
                <label>
                    <?php Checkbox::render('includeOtherFilesInWpContent', 'includeOtherFilesInWpContent', 'true', true); ?>
                    <?php esc_html_e('Backup Other Files In wp-content', 'wp-staging'); ?>
                    <span id="includeOtherFilesInWpContentSize"></span>
                    <div class="wpstg--tooltip wpstg-backup-modal-tooltip">
                        <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info"/>
                        <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                                <?php esc_html_e('All files in folder wp-content that are not plugins, themes, mu-plugins and uploads. Recommended for full-site backups.', 'wp-staging'); ?>
                        </span>
                    </div>
                </label>
            </div>
            <div>
                <label>
                    <?php if ($isProVersion) : ?>
                        <div class="wpstg--wproot-expand-folder">
                            <img class="wpstg--dashicons wpstg-dashicons-14 wpstg--expand-folder-img" src="<?php echo esc_url($urlAssets); ?>svg/folder-expand-chevron.svg" alt="info"/>
                        </div>
                    <?php endif; ?>
                    <?php
                        Checkbox::render(
                            'wpstgIncludeOtherFilesInWpRoot',
                            'includeOtherFilesInWpRoot',
                            '',
                            false,
                            [
                                'classes'    => $isProVersion ? 'wpstg-is-pro' : 'wpstg-is-basic',
                                'isDisabled' => !$isProVersion
                            ]
                        );
                        ?>
                    <span class="<?php echo esc_attr($disabledClass); ?>" id="wpstg-wproot-other-files-span" data-id="#wpstg-wproot-scanning-files">
                        <?php esc_html_e('Backup Other WP Root Folders', 'wp-staging'); ?>
                    </span>
                    <span id="wpstgIncludeOtherFilesInWpRootSize"></span>
                    <div class="wpstg--tooltip wpstg-wproot-tooltip">
                        <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info"/>
                        <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                            <?php echo sprintf(esc_html__('Only folders are backed up; files like %s are excluded and must be saved manually if needed. The following folders are also not included in the backup: %s, %s, and those containing WP Staging sites. To back up a staging site, open WP Staging on that site and create a backup directly from there.', 'wp-staging'), '<code>wp-config.php</code>', '<code>wp-admin</code>', '<code>wp-includes</code>'); ?>
                        </span>
                    </div>

                    <?php if (!$isProVersion) : ?>
                        <a href="https://wp-staging.com" target="_blank" class="wpstg-pro-feature-link"><span class="wpstg-pro-feature wpstg-ml-8"><?php esc_html_e('Upgrade', 'wp-staging'); ?></span></a>
                    <?php else : ?>
                        <fieldset class="wpstg-wproot-files-selection-section wpstg-wproot-files-selection" id="wpstg-wproot-scanning-files">
                            <?php require(WPSTG_VIEWS_DIR . 'pro/backup/backup-files.php'); ?>
                        </fieldset>
                    <?php endif; ?>
                </label>
            </div>

            <label>
                <?php Checkbox::render('includeDatabaseInBackup', 'backup_database', 'true', true); ?>
                <?php esc_html_e('Backup Database', 'wp-staging'); ?>
                <span id="includeDatabaseInBackupSize"></span>
                <div class="wpstg--tooltip wpstg-backup-modal-tooltip">
                    <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info"/>
                    <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                            <?php
                            printf(
                                esc_html__('This will backup all database tables starting with the prefix "%s". To backup a staging site, run the backup function again on the staging site', 'wp-staging'),
                                isset($GLOBALS['wpdb']->prefix) ? esc_html($GLOBALS['wpdb']->prefix) : 'wp_'
                            );
                            ?>
                    </span>
                </div>
                <div id="backupUploadsWithoutDatabaseWarning" style="display:none;">
                    <?php esc_html_e('When backing up the Media Library without the Database, the attachments will be migrated but won\'t show up in the media library after restore.', 'wp-staging'); ?>
                </div>
            </label>
            <input type="hidden" name="wpContentDir" value="<?php echo esc_attr($directories['wpContent']); ?>"/>
            <input type="hidden" name="wpStagingDir" value="<?php echo esc_attr($directories['wpStaging']); ?>"/>
            <?php unset($directories['wpContent'], $directories['wpStaging']) ?>
            <input type="hidden" name="availableDirectories" value="<?php echo esc_attr(implode('|', (array)$directories)); ?>"/>
            <?php if (!is_multisite()) { ?>
                <input type="hidden" name="backupType" value="<?php echo esc_attr(BackupMetadata::BACKUP_TYPE_SINGLE); ?>"/>
            <?php } else { ?>
                <?php require_once WPSTG_VIEWS_DIR . 'pro/backup/modal/network-options.php'; ?>
            <?php } ?>

            <!--calculate backup size-->
            <div class="wpstg-calculate-backup-container">
                <div class="wpstg-left-group">
                    <div id="wpstg-calculate-backup-size" class="wpstg-blue-primary wpstg-button">
                        <span class="wpstg-calculator-icon"></span>
                        <?php esc_html_e('Calculate Backup Size', 'wp-staging'); ?>
                    </div>
                    <div class="wpstg-loader-container">
                        <span id="wpstg-calculate-backup-size-loader" class="wpstg-loader"></span>
                    </div>
                </div>
                <div id="wpstg-total-backup-size-container" class="wpstg-right-group">
                    <div class="wpstg-estimate-container">
                        <strong><?php esc_html_e('Estimated Size:', 'wp-staging'); ?></strong>
                        <span id="wpstg-total-estimated-backup-size">0.0</span>
                    </div>
                </div>
            </div>
            <!--calculate backup size-->
            <!-- Advanced Options -->
            <div id="wpstg-backup-advance-section-header" data-id="#wpstg-backup-advance-section">
                <img class="wpstg--dashicons wpstg-dashicons-14 wpstg--expand-folder-img" src="<?php echo esc_url($urlAssets); ?>svg/folder-expand-chevron.svg" alt="Advanced Options"/>
                <strong><?php esc_html_e("Advanced Options", 'wp-staging'); ?></strong>
            </div>
            <div class="wpstg-backup-options-section hidden wpstg-sub-options-details" id="wpstg-backup-advance-section">
                <div class="wpstg-container">
                    <label class="wpstg-backup-option wpstg-with-tooltip" id="wpstg-add-exclusions-label">
                        <div class="wpstg--add-exclusions-expand-folder">
                            <img class="wpstg--dashicons wpstg-dashicons-14 wpstg--add-exclusions-expand-folder-img" src="<?php echo esc_url($urlAssets); ?>svg/folder-expand-chevron.svg" alt="info"/>
                        </div>
                        <?php
                        $attributes = [
                            'classes'    => $isProVersion ? 'wpstg-is-pro' : 'wpstg-is-basic',
                            'onChange'   => 'WPStaging.handleDisplayDependencies(this)',
                            'isDisabled' => !$isProVersion,
                        ];
                        Checkbox::render('wpstgSmartExclusion', 'smartExclusion', '', false, $attributes);
                        ?>
                        <span class="<?php echo esc_attr($disabledClass); ?>" id="wpstg-add-exclusions-span" data-id="#wpstg-advanced-exclude-options">
                            <?php esc_html_e('Add Exclusions', 'wp-staging'); ?>
                        </span>
                        <div class="wpstg--tooltip">
                            <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info"/>
                            <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                                <?php esc_html_e('To keep backups fast and efficient, we automatically exclude files over 200MB and system files like .wpstg, .gz, and .tmp', 'wp-staging'); ?>
                                <br/><?php printf(esc_html__('Want to change this? %s', 'wp-staging'), '<a href="https://wp-staging.com/docs/actions-and-filters/#Exclude_a_file_extension_from_backup" target="_blank" rel="noopener noreferrer">' . esc_html__('Learn how to customize exclusions.', 'wp-staging') . '</a>'); ?>
                            </span>
                        </div>
                        <?php if (!$isProVersion) : ?>
                            <a href="https://wp-staging.com" target="_blank" class="wpstg-pro-feature-link"><span class="wpstg-pro-feature wpstg-ml-8"><?php esc_html_e('Upgrade', 'wp-staging'); ?></span></a>
                        <?php endif; ?>
                    </label>

                    <?php require_once WPSTG_VIEWS_DIR . 'backup/modal/advanced-exclude-options.php'; ?>
                </div>

                <div class="wpstg-container wpstg-mt-5px">
                    <label class="wpstg-backup-option wpstg-with-tooltip">
                        <?php Checkbox::render('wpstg-run-in-background', 'runInBackground'); ?>
                        <span><?php esc_html_e('Run In Background', 'wp-staging'); ?></span>
                        <div class="wpstg--tooltip">
                            <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info"/>
                            <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                                <?php esc_html_e('This runs the backup in the background and means you can close the window or open another WordPress page and the backup process will not stop.', 'wp-staging'); ?>
                                <br/><?php esc_html_e('You will be notified by e-mail or slack if the backup fails. (If activated in WP Staging settings)', 'wp-staging'); ?>
                            </span>
                        </div>
                    </label>
                </div>
            </div>
            <!-- End Advanced Options -->
            <div id="wpstg-backup-times-header" data-id="#wpstg-backup-times-section">
                <img class="wpstg--dashicons wpstg-dashicons-14 wpstg--expand-folder-img" src="<?php echo esc_url($urlAssets); ?>svg/folder-expand-chevron.svg" alt="Backup Times"/>
                <strong><?php esc_html_e("Backup Times", 'wp-staging'); ?></strong>
            </div>
            <div class="wpstg-backup-options-section hidden wpstg-sub-options-details" id="wpstg-backup-times-section">
                <div class="wpstg-backup-scheduling-options wpstg-container <?php echo esc_attr($classPropertyHasScheduleAndIsFree); ?>">

                    <label>
                        <?php
                        $attributes = [
                            'classes'    => $isProVersion ? 'wpstg-is-pro' : 'wpstg-is-basic',
                            'onChange'   => 'WPStaging.handleDisplayDependencies(this)',
                            'isDisabled' => ($hasSchedule && !$isProVersion),
                        ];
                        Checkbox::render('repeatBackupOnSchedule', 'repeatBackupOnSchedule', '1', true, $attributes);
                        ?>

                        <?php esc_html_e('One-Time Backup', 'wp-staging'); ?>
                    </label>

                    <span class="wpstg--text--danger wpstg-basic-schedule-notice <?php echo $isProVersion ? 'wpstg-is-pro' : 'wpstg-is-basic'; ?>" style="display: <?php echo ($hasSchedule && !$isProVersion) ? 'block' : 'none'; ?>">
                        <?php echo esc_html($cronMessage); ?>
                        <br>
                        <br>
                        <a href="https://wp-staging.com" target="_blank" class="wpstg-pro-feature-link"><?php echo sprintf(esc_html__('%sUpgrade to Pro%s to create unlimited backup plans, change the start time or upload backups to cloud storage.', 'wp-staging'), '<strong><u>', '</u></strong>'); ?></a>
                    </span>

                    <?php require_once WPSTG_VIEWS_DIR . 'backup/modal/backup-scheduling-options.php'; ?>
                </div>
            </div>
            <div id="wpstg-backup-storages-header" data-id="#wpstg-storages-section">
                <img class="wpstg--dashicons wpstg-dashicons-14 wpstg--expand-folder-img" src="<?php echo esc_url($urlAssets); ?>svg/folder-expand-chevron.svg" alt="Storage Providers"/>
                <strong><?php esc_html_e("Storage Providers", 'wp-staging'); ?></strong>
            </div>
            <div id="wpstg-storages-section" class="hidden">
                <?php require WPSTG_VIEWS_DIR . 'backup/modal/backup-storages.php'; ?>
            </div>
        </div>

        <!-- ADVANCED OPTIONS DROPDOWN -->
        <div class="wpstg-advanced-options-dropdown-wrapper">
            <a href="#" class="wpstg--tab--toggle" data-target=".wpstg-advanced-options-dropdown" style="text-decoration: none;">
                <span style="margin-right: .25em">►</span>
                <?php esc_html_e('Advanced Options', 'wp-staging'); ?>
            </a>

            <div class="wpstg-advanced-options-dropdown" style="display:none; padding-left: .75em;">
                <?php esc_html_e('Advanced Options', 'wp-staging'); ?>
            </div>
        </div>

    </div>
</div>
