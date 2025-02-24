<?php

/**
 * @var \WPStaging\Framework\Adapter\Directory $directories
 * @var string $urlAssets
 * @var bool $isProVersion
 * @var bool $hasSchedule
 */

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Storage\Providers;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\UI\Checkbox;
use WPStaging\Framework\Utils\Times;
use WPStaging\Basic\Ajax\ProCronsCleaner;

$timeFormatOption = get_option('time_format');

/** @var Times */
$time = WPStaging::make(Times::class);

/** @var Providers */
$storages = WPStaging::make(Providers::class);

$recurInterval   = (defined('WPSTG_IS_DEV') && WPSTG_IS_DEV) ? 'PT1M' : 'PT15M';
$recurInterval   = apply_filters('wpstg.schedulesBackup.interval', $recurInterval);
$recurrenceTimes = $time->range('midnight', 'tomorrow - 1 minutes', $recurInterval);

$disabledProAttribute = $isProVersion ? '' : ' disabled';

$disabledClass = !$isProVersion ? 'wpstg-storage-settings-disabled' : '';

$classPropertyHasScheduleAndIsFree = ($hasSchedule && !$isProVersion) ? 'wpstg-free-has-schedule-message ' : '';

$haveProCrons = WPStaging::make(ProCronsCleaner::class)->haveProCrons();

$cronMessage = $haveProCrons ? __('There are backup plans created with WP Staging Pro. Delete them first to create a backup plan with the free version of WP Staging. ', 'wp-staging') :
    __('A backup is created every day at 12:00 noon!', 'wp-staging');

