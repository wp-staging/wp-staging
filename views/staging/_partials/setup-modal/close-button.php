<?php

/**
 * Renders a setup modal close button.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var string                                    $class
 */
?>
<button type="button" class="<?php echo esc_attr($class); ?>" aria-label="<?php esc_attr_e('Close', 'wp-staging'); ?>">
    <?php $renderer->icon('close'); ?>
</button>
