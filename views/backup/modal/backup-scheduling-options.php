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
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\Utils\Times;

$proFeature = $isProVersion ? ' ' : ' (' . __('Pro', 'wp-staging') . ')';
?>

<div id="wpstg-sched-fields" class="wpstg-mt-3 wpstg-space-y-3 wpstg-hidden">

    <?php
    $currentTime = (new DateTime('now', $time->getSiteTimezoneObject()))->format($timeFormatOption);
    ?>

    <div class="wpstg-flex wpstg-items-center wpstg-justify-between wpstg-gap-2" role="status">
        <div class="wpstg-inline-flex wpstg-min-w-0 wpstg-flex-1 wpstg-items-center wpstg-gap-2">
            <svg class="wpstg-shrink-0 wpstg-text-royal-600 dark:wpstg-text-blue-400" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8"  y1="2" x2="8"  y2="6"/>
                <line x1="3"  y1="10" x2="21" y2="10"/>
            </svg>
            <span id="wpstg-sched-summary">&mdash;</span>
        </div>
        <div class="wpstg-flex wpstg-items-center wpstg-gap-2 wpstg-shrink-0">
            <div id="backup-schedule-current-time" class="wpstg-badge wpstg-badge-pill wpstg-badge-blue">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <span><?php echo esc_html__('Now:', 'wp-staging'); ?></span>
                <span><?php echo esc_html($currentTime); ?></span>
            </div>
        </div>
    </div>

    <div class="wpstg-grid wpstg-grid-cols-3 wpstg-gap-3 !wpstg-border-0 max-[640px]:wpstg-grid-cols-1">
        <div class="wpstg-w-full wpstg-space-y-2">
            <div>
                <label for="backupScheduleRecurrence" class="wpstg-w-full wpstg-leading-tight"><?php esc_html_e('Repeat', 'wp-staging'); ?></label>
            </div>
            <div class="wpstg-relative wpstg-flex-1">
                <select name="backupScheduleRecurrence" id="backupScheduleRecurrence" class="wpstg-mt-1.5 !wpstg-w-full dark:!wpstg-border-slate-600 dark:!wpstg-text-slate-100 dark:focus:!wpstg-border-blue-500">
                    <optgroup label="<?php esc_attr_e('Hourly', 'wp-staging'); ?>">
                        <option value="<?php echo esc_attr(Cron::HOURLY); ?>" <?php echo esc_attr($disabledProAttribute); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::HOURLY))       . esc_html($proFeature); ?></option>
                        <option value="<?php echo esc_attr(Cron::SIX_HOURS); ?>" <?php echo esc_attr($disabledProAttribute); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::SIX_HOURS))    . esc_html($proFeature); ?></option>
                        <option value="<?php echo esc_attr(Cron::TWELVE_HOURS); ?>" <?php echo esc_attr($disabledProAttribute); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::TWELVE_HOURS)) . esc_html($proFeature); ?></option>
                    </optgroup>
                    <optgroup label="<?php esc_attr_e('Daily', 'wp-staging'); ?>">
                        <option value="<?php echo esc_attr(Cron::DAILY); ?>" selected><?php echo esc_html(Cron::getCronDisplayName(Cron::DAILY)); ?></option>
                        <option value="<?php echo esc_attr(Cron::EVERY_TWO_DAYS); ?>" <?php echo esc_attr($disabledProAttribute); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::EVERY_TWO_DAYS)) . esc_html($proFeature); ?></option>
                    </optgroup>
                    <optgroup label="<?php esc_attr_e('Weekly', 'wp-staging'); ?>">
                        <?php for ($day = 1; $day <= 7; $day++) :
                            $weeklyDay = Cron::WEEKLY . '_' . $day; ?>
                            <option value="<?php echo esc_attr($weeklyDay); ?>" <?php echo esc_attr($disabledProAttribute); ?>><?php echo esc_html(Cron::getCronDisplayName($weeklyDay)) . esc_html($proFeature); ?></option>
                        <?php endfor; ?>
                        <option value="<?php echo esc_attr(Cron::EVERY_TWO_WEEKS); ?>" <?php echo esc_attr($disabledProAttribute); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::EVERY_TWO_WEEKS)) . esc_html($proFeature); ?></option>
                    </optgroup>
                    <optgroup label="<?php esc_attr_e('Monthly', 'wp-staging'); ?>">
                        <option value="<?php echo esc_attr(Cron::MONTHLY); ?>" <?php echo esc_attr($disabledProAttribute); ?>><?php echo esc_html(Cron::getCronDisplayName(Cron::MONTHLY)) . esc_html($proFeature); ?></option>
                    </optgroup>
                </select>
            </div>
        </div>

        <div class="wpstg-w-full wpstg-space-y-2">
            <div class="wpstg-flex wpstg-w-full wpstg-items-center">
                <label for="backupScheduleTime" class="wpstg-leading-tight"><?php esc_html_e('Start time', 'wp-staging'); ?></label>
                <div class="wpstg--tooltip !wpstg-relative">
                    <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info"/>
                    <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                        <?php echo sprintf(
                            Escape::escapeHtml(__('Relative to current server time, which you can change in <a href="%s">WordPress Settings</a>.', 'wp-staging')),
                            esc_url(admin_url('options-general.php#timezone_string'))
                        ); ?>
                        <br><br>
                        <?php echo sprintf(esc_html__('Current Server Time: %s', 'wp-staging'), esc_html((new DateTime('now', $time->getSiteTimezoneObject()))->format($timeFormatOption))); ?>
                        <br>
                        <?php echo sprintf(esc_html__('Site Timezone: %s', 'wp-staging'), esc_html($time->getSiteTimezoneString())); ?>
                        <?php if (!$isProVersion) {
                            echo '<br><br><hr>';
                            echo esc_html__('You can customize this start time in WP Staging Pro!', 'wp-staging'); ?>
                            <a href="<?php echo esc_url(Language::getUpgradeUrl('backup_schedule_time')); ?>" target="_blank" class="wpstg-btn wpstg-btn-sm wpstg-btn-pro-soft wpstg-mt-2"><?php echo esc_html__('Get Pro Version', 'wp-staging'); ?></a>
                        <?php } ?>
                    </span>
                </div>
            </div>
            <div class="wpstg-flex">
                <div class="wpstg-flex wpstg-w-full">
                    <select name="backupScheduleTime" id="backupScheduleTime" class="!wpstg-w-full dark:!wpstg-border-slate-600 dark:!wpstg-text-slate-100 dark:focus:!wpstg-border-blue-500">
                        <?php foreach ($recurrenceTimes as $recurTime) : ?>
                            <option value="<?php echo esc_attr($recurTime->format('H:i')); ?>"
                                    <?php echo $isProVersion
                                            ? (esc_html($recurTime->format($timeFormatOption)) === esc_html($currentTime) ? 'selected' : '')
                                            : ($recurTime->format('H:i') === '00:00' ? 'selected' : 'disabled'); ?>>
                                <?php echo esc_html($recurTime->format($timeFormatOption)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="wpstg-w-full wpstg-space-y-2">
            <div class="wpstg-flex wpstg-w-full wpstg-items-center">
                <label for="backupScheduleRotation" class="wpstg-leading-tight"><?php esc_html_e('Retention', 'wp-staging'); ?></label>
                <div class="wpstg--tooltip wpstg-shrink-0 !wpstg-relative">
                    <img class="wpstg--dashicons wpstg-dashicons-19 wpstg--grey" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg" alt="info"/>
                    <span class="wpstg--tooltiptext wpstg--tooltiptext-backups wpstg-left-[-125px]">
                        <?php esc_html_e('Number of backups to keep before deleting old ones to free up storage space.', 'wp-staging'); ?>
                            <?php if (!$isProVersion) {
                                echo '<br><br><hr>';
                                echo esc_html__('Keep more than one automatic backup with WP Staging Pro!', 'wp-staging'); ?>
                                <a href="<?php echo esc_url(Language::getUpgradeUrl('backup_schedule_retention')); ?>" target="_blank" class="wpstg-btn wpstg-btn-sm wpstg-btn-pro-soft wpstg-mt-2"><?php echo esc_html__('Get Pro Version', 'wp-staging'); ?></a>
                            <?php } ?>
                    </span>
                </div>
            </div>
            <div class="wpstg-flex wpstg-w-full">
                <select name="backupScheduleRotation" id="backupScheduleRotation" class="!wpstg-w-full dark:!wpstg-border-slate-600 dark:!wpstg-text-slate-100 dark:focus:!wpstg-border-blue-500">
                    <?php for ($i = 1; $i <= 10; $i++) : ?>
                        <option value="<?php echo esc_attr((string)$i); ?>"
                                <?php echo $isProVersion ? '' : ($i === 1 ? 'selected' : 'disabled'); ?>>
                                <?php
                                    // phpcs:ignore WordPress.WP.I18n.MismatchedPlaceholders,WordPress.WP.I18n.MissingSingularPlaceholder -- Singular text intentionally omits the count.
                                    echo esc_html(sprintf(_n('Keep latest backup', 'Keep last %d backups', $i, 'wp-staging'), (int)$i));
                                ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
    </div>
    <div>
        <label for="backupScheduleLaunch" class="wpstg-form-label">
            <input type="checkbox" class="wpstg-mt-1 wpstg-checkbox" id="backupScheduleLaunch" name="backupScheduleLaunch" value="">
            <span class="wpstg-block"><?php esc_html_e('Run a backup immediately after saving this schedule', 'wp-staging'); ?></span>
        </label>
    </div>
</div>
