<?php

/**
 * @var Times $time
 * @var DateTimeImmutable[] $recurrenceTimes
 * @var string $disabledProAttribute
 * @var string $timeFormatOption
 * @var string $urlAssets
 * @var string $disabledClass
 * @var bool $isProVersion
 * @var bool $hasSchedule
 */


$isAdvanceCheckboxDisabled = ($disabledProAttribute === ' disabled');
?>
<div id="wpstg-advanced-exclude-options">
    <div class="wpstg-ml-6 wpstg-mt-3 wpstg-grid wpstg-grid-cols-3 wpstg-gap-x-7 wpstg-gap-y-3 max-[640px]:wpstg-ml-0 max-[640px]:wpstg-grid-cols-1">
        <div class="wpstg-grid wpstg-gap-y-3">
            <label class="!wpstg-flex !wpstg-text-sm dark:wpstg-text-slate-200 wpstg-gap-2 wpstg-items-center">
                <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox wpstg-advanced-exclusion-child" id="wpstgExcludeLogs" name="advancedExclusions[]" value="" <?php echo $isAdvanceCheckboxDisabled ? 'disabled' : '';?> >
                <span class="<?php echo esc_attr($disabledClass); ?>">
                    <?php esc_html_e('Log files', 'wp-staging'); ?>
                </span>
            </label>
            <label class="!wpstg-flex !wpstg-text-sm dark:wpstg-text-slate-200 wpstg-gap-2 wpstg-items-center">
                <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox wpstg-advanced-exclusion-child" id="wpstgExcludeCaches" name="advancedExclusions[]" value="" <?php echo $isAdvanceCheckboxDisabled ? 'disabled' : '';?> >
                <span class="<?php echo esc_attr($disabledClass); ?>">
                    <?php esc_html_e('Cache files', 'wp-staging'); ?>
                </span>
            </label>
        </div>
        <div class="wpstg-grid wpstg-gap-y-3">
            <label class="!wpstg-flex !wpstg-text-sm dark:wpstg-text-slate-200 wpstg-gap-2 wpstg-items-center">
                <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox wpstg-advanced-exclusion-child" id="wpstgExcludePostRevision" name="advancedExclusions[]" value="" <?php echo $isAdvanceCheckboxDisabled ? 'disabled' : '';?> >
                <span class="<?php echo esc_attr($disabledClass); ?>">
                    <?php esc_html_e('Post revisions', 'wp-staging'); ?>
                </span>
            </label>
            <label class="!wpstg-flex !wpstg-text-sm dark:wpstg-text-slate-200 wpstg-gap-2 wpstg-items-center">
                <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox wpstg-advanced-exclusion-child" id="wpstgExcludeSpamComments" name="advancedExclusions[]" value="" <?php echo $isAdvanceCheckboxDisabled ? 'disabled' : '';?> >
                <span class="<?php echo esc_attr($disabledClass); ?>">
                    <?php esc_html_e('Spam comments', 'wp-staging'); ?>
                </span>
            </label>
        </div>
        <div class="wpstg-grid wpstg-gap-y-3">
            <label class="!wpstg-flex !wpstg-text-sm dark:wpstg-text-slate-200 wpstg-gap-2 wpstg-items-center">
                <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox wpstg-advanced-exclusion-child" id="wpstgExcludeUnusedThemes" name="advancedExclusions[]" value="" <?php echo $isAdvanceCheckboxDisabled ? 'disabled' : '';?> >
                <span class="<?php echo esc_attr($disabledClass); ?>">
                    <?php esc_html_e('Unused themes', 'wp-staging'); ?>
                </span>
            </label>
            <label class="!wpstg-flex !wpstg-text-sm dark:wpstg-text-slate-200 wpstg-gap-2 wpstg-items-center">
                <input type="checkbox" class="!wpstg-m-0 wpstg-checkbox wpstg-advanced-exclusion-child" id="wpstgExcludeDeactivatedPlugins" name="advancedExclusions[]" value="" <?php echo $isAdvanceCheckboxDisabled ? 'disabled' : '';?> >
                <span class="<?php echo esc_attr($disabledClass); ?>">
                    <?php esc_html_e('Deactivated plugins', 'wp-staging'); ?>
                </span>
            </label>
        </div>
    </div>
</div>
