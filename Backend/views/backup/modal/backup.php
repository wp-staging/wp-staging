<?php

/**
 * @var \WPStaging\Framework\Adapter\Directory $directories
 * @var string                                 $urlAssets
 * @var bool                                   $isProVersion
 * @var bool                                   $hasSchedule
 */

use WPStaging\Backup\Storage\Providers;
use WPStaging\Core\Cron\Cron;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Utils\Times;

$timeFormatOption = get_option('time_format');

/** @var Times */
$time = WPStaging::make(Times::class);

/** @var Providers */
$storages = WPStaging::make(Providers::class);

$recurInterval   = (defined('WPSTG_DEV') && WPSTG_DEV) ? 'PT1M' : 'PT15M';
$recurInterval   = apply_filters('wpstg.schedulesBackup.interval', $recurInterval);
$recurrenceTimes = $time->range('midnight', 'tomorrow - 1 minutes', $recurInterval);

$disabledProElement = $isProVersion ? '' : ' disabled';
?>
<div id="wpstg--modal--backup--new" data-confirmButtonText="<?php esc_attr_e('Start Backup', 'wp-staging') ?>" style="display: none">
    <h3 class="wpstg--swal2-title wpstg-w-100" for="wpstg-backup-name-input"><?php esc_html_e('Create Site Backup', 'wp-staging') ?></h3>
    <input id="wpstg-backup-name-input" name="backup_name" class="wpstg--swal2-input" placeholder="<?php esc_attr_e('Backup Name (Optional)', 'wp-staging') ?>">

    <div class="wpstg-advanced-options" style="text-align: left;">

        <!-- BACKUP CHECKBOXES -->
        <div class="wpstg-advanced-options-site">
            <label>
                <input type="checkbox" class="wpstg-checkbox" name="includedDirectories[]" id="includeMediaLibraryInBackup" value="<?php echo esc_attr($directories['uploads']); ?>" checked/>
                <?php esc_html_e('Backup Media Library', 'wp-staging') ?>
            </label>
            <label>
                <input type="checkbox" class="wpstg-checkbox" name="includedDirectories[]" id="includeThemesInBackup" value="<?php echo esc_attr($directories['themes']); ?>" checked/>
                <?php esc_html_e('Backup Themes', 'wp-staging') ?>
            </label>
            <label>
                <input type="checkbox" class="wpstg-checkbox" name="includedDirectories[]" id="includeMuPluginsInBackup" value="<?php echo esc_attr($directories['muPlugins']); ?>" checked/>
                <?php esc_html_e('Backup Must-Use Plugins', 'wp-staging') ?>
            </label>
            <label>
                <input type="checkbox" class="wpstg-checkbox" name="includedDirectories[]" id="includePluginsInBackup" value="<?php echo esc_attr($directories['plugins']); ?>" checked/>
                <?php esc_html_e('Backup Plugins', 'wp-staging') ?>
            </label>
            <label>
                <input type="checkbox" class="wpstg-checkbox" name="includeOtherFilesInWpContent" id="includeOtherFilesInWpContent" value="true" checked/>
                <?php esc_html_e('Backup Other Files In wp-content', 'wp-staging') ?>
                <div class="wpstg--tooltip" style="position: absolute;">
                <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/info-outline.svg" alt="info" />
                    <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                            <?php esc_html_e('All files in folder wp-content that are not plugins, themes, mu-plugins and uploads. Recommended for full-site backups.', 'wp-staging') ?>
                    </span>
                </div>
            </label>
            <label style="display: block;margin: .5em 0;">
                <input type="checkbox" class="wpstg-checkbox" name="backup_database" id="includeDatabaseInBackup" value="true" checked/>
                <?php esc_html_e('Backup Database', 'wp-staging') ?>
                <div id="backupUploadsWithoutDatabaseWarning" style="display:none;">
                    <?php esc_html_e('When backing up the Media Library without the Database, the attachments will be migrated but won\'t show up in the media library after restore.', 'wp-staging'); ?>
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
                        <input type="checkbox" class="wpstg-checkbox" name="repeatBackupOnSchedule" id="repeatBackupOnSchedule" value="1" checked
                               onchange="WPStaging.handleDisplayDependencies(this)" <?php echo ($hasSchedule && !$isProVersion) ? 'disabled' : '' ?>>
                        <?php esc_html_e('One-Time Backup', 'wp-staging'); ?>
                    </label>

                    <?php if ($hasSchedule && !$isProVersion) { ?>
                    <span class="wpstg--text--danger">
                        <?php esc_html_e('Note: Only one schedule allowed in Basic Version!', 'wp-staging'); ?>
                    </span>
                    <?php } ?>

                    <div class="hidden" data-show-if-unchecked="repeatBackupOnSchedule">
                        <label for="backupScheduleRecurrence">
                            <?php esc_html_e('How often?', 'wp-staging'); ?>
                        </label>
                        <select name="backupScheduleRecurrence" id="backupScheduleRecurrence">
                            <option value="<?php echo esc_attr(Cron::HOURLY); ?>" <?php echo esc_attr($disabledProElement); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::HOURLY));?></option>
                            <option value="<?php echo esc_attr(Cron::SIX_HOURS); ?>" <?php echo esc_attr($disabledProElement); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::SIX_HOURS));?></option>
                            <option value="<?php echo esc_attr(Cron::TWELVE_HOURS); ?>" <?php echo esc_attr($disabledProElement); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::TWELVE_HOURS));?></option>
                            <option value="<?php echo esc_attr(Cron::DAILY); ?>" <?php echo esc_attr($disabledProElement); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::DAILY));?></option>
                            <option value="<?php echo esc_attr(Cron::EVERY_TWO_DAYS); ?>" <?php echo esc_attr($disabledProElement); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::EVERY_TWO_DAYS));?></option>
                            <option value="<?php echo esc_attr(Cron::WEEKLY); ?>" <?php echo esc_attr($disabledProElement); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::WEEKLY));?></option>
                            <option value="<?php echo esc_attr(Cron::EVERY_TWO_WEEKS); ?>" <?php echo esc_attr($disabledProElement); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::EVERY_TWO_WEEKS));?></option>
                            <option value="<?php echo esc_attr(Cron::MONTHLY); ?>" <?php echo esc_attr($disabledProElement); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::MONTHLY));?></option>
                        </select>

                        <label for="backupScheduleTime">
                            <?php esc_html_e('Start Time?', 'wp-staging'); ?>
                            <div class="wpstg--tooltip" style="position: absolute;">
                                <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/info-outline.svg" alt="info" />
                                <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                                    <?php echo sprintf(
                                        Escape::escapeHtml(__('Relative to current server time, which you can change in <a href="%s">WordPress Settings</a>.', 'wp-staging')),
                                        esc_url(admin_url('options-general.php#timezone_string'))
                                    ); ?>
                                    <br>
                                    <br>
                                        <?php echo sprintf(esc_html__('Current Server Time: %s', 'wp-staging'), esc_html((new DateTime('now', $time->getSiteTimezoneObject()))->format($timeFormatOption))); ?>
                                        <br>
                                        <?php echo sprintf(esc_html__('Site Timezone: %s', 'wp-staging'), esc_html($time->getSiteTimezoneString())); ?>
                                 </span>
                            </div>
                        </label>
                        <select name="backupScheduleTime" id="backupScheduleTime">
                            <?php $currentTime = (new DateTime('now', $time->getSiteTimezoneObject()))->format($timeFormatOption); ?>
                            <?php foreach ($recurrenceTimes as $recurTime) : ?>
                                <option value="<?php echo esc_attr($recurTime->format('H:i')) ?>" <?php echo $isProVersion ? (esc_html($recurTime->format($timeFormatOption)) === esc_html($currentTime) ? 'selected' : '') : ($recurTime->format('H:i') === "00:00" ? 'selected' : 'disabled') ?>>
                                    <?php echo esc_html($recurTime->format($timeFormatOption)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span id="backup-schedule-current-time"><?php echo sprintf(esc_html__('Current Time: %s', 'wp-staging'), esc_html($currentTime)); ?></span>
                        <label for="backupScheduleRotation">
                            <?php esc_html_e('How many local backups to keep?', 'wp-staging'); ?>
                            <div class="wpstg--tooltip" style="position: absolute;">
                                <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/info-outline.svg" alt="info" />
                                <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                                    <?php esc_html_e('How many local backups to keep before deleting old ones to free up storage space.', 'wp-staging') ?>
                                 </span>
                            </div>
                        </label>
                        <select name="backupScheduleRotation" id="backupScheduleRotation">
                            <?php for ($i = 1; $i <= 10; $i++) : ?>
                                <option value="<?php echo esc_attr($i) ?>" <?php echo $isProVersion ? "" : ($i === 2 ? 'selected' : 'disabled') ?>>
                                    <?php esc_html_e(sprintf('Keep last %d backup%s', $i, ($i > 1 ? 's' : ''))); ?>
                                </option>
                            <?php endfor; ?>
                        </select>

                        <label for="backupScheduleLaunch">
                            <input type="checkbox" class="wpstg-checkbox" name="backupScheduleLaunch" id="backupScheduleLaunch" />
                            <?php esc_html_e('Run Backup Now?', 'wp-staging'); ?>
                        </label>
                    </div>

                </div>
            </div>

            <div class="wpstg-backup-options-section">
                <h4 class="swal2-title wpstg-w-100" >
                    <?php esc_html_e('Storages', 'wp-staging') ?>
                </h4>

                <div class="wpstg-backup-scheduling-options wpstg-container">

                    <label class="wpstg-storage-option">
                        <input type="checkbox" class="wpstg-checkbox" name="storages" id="storage-localStorage" value="localStorage" checked />
                        <span><?php esc_html_e('Local Storage', 'wp-staging'); ?></span>
                    </label>

                    <?php foreach ($storages->getStorages($enabled = true) as $storage) : ?>
                        <label class="wpstg-storage-option">
                            <?php
                                $isActivated   = $storages->isActivated($storage['authClass']);
                                $isProStorage  = empty($storage['authClass']);
                                $isDisabled    = !$isActivated || (!$isProVersion && $isProStorage);
                                $disabledClass = $isDisabled ? 'wpstg-storage-settings-disabled' : '';
                            ?>
                            <input type="checkbox" class="wpstg-checkbox" name="storages" id="storage-<?php echo esc_attr($storage['id'])?>" value="<?php echo esc_attr($storage['id'])?>" <?php echo $isDisabled ? 'disabled' : '' ?> />
                            <span class="<?php echo esc_attr($disabledClass) ?>"><?php echo esc_html($storage['name']); ?></span>
                            <?php if (!$isProVersion && $isProStorage) { ?>
                                <span class="wpstg--text--danger wpstg-ml-8"><?php esc_html_e('Premium Feature', 'wp-staging') ?></span>
                            <?php } else { ?>
                                <span class="wpstg-storage-settings"><a class="" href="<?php echo esc_url($storage['settingsPath']); ?>" target="_blank"><?php echo $isActivated ? esc_html('Settings', 'wp-staging') : esc_html('Activate', 'wp-staging'); ?></a></span>
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
