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
 * @var string                                    $context
 */

use WPStaging\Framework\Language\Language;

if (empty($badgeLabel)) {
    $badgeLabel = __('Pro', 'wp-staging');
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
        <?php if (!empty($context)) : ?>
            <a class="wpstg-badge-amber" href="<?php echo esc_url(Language::getUpgradeUrl($context)); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Requires WP STAGING Pro', 'wp-staging'); ?>"><?php $renderer->icon('lock', 'wpstg-h-3 wpstg-w-3'); ?><?php echo esc_html($badgeLabel); ?></a>
        <?php else : ?>
            <span class="wpstg-badge-amber"><?php $renderer->icon('lock', 'wpstg-h-3 wpstg-w-3'); ?><?php echo esc_html($badgeLabel); ?></span>
        <?php endif; ?>
    </span>
</div>
