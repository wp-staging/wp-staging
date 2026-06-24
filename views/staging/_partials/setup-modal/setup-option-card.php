<?php

/**
 * Renders a checkbox option card inside setup modals.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var string                                    $name
 * @var string                                    $label
 * @var string                                    $description
 * @var bool                                      $checked
 * @var array                                     $checkboxOptions
 * @var string                                    $tooltip
 * @var bool                                      $proLocked
 */

$isOptionDisabled = !empty($checkboxOptions['isDisabled']);
$cardClasses      = 'wpstg-create-option-card';
if ($isOptionDisabled) {
    $cardClasses .= ' wpstg-create-option-card--disabled';
}

if ($proLocked) {
    $cardClasses .= ' wpstg-create-option-card--pro';
}
?>
<label class="<?php echo esc_attr($cardClasses); ?>" for="<?php echo esc_attr($name); ?>">
    <?php \WPStaging\Framework\Facades\UI\Checkbox::render($name, $name, 'true', $checked, array_merge(['usePrimitive' => true], $checkboxOptions)); ?>
    <span class="wpstg-create-option-card__copy">
        <span class="wpstg-create-option-card__label">
            <strong><?php echo esc_html($label); ?></strong>
            <?php if ($proLocked) : ?>
                <span class="wpstg-badge-amber"><?php $renderer->icon('lock', 'wpstg-h-3 wpstg-w-3'); ?><?php esc_html_e('Pro', 'wp-staging'); ?></span>
            <?php endif; ?>
        </span>
        <span><?php echo esc_html($description); ?></span>
    </span>
    <?php if (!empty($tooltip)) : ?>
        <span class="wpstg--tooltip wpstg-ml-auto wpstg-mt-0.5 wpstg-flex wpstg-h-5 wpstg-w-5 wpstg-flex-shrink-0 wpstg-items-center wpstg-justify-center">
            <span class="dashicons dashicons-info-outline wpstg-text-[#a8b5c6]" aria-hidden="true"></span>
            <span class="wpstg--tooltiptext"><?php echo wp_kses_post($tooltip); ?></span>
        </span>
    <?php endif; ?>
</label>
