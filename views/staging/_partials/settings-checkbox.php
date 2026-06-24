<?php

/**
 * @var string $name
 * @var string $label
 * @var string $value
 * @var bool   $checked
 * @var bool   $disabled
 */

use WPStaging\Framework\Facades\UI\Checkbox;

?>

<div class="wpstg-advanced-settings-row wpstg-flex wpstg-items-start wpstg-gap-3 wpstg-border-0 wpstg-border-b wpstg-border-solid wpstg-border-slate-200 wpstg-bg-white wpstg-px-4 wpstg-py-3 dark:wpstg-border-slate-700 dark:wpstg-bg-slate-900/60 wpstg-form-group">
    <label for="<?php echo esc_attr($name) ?>" class="wpstg-min-w-0 wpstg-flex-1 wpstg-cursor-pointer">
        <span class="wpstg-block wpstg-text-sm wpstg-font-bold wpstg-leading-5 wpstg-text-[#001b3d] dark:wpstg-text-slate-100"><?php echo esc_html($label); ?></span>
    </label>
    <div class="wpstg-mt-0.5 wpstg-flex wpstg-flex-shrink-0 wpstg-items-center">
        <?php Checkbox::render($name, $name, $value, $checked, ['isDisabled' => $disabled, 'usePrimitive' => true]); ?>
    </div>
</div>
