<?php

/**
 * @var string $name
 * @var string $label
 * @var string $description
 * @var bool   $checked
 * @var bool   $disabled
 * @var string $classes
 * @var string $dataId
 * @var string $infoIcon
 */

use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Facades\UI\Checkbox;

$attributes = [
    'isDisabled' => $disabled
];

if (!empty($classes)) {
    $attributes['classes'] = $classes;
}

$dataAttributes = [];
if (!empty($dataId)) {
    $dataAttributes['id'] = $dataId;
}

?>
<div class="wpstg--advanced-settings--checkbox">
    <label for="<?php echo esc_attr($name) ?>"><?php echo esc_html($label); ?></label>
    <?php Checkbox::render($name, $name, 'true', $checked, $attributes, $dataAttributes); ?>
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo esc_url($infoIcon); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php echo Escape::escapeHtml($description); ?>
        </span>
    </span>
</div>
