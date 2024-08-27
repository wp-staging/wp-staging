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

use WPStaging\Framework\Facades\UI\Checkbox;

$isAdvanceCheckboxDisabled = ($disabledProAttribute === ' disabled');
?>
<div class="hidden" data-show-if-checked="wpstgSmartExclusion">
    <label>
        <?php Checkbox::render('wpstgExcludeLogs', 'advancedExclusions[]', '', false, ['isDisabled' => $isAdvanceCheckboxDisabled]); ?>
        <span class="<?php echo esc_attr($disabledClass) ?>">
        <?php esc_html_e('Exclude log files', 'wp-staging'); ?>
        </span>
    </label>
    <label>
        <?php Checkbox::render('wpstgExcludeCaches', 'advancedExclusions[]', '', false, ['isDisabled' => $isAdvanceCheckboxDisabled]); ?>
        <span class="<?php echo esc_attr($disabledClass) ?>">
        <?php esc_html_e('Exclude cache files', 'wp-staging'); ?>
        </span>
    </label>
    <label>
        <?php Checkbox::render('wpstgExcludePostRevision', 'advancedExclusions[]', '', false, ['isDisabled' => $isAdvanceCheckboxDisabled]); ?>
        <span class="<?php echo esc_attr($disabledClass) ?>">
        <?php esc_html_e('Exclude post revisions', 'wp-staging'); ?>
        </span>
    </label>
    <label>
        <?php Checkbox::render('wpstgExcludeSpamComments', 'advancedExclusions[]', '', false, ['isDisabled' => $isAdvanceCheckboxDisabled]); ?>
        <span class="<?php echo esc_attr($disabledClass) ?>">
        <?php esc_html_e('Exclude spam comments', 'wp-staging'); ?>
        </span>
    </label>
    <label>
        <?php Checkbox::render('wpstgExcludeUnusedThemes', 'advancedExclusions[]', '', false, ['isDisabled' => $isAdvanceCheckboxDisabled]); ?>
        <span class="<?php echo esc_attr($disabledClass) ?>">
        <?php esc_html_e('Exclude unused themes', 'wp-staging'); ?>
        </span>
    </label>
    <label>
        <?php Checkbox::render('wpstgExcludeDeactivatedPlugins', 'advancedExclusions[]', '', false, ['isDisabled' => $isAdvanceCheckboxDisabled]); ?>
        <span class="<?php echo esc_attr($disabledClass) ?>">
        <?php esc_html_e('Exclude deactivated plugins', 'wp-staging'); ?>
        </span>
    </label>
</div>