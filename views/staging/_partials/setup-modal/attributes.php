<?php

/**
 * Renders HTML attributes for setup modal fragments.
 *
 * @var array $attributes
 */

$attributeString = '';

foreach ($attributes as $name => $value) {
    if ($value === false || $value === null) {
        continue;
    }

    if ($value === true) {
        $attributeString .= ' ' . esc_attr($name);
        continue;
    }

    $attributeString .= ' ' . esc_attr($name) . '="' . esc_attr($value) . '"';
}

echo $attributeString; // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped
