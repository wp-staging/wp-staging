<?php

/**
 * Renders one runtime summary value.
 *
 * @var string $key
 * @var array  $tooltips
 * @var bool   $isProLicenseActive
 */

// Create default: Pro opens safe (Disabled/green); Free is Pro-locked and runs
// (Enabled/amber + lock). An unlicensed Pro install is treated as locked too.
$isPro      = $isProLicenseActive;
$enabled    = !$isPro;
$stateClass = $enabled ? 'wpstg-create-summary-enabled' : 'wpstg-create-summary-disabled';
$label      = $enabled ? __('Enabled', 'wp-staging') : __('Disabled', 'wp-staging');
$tooltip    = $enabled ? $tooltips['enabled'] : $tooltips['disabled'];
?>
<dd class="<?php echo esc_attr($stateClass); ?> wpstg-staging-summary-runtime" data-wpstg-create-summary-runtime="<?php echo esc_attr($key); ?>">
    <span data-wpstg-summary-runtime-label><?php echo esc_html($label); ?></span>
    <span class="wpstg--tooltip wpstg--tooltip-normal wpstg-staging-summary-tooltip" tabindex="0" aria-label="<?php echo esc_attr(wp_strip_all_tags($tooltip)); ?>">
        <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
        <span class="wpstg--tooltiptext"><?php echo wp_kses_post($tooltip); ?></span>
    </span>
</dd>
