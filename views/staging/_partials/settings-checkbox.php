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

<div class="wpstg--advanced-settings--checkbox">
    <label for="<?php echo esc_attr($name) ?>"><?php echo esc_html($label); ?></label>
    <?php Checkbox::render($name, $name, $value, $checked, ['isDisabled' => $disabled]); ?>
</div>
