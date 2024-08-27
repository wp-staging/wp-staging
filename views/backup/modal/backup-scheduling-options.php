<?php

/**
 * @var Times $time ;
 * @var DateTimeImmutable[] $recurrenceTimes
 * @var string $disabledProAttribute
 * @var string $timeFormatOption
 * @var string $urlAssets
 * @var bool $isProVersion
 * @var bool $hasSchedule *
 * @see src/views/backup/modal/backup.php
 */

use WPStaging\Core\Cron\Cron;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Facades\UI\Checkbox;
use WPStaging\Framework\Utils\Times;

$proFeature = $isProVersion ? ' ' : ' (Pro Feature)';

?>

<div class="hidden" data-show-if-unchecked="repeatBackupOnSchedule">
    <div class="wpstg-backup-schedule-option">
        <label for="backupScheduleRecurrence">
            <?php esc_html_e('How often:', 'wp-staging'); ?>
        </label>
        <select name="backupScheduleRecurrence" id="backupScheduleRecurrence">
            <option value="<?php echo esc_attr(Cron::HOURLY); ?>" <?php echo esc_attr($disabledProAttribute); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::HOURLY)) . esc_html($proFeature); ?></option>
            <option value="<?php echo esc_attr(Cron::SIX_HOURS); ?>" <?php echo esc_attr($disabledProAttribute); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::SIX_HOURS)) . esc_html($proFeature); ?></option>
            <option value="<?php echo esc_attr(Cron::TWELVE_HOURS); ?>" <?php echo esc_attr($disabledProAttribute); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::TWELVE_HOURS)) . esc_html($proFeature); ?></option>
            <option value="<?php echo esc_attr(Cron::DAILY); ?>" selected> <?php echo esc_html(Cron::getCronDisplayName(Cron::DAILY));?></option>
            <option value="<?php echo esc_attr(Cron::EVERY_TWO_DAYS); ?>" <?php echo esc_attr($disabledProAttribute); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::EVERY_TWO_DAYS)) . esc_html($proFeature); ?></option>
            <option value="<?php echo esc_attr(Cron::WEEKLY); ?>" <?php echo esc_attr($disabledProAttribute); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::WEEKLY)) . esc_html($proFeature); ?></option>
            <option value="<?php echo esc_attr(Cron::EVERY_TWO_WEEKS); ?>" <?php echo esc_attr($disabledProAttribute); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::EVERY_TWO_WEEKS)) . esc_html($proFeature); ?></option>
            <option value="<?php echo esc_attr(Cron::MONTHLY); ?>" <?php echo esc_attr($disabledProAttribute); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::MONTHLY)) . esc_html($proFeature); ?></option>
        </select>
    </div>
    <div class="wpstg-backup-schedule-option">
        <div class="wpstg-backup-schedule-option-inner">
            <label for="backupScheduleTime">
                <?php esc_html_e('Start Time:', 'wp-staging'); ?>
            </label>
            <select name="backupScheduleTime" id="backupScheduleTime">
                <?php $currentTime = (new DateTime('now', $time->getSiteTimezoneObject()))->format($timeFormatOption); ?>
                <?php foreach ($recurrenceTimes as $recurTime) : ?>
                    <option value="<?php echo esc_attr($recurTime->format('H:i')) ?>" <?php echo $isProVersion ? (esc_html($recurTime->format($timeFormatOption)) === esc_html($currentTime) ? 'selected' : '') : ($recurTime->format('H:i') === "00:00" ? 'selected' : 'disabled') ?>>
                        <?php echo esc_html($recurTime->format($timeFormatOption)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div id="backup-schedule-current-time">
                <span><?php echo esc_html__('Current Time', 'wp-staging'); ?></span>
                <br/>
                <span><?php echo esc_html($currentTime); ?></span>
            </div>
        </div>
        <div class="wpstg--tooltip">
            <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info"/>
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

                <?php if (!$isProVersion) {
                    echo '<br><br><hr>';
                    echo esc_html__('You can customize this start time in WP Staging Pro!', 'wp-staging'); ?>
                    <a href="https://wp-staging.com" target="_blank" class="wpstg-pro-feature-link"><?php echo esc_html__('Get Pro Version', 'wp-staging'); ?></a>
                    <?php
                }
                ?>
            </span>
        </div>
    </div>
    <div class="wpstg-backup-schedule-option">
        <div class="wpstg-backup-schedule-option-inner">
            <label for="backupScheduleRotation">
                <?php esc_html_e('Retention:', 'wp-staging'); ?>
            </label>
            <select name="backupScheduleRotation" id="backupScheduleRotation">
                <?php for ($i = 1; $i <= 10; $i++) : ?>
                    <option value="<?php echo esc_attr($i) ?>" <?php echo $isProVersion ? "" : ($i === 1 ? 'selected' : 'disabled') ?>>
                        <?php echo sprintf(esc_html__('Keep last %d backup%s', 'wp-staging'), (int)$i, (int)$i > 1 ? 's' : ''); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="wpstg--tooltip">
            <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info"/>
            <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                <?php esc_html_e('Number of backups to keep before deleting old ones to free up storage space.', 'wp-staging') ?>
                <?php if (!$isProVersion) {
                    echo '<br><br><hr>';
                    echo esc_html__('Keep more than one automatic backup with WP Staging Pro!', 'wp-staging'); ?>
                    <a href="https://wp-staging.com" target="_blank" class="wpstg-pro-feature-link"><?php echo esc_html__('Get Pro Version', 'wp-staging'); ?></a>
                    <?php
                }
                ?>
            </span>
        </div>
    </div>
    <div class="wpstg-backup-schedule-option wpstg-align-checkbox">
        <label for="backupScheduleLaunch">
            <?php esc_html_e('Run Now:', 'wp-staging'); ?>
        </label>
        <?php Checkbox::render('backupScheduleLaunch', 'backupScheduleLaunch'); ?>
    </div>
</div>
