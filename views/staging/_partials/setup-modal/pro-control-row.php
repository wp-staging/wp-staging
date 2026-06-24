<?php

/**
 * Renders a read-only Pro-control row for locked setup options.
 *
 * @var \WPStaging\Staging\Renderer\SetupRenderer $renderer
 * @var string                                    $name
 * @var bool                                      $checked
 * @var string                                    $label
 * @var string                                    $description
 * @var string                                    $statusLabel
 * @var string                                    $badgeLabel
 */

if (empty($badgeLabel)) {
    $badgeLabel = __('Pro control', 'wp-staging');
}
?>
<div class="wpstg-create-pro-row">
    <span class="wpstg-create-pro-row__input">
        <?php \WPStaging\Framework\Facades\UI\Checkbox::render($name, $name, 'true', $checked, ['usePrimitive' => true, 'isDisabled' => true]); ?>
    </span>
    <span class="wpstg-create-pro-row__copy">
        <strong><?php echo esc_html($label); ?></strong>
        <span><?php echo esc_html($description); ?></span>
    </span>
    <span class="wpstg-create-pro-row__meta">
        <?php if (!empty($statusLabel)) : ?>
            <span class="wpstg-create-pro-row__status"><?php echo esc_html($statusLabel); ?></span>
        <?php endif; ?>
        <span class="wpstg-badge-amber"><?php $renderer->icon('lock', 'wpstg-h-3 wpstg-w-3'); ?><?php echo esc_html($badgeLabel); ?></span>
    </span>
</div>
