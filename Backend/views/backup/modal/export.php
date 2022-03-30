<?php

/**
 * @var \WPStaging\Framework\Adapter\Directory $directories
 * @var string                                 $urlAssets
 */

use WPStaging\Core\Cron\Cron;

$timeFormatOption = get_option('time_format');

$time = WPStaging\Core\WPStaging::make(\WPStaging\Framework\Utils\Times::class);

/** @var \WPStaging\Pro\Backup\Storage\Providers */
$storages = WPStaging\Core\WPStaging::make(\WPStaging\Pro\Backup\Storage\Providers::class);

$recurrenceTimes = $time->range('midnight', 'tomorrow - 60 minutes', defined('WPSTG_DEV') && WPSTG_DEV ? 'PT1M' : 'PT15M');
?>
<div id="wpstg--modal--backup--new" data-confirmButtonText="<?php esc_attr_e('Start Backup', 'wp-staging') ?>" style="display: none">
    <h3 class="wpstg--swal2-title wpstg-w-100" for="wpstg-backup-name-input"><?php esc_html_e('Create Site Backup', 'wp-staging') ?></h3>
    <input id="wpstg-backup-name-input" name="backup_name" class="wpstg--swal2-input" placeholder="<?php esc_attr_e('Backup Name (Optional)', 'wp-staging') ?>">

    <div class="wpstg-advanced-options" style="text-align: left;">

        <!-- BACKUP CHECKBOXES -->
        <div class="wpstg-advanced-options-site">
            <label>
                <input type="checkbox" name="includedDirectories[]" id="includeMediaLibraryInBackup" value="<?php echo esc_attr($directories['uploads']); ?>" checked/>
                <?php esc_html_e('Backup Media Library', 'wp-staging') ?>
            </label>
            <label>
                <input type="checkbox" name="includedDirectories[]" id="includeThemesInBackup" value="<?php echo esc_attr($directories['themes']); ?>" checked/>
                <?php esc_html_e('Backup Themes', 'wp-staging') ?>
            </label>
            <label>
                <input type="checkbox" name="includedDirectories[]" id="includeMuPluginsInBackup" value="<?php echo esc_attr($directories['muPlugins']); ?>" checked/>
                <?php esc_html_e('Backup Must-Use Plugins', 'wp-staging') ?>
            </label>
            <label>
                <input type="checkbox" name="includedDirectories[]" id="includePluginsInBackup" value="<?php echo esc_attr($directories['plugins']); ?>" checked/>
                <?php esc_html_e('Backup Plugins', 'wp-staging') ?>
            </label>
            <label>
                <input type="checkbox" name="includeOtherFilesInWpContent" id="includeOtherFilesInWpContent" value="true" checked/>
                <?php esc_html_e('Backup Other Files In wp-content', 'wp-staging') ?>
                <div class="wpstg--tooltip" style="position: absolute;">
                <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo $urlAssets; ?>svg/vendor/dashicons/info-outline.svg" alt="info" />
                    <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                            <?php esc_html_e('All files in folder wp-content that are not plugins, themes, mu-plugins and uploads. Recommended for full-site backups.', 'wp-staging') ?>
                    </span>
                </div>
            </label>
            <label style="display: block;margin: .5em 0;">
                <input type="checkbox" name="export_database" id="includeDatabaseInBackup" value="true" checked/>
                <?php esc_html_e('Backup Database', 'wp-staging') ?>
                <div id="exportUploadsWithoutDatabaseWarning" style="display:none;">
                    <?php esc_html_e('When exporting the Media Library without the Database, the attachments will be migrated but won\'t show up in the media library after import.', 'wp-staging'); ?>
                </div>
            </label>
            <input type="hidden" name="wpContentDir" value="<?php echo esc_attr($directories['wpContent']); ?>"/>
            <input type="hidden" name="wpStagingDir" value="<?php echo esc_attr($directories['wpStaging']); ?>"/>
            <?php unset($directories['wpContent'], $directories['wpStaging']) ?>
            <input type="hidden" name="availableDirectories" value="<?php echo esc_attr(implode('|', $directories)); ?>"/>

            <div class="wpstg-backup-options-section">
                <h4 class="swal2-title wpstg-w-100" >
                    <?php esc_html_e('Backup Times', 'wp-staging') ?>
                </h4>

                <div class="wpstg-backup-scheduling-options wpstg-container">

                    <label>
                        <input type="checkbox" name="repeatBackupOnSchedule" id="repeatBackupOnSchedule" value="1" checked
                               onchange="WPStaging.handleDisplayDependencies(this)">
                        <?php esc_html_e('One-Time Backup', 'wp-staging'); ?>
                    </label>

                    <div class="hidden" data-show-if-unchecked="repeatBackupOnSchedule">
                        <label for="backupScheduleRecurrence">
                            <?php esc_html_e('How often to backup?', 'wp-staging'); ?>
                        </label>
                        <select name="backupScheduleRecurrence" id="backupScheduleRecurrence">
                            <option value="<?php echo esc_attr(Cron::HOURLY); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::HOURLY));?></option>
                            <option value="<?php echo esc_attr(Cron::SIX_HOURS); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::SIX_HOURS));?></option>
                            <option value="<?php echo esc_attr(Cron::TWELVE_HOURS); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::TWELVE_HOURS));?></option>
                            <option value="<?php echo esc_attr(Cron::DAILY); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::DAILY));?></option>
                            <option value="<?php echo esc_attr(Cron::EVERY_TWO_DAYS); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::EVERY_TWO_DAYS));?></option>
                            <option value="<?php echo esc_attr(Cron::WEEKLY); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::WEEKLY));?></option>
                            <option value="<?php echo esc_attr(Cron::EVERY_TWO_WEEKS); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::EVERY_TWO_WEEKS));?></option>
                            <option value="<?php echo esc_attr(Cron::MONTHLY); ?>"><?php echo esc_html(Cron::getCronDisplayName(Cron::MONTHLY));?></option>
                        </select>

                        <label for="backupScheduleTime">
                            <?php esc_html_e('At what time should it start?', 'wp-staging'); ?>
                            <div class="wpstg--tooltip" style="position: absolute;">
                                <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo $urlAssets; ?>svg/vendor/dashicons/info-outline.svg" alt="info" />
                                <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                                    <?php _e(sprintf('Relative to current server time, which you can change in <a href="%s">WordPress Settings</a>.', admin_url('options-general.php#timezone_string'))); ?>
                                    <br>
                                    <br>
                                        <?php _e(sprintf('Current Server Time: %s', (new DateTime('now', $time->getSiteTimezoneObject()))->format($timeFormatOption)), 'wp-staging'); ?>
                                        <br>
                                        <?php _e(sprintf('Site Timezone: %s', $time->getSiteTimezoneString()), 'wp-staging'); ?>
                                 </span>
                            </div>
                        </label>
                        <select name="backupScheduleTime" id="backupScheduleTime">
                            <?php $currentTime = (new DateTime('now', $time->getSiteTimezoneObject()))->format($timeFormatOption); ?>
                            <?php foreach ($recurrenceTimes as $recurTime) : ?>
                                <option value="<?php echo esc_attr($recurTime->format('H:i')) ?>" <?php echo esc_html($recurTime->format($timeFormatOption)) === esc_html($currentTime) ? 'selected' : '' ?>>
                                    <?php echo esc_html($recurTime->format($timeFormatOption)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span id="backup-schedule-current-time"><?php _e(sprintf('Current Time: %s', $currentTime), 'wp-staging'); ?></span>
                        <label for="backupScheduleRotation">
                            <?php esc_html_e('How many backups to keep?', 'wp-staging'); ?>
                            <div class="wpstg--tooltip" style="position: absolute;">
                                <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo $urlAssets; ?>svg/vendor/dashicons/info-outline.svg" alt="info" />
                                <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                                    <?php esc_html_e('Choose how many backups you want to keep before old ones are deleted to free up disk space.', 'wp-staging') ?>
                                 </span>
                            </div>
                        </label>
                        <select name="backupScheduleRotation" id="backupScheduleRotation">
                            <?php for ($i = 1; $i <= 10; $i++) : ?>
                                <option value="<?php echo $i ?>">
                                    <?php esc_html_e(sprintf('Keep last %d backup%s', $i, ($i > 1 ? 's' : ''))); ?>
                                </option>
                            <?php endfor; ?>
                        </select>

                        <label for="backupScheduleLaunch">
                            <?php esc_html_e('Run backup now?', 'wp-staging'); ?>
                        </label>
                        <input type="checkbox" name="backupScheduleLaunch" id="backupScheduleLaunch" />
                    </div>

                </div>
            </div>

            <div class="wpstg-backup-options-section">
                <h4 class="swal2-title wpstg-w-100" >
                    <?php esc_html_e('Storages', 'wp-staging') ?>
                </h4>

                <div class="wpstg-backup-scheduling-options wpstg-container">

                    <label class="wpstg-storage-option">
                        <input type="checkbox" name="storages" id="storage-localStorage" value="localStorage" checked disabled>
                        <span><?php esc_html_e('Local Storage', 'wp-staging'); ?></span>
                    </label>

                    <?php foreach ($storages->getStorages(true) as $storage) : ?>
                        <label class="wpstg-storage-option">
                            <?php
                                $isActivated = $storage['activated'];
                            ?>
                            <input type="checkbox" name="storages" id="storage-<?php echo $storage['id']?>" value="<?php echo $storage['id']?>" <?php echo $isActivated === false ? 'disabled' : '' ?> />
                            <span><?php echo $storage['name']; ?></span>
                            <span class="wpstg-storage-settings"><a class="<?php echo $isActivated === false ? 'wpstg-storage-settings-disabled' : ''; ?>" href="<?php echo $storage['settingsPath']; ?>" target="_blank"><?php echo $isActivated ? esc_html('Settings', 'wp-staging') : esc_html('Activate', 'wp-staging'); ?></a></span>
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
                Advanced options
            </div>
        </div>

    </div>
</div>
