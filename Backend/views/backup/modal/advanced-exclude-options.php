<?php

/**
 * @var Times $time
 * @var DateTimeImmutable[] $recurrenceTimes
 * @var string $disabledProAttribute
 * @var string $timeFormatOption
 * @var string $urlAssets
 * @var bool $isProVersion
 * @var bool $hasSchedule
 */

?>
<div class="hidden" data-show-if-checked="wpstgSmartExclusion">
    <label>
        <input type="checkbox" class="wpstg-checkbox" name="advancedExclusions[]" id="wpstgExcludeLogs" <?php echo esc_attr($disabledProAttribute) ?>/>
        <span class="<?php echo esc_attr($disabledClass) ?>">
        <?php esc_html_e('Exclude log files', 'wp-staging'); ?>
        </span>
    </label>
    <label>
        <input type="checkbox" class="wpstg-checkbox" name="advancedExclusions[]" id="wpstgExcludeCaches" <?php echo esc_attr($disabledProAttribute) ?>/>
        <span class="<?php echo esc_attr($disabledClass) ?>">
        <?php esc_html_e('Exclude cache files', 'wp-staging'); ?>
        </span>
    </label>
    <label>
        <input type="checkbox" class="wpstg-checkbox" name="advancedExclusions[]" id="wpstgExcludePostRevision" <?php echo esc_attr($disabledProAttribute) ?>/>
        <span class="<?php echo esc_attr($disabledClass) ?>">
        <?php esc_html_e('Exclude post revisions', 'wp-staging'); ?>
        </span>
    </label>
    <label>
        <input type="checkbox" class="wpstg-checkbox" name="advancedExclusions[]" id="wpstgExcludeSpamComments" <?php echo esc_attr($disabledProAttribute) ?>/>
        <span class="<?php echo esc_attr($disabledClass) ?>">
        <?php esc_html_e('Exclude spam comments', 'wp-staging'); ?>
        </span>
    </label>
    <label>
        <input type="checkbox" class="wpstg-checkbox" name="advancedExclusions[]" id="wpstgExcludeUnusedThemes" <?php echo esc_attr($disabledProAttribute) ?>/>
        <span class="<?php echo esc_attr($disabledClass) ?>">
        <?php esc_html_e('Exclude unused themes', 'wp-staging'); ?>
        </span>
    </label>
    <label>
        <input type="checkbox" class="wpstg-checkbox" name="advancedExclusions[]" id="wpstgExcludeDeactivatedPlugins" <?php echo esc_attr($disabledProAttribute) ?>/>
        <span class="<?php echo esc_attr($disabledClass) ?>">
        <?php esc_html_e('Exclude deactivated plugins', 'wp-staging'); ?>
        </span>
    </label>
</div>
