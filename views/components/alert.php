<?php

/**
 * Alert component.
 * @var string $title
 * @var string $desc
 * @var string $buttonText
 * @var string $buttonUrl
 * @var bool $closeable
 * @var bool $visible
 *
 * @package WPStaging\Component
 * @see \WPStaging\Component\Alert::render()
 */
?>
<div class="wpstg-banner" style="display: <?php echo $visible ? 'block' : 'none'; ?>;">
    <div class="wpstg-banner-content">
        <div class="wpstg-banner-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path fill="none" d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line  x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>
        <div class="wpstg-banner-text">
            <?php if (!empty($title)) : ?>
                <h3 class="wpstg-banner-title">
                    <?php echo esc_html($title); ?>
                </h3>
            <?php endif; ?>

            <?php if (!empty($desc)) : ?>
                <p class="wpstg-banner-description">
                    <?php echo wp_kses_post($desc); ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($buttonText)) : ?>
                <?php
                $url = !empty($buttonUrl) ? esc_url($buttonUrl) : '#';
                ?>
                <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" class="wpstg-button danger wpstg-banner-button">
                    <?php echo esc_html($buttonText); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($closeable) : ?>
        <div class="wpstg-banner-close" title="<?php esc_attr_e('Close', 'wp-staging'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wpstg-banner-close-icon"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </div>
    <?php endif; ?>
</div>
