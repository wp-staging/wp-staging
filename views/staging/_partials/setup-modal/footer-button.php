<?php

/**
 * Renders a setup modal footer button.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var string                                    $label
 * @var string                                    $class
 * @var string                                    $variant
 * @var string                                    $icon
 * @var string                                    $iconPosition
 * @var array                                     $attributes
 */

if ($variant === 'primary' && empty($icon)) {
    $icon = 'play';
}

$variantClass = $variant === 'primary' ? 'wpstg-btn-primary wpstg-px-6' : 'wpstg-btn-secondary wpstg-px-5';
$attributes   = array_merge([
    'type'  => 'button',
    'class' => trim('wpstg-btn wpstg-btn-md wpstg-h-11 wpstg-rounded-lg wpstg-py-0 wpstg-leading-none ' . $variantClass . ' ' . $class),
], $attributes);
?>
<button<?php $renderer->attributes($attributes); ?>>
    <?php if (!empty($icon) && $iconPosition === 'start') : ?>
        <?php $renderer->icon($icon, 'wpstg-btn-icon-sm'); ?>
    <?php endif; ?>
    <?php echo esc_html($label); ?>
    <?php if (!empty($icon) && $iconPosition === 'end') : ?>
        <?php $renderer->icon($icon, 'wpstg-btn-icon-sm'); ?>
    <?php endif; ?>
</button>