?>
<div id="wpstg--modal--backup--new" data-confirmButtonText="<?php esc_attr_e('Start Backup', 'wp-staging') ?>" style="display: none">
    <h3 class="wpstg--swal2-title wpstg-w-100" for="wpstg-backup-name-input"><?php esc_html_e('Create Site Backup', 'wp-staging') ?></h3>
    <input id="wpstg-backup-name-input" name="backup_name" class="wpstg--swal2-input" placeholder="<?php esc_attr_e('Backup Name (Optional)', 'wp-staging') ?>">

    <div class="wpstg-advanced-options" style="text-align: left;">

        <!-- BACKUP CHECKBOXES -->
        <div class="wpstg-advanced-options-site">
            <label>
                <?php Checkbox::render('includeMediaLibraryInBackup', 'includedDirectories[]', $directories['uploads'], true); ?>
                <?php esc_html_e('Backup Media Library', 'wp-staging') ?>
            </label>
            <label>
                <?php Checkbox::render('includeThemesInBackup', 'includedDirectories[]', $directories['themes'], true); ?>
                <?php esc_html_e('Backup Themes', 'wp-staging') ?>
            </label>
            <label>
                <?php Checkbox::render('includeMuPluginsInBackup', 'includedDirectories[]', $directories['muPlugins'], true); ?>
                <?php esc_html_e('Backup Must-Use Plugins', 'wp-staging') ?>
            </label>
            <label>
                <?php Checkbox::render('includePluginsInBackup', 'includedDirectories[]', $directories['plugins'], true); ?>
                <?php esc_html_e('Backup Plugins', 'wp-staging') ?>
            </label>
            <label>
                <?php Checkbox::render('includeOtherFilesInWpContent', 'includeOtherFilesInWpContent', 'true', true); ?>
                <?php esc_html_e('Backup Other Files In wp-content', 'wp-staging') ?>
                <div class="wpstg--tooltip">
                    <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info"/>
                    <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                            <?php esc_html_e('All files in folder wp-content that are not plugins, themes, mu-plugins and uploads. Recommended for full-site backups.', 'wp-staging') ?>
                    </span>
                </div>
            </label>
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
                    <span class="<?php echo esc_attr($disabledClass) ?>" id="wpstg-wproot-other-files-span" data-id="#wpstg-wproot-scanning-files">
                        <?php esc_html_e('Backup Other WP Root Folders', 'wp-staging'); ?>
                    </span>
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
                <?php esc_html_e('Backup Database', 'wp-staging') ?>
                <div class="wpstg--tooltip">
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
                <input type="hidden" name="backupType" value="<?php echo esc_attr(BackupMetadata::BACKUP_TYPE_SINGLE) ?>"/>
            <?php } else { ?>
                <?php require_once WPSTG_VIEWS_DIR . 'pro/backup/modal/network-options.php'; ?>
            <?php } ?>

            <!-- Advanced Options -->
            <div class="wpstg-backup-options-section">
                <h4 class="swal2-title wpstg-w-100">
                    <?php esc_html_e('Advanced Options', 'wp-staging'); ?>
                </h4>

                <div class="wpstg-container">
                    <label class="wpstg-storage-option">
                        <?php
                        $attributes = [
                            'classes' => $isProVersion ? 'wpstg-is-pro' : 'wpstg-is-basic',
                            'onChange' => 'WPStaging.handleDisplayDependencies(this)',
                        ];
                        Checkbox::render('wpstgSmartExclusion', 'smartExclusion', '', false, $attributes);
                        ?>
                        <span class="<?php echo esc_attr($disabledClass) ?>">
                            <?php esc_html_e('Add Exclusions', 'wp-staging'); ?>
                        </span>
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
                                <?php esc_html_e('This runs the backup in the background and means you can close the window or open another WordPress page and the backup process will not stop.', 'wp-staging') ?>
                                <br/><?php esc_html_e('You will be notified by e-mail or slack if the backup fails. (If activated in WP Staging settings)', 'wp-staging') ?>
                            </span>
                        </div>
                    </label>
                </div>

                <?php if (defined('WPSTG_DEBUG') && constant('WPSTG_DEBUG')) : ?>
                <div class="wpstg-container wpstg-mt-5px">
                    <label class="wpstg-backup-option wpstg-with-tooltip">
                        <?php Checkbox::render('wpstg-validate-backup-files', 'validateBackupFiles'); ?>
                        <span><?php esc_html_e('Validate Backup', 'wp-staging'); ?></span>
                        <div class="wpstg--tooltip">
                            <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info"/>
                            <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                                <?php esc_html_e('Validate each file in the backup to make sure backup is correctly created.', 'wp-staging') ?>
                            </span>
                        </div>
                    </label>
                </div>
                <?php endif; ?>
            </div>
            <!-- End Advanced Options -->

            <div class="wpstg-backup-options-section">
                <h4 class="swal2-title wpstg-w-100">
                    <?php esc_html_e('Backup Times', 'wp-staging') ?>
                </h4>

                <div class="wpstg-backup-scheduling-options wpstg-container <?php echo esc_attr($classPropertyHasScheduleAndIsFree); ?>">

                    <label>
                        <?php
                        $attributes = [
                            'classes' => $isProVersion ? 'wpstg-is-pro' : 'wpstg-is-basic',
                            'onChange' => 'WPStaging.handleDisplayDependencies(this)',
                            'isDisabled' => ($hasSchedule && !$isProVersion),
                        ];
                        Checkbox::render('repeatBackupOnSchedule', 'repeatBackupOnSchedule', '1', true, $attributes);
                        ?>

                        <?php esc_html_e('One-Time Backup', 'wp-staging'); ?>
                    </label>

                    <span class="wpstg--text--danger wpstg-basic-schedule-notice <?php echo $isProVersion ? 'wpstg-is-pro' : 'wpstg-is-basic' ?>" style="display: <?php echo ($hasSchedule && !$isProVersion) ? 'block' : 'none' ?>">
                        <?php echo esc_html($cronMessage); ?>
                        <br>
                        <br>
                        <a href="https://wp-staging.com" target="_blank" class="wpstg-pro-feature-link"><?php echo sprintf(esc_html__('%sUpgrade to Pro%s to create unlimited backup plans, change the start time or upload backups to cloud storage.', 'wp-staging'), '<strong><u>', '</u></strong>'); ?></a>
                    </span>

                    <?php require_once WPSTG_VIEWS_DIR . 'backup/modal/backup-scheduling-options.php'; ?>
                </div>
            </div>

            <div class="wpstg-backup-options-section">
                <h4 class="swal2-title wpstg-w-100">
                    <?php esc_html_e('Storages', 'wp-staging') ?>
                </h4>

                <div class="wpstg-backup-scheduling-options wpstg-container">

                    <label class="wpstg-storage-option wpstg-advanced-storage-options">
                        <?php Checkbox::render("storage-localStorage", 'storages', 'localStorage', true); ?>
                        <span class="wpstg-storage-name"><?php esc_html_e('Local Storage', 'wp-staging'); ?></span>
                    </label>

                    <?php foreach ($storages->getStorages($enabled = true) as $storage) : ?>
                        <label class="wpstg-storage-option wpstg-advanced-storage-options">
                            <?php
                            $isActivated   = $storages->isActivated($storage['authClass']);
                            $isProStorage  = empty($storage['authClass']);
                            $isDisabled    = !$isActivated || (!$isProVersion && $isProStorage);
                            $disabledClass = $isDisabled ? 'wpstg-storage-settings-disabled' : '';
                            Checkbox::render('storage-' . $storage['id'], 'storages', $storage['id'], false, ['isDisabled' => $isDisabled]);
                            ?>
                            <span class="wpstg-storage-name <?php echo esc_attr($disabledClass) ?>"><?php echo esc_html($storage['name']); ?></span>
                            <?php if (!$isProVersion && $isProStorage) { ?>
                                <span class="wpstg-pro-feature"><a href="https://wp-staging.com/get-<?php echo esc_attr($storage['id']) ?>" target="_blank" class="wpstg-pro-feature-link"><?php esc_html_e('Upgrade', 'wp-staging') ?></a></span>
                            <?php } else { ?>
                                <span class="wpstg-storage-settings"><a class="" href="<?php echo esc_url($storage['settingsPath']); ?>" target="_blank"><?php echo $isActivated ? esc_html__('Settings', 'wp-staging') : esc_html__('Activate', 'wp-staging'); ?></a></span>
                            <?php } ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- ADVANCED OPTIONS DROPDOWN -->
        <div class="wpstg-advanced-options-dropdown-wrapper">
            <a href="#" class="wpstg--tab--toggle" data-target=".wpstg-advanced-options-dropdown" style="text-decoration: none;">
                <span style="margin-right: .25em">►</span>
                <?php esc_html_e('Advanced Options', 'wp-staging') ?>
            </a>

            <div class="wpstg-advanced-options-dropdown" style="display:none; padding-left: .75em;">
                <?php esc_html_e('Advanced Options', 'wp-staging') ?>
            </div>
        </div>

    </div>
</div>
