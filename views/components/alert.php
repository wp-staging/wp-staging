<?php

/**
 * Alert component.
 * @var string $title
 * @var string $desc
 * @var string $buttonText
 * @var string $buttonUrl
 * @var bool $closeable
 * @var string $id
 * @var string $style
 * @var string $class
 * @var string $variant info|success|warning|danger
 *
 * @package WPStaging\Component
 * @see \WPStaging\Component\Alert::render()
 */
?>
<div
    <?php if (!empty($id)) : ?>
        id="<?php echo esc_attr($id); ?>"
    <?php endif; ?>
    class="wpstg-callout wpstg-callout-<?php echo esc_attr($variant); ?> wpstg-mt-4 wpstg-mb-2 <?php echo esc_attr($class); ?>"
    style="<?php echo esc_attr($style); ?>">
    <?php if ($variant === 'success') : ?>
        <svg class="wpstg-w-5 wpstg-h-5 wpstg-flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path>
        </svg>
    <?php elseif ($variant === 'info') : ?>
        <svg class="wpstg-w-5 wpstg-h-5 wpstg-flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path>
        </svg>
    <?php elseif ($variant === 'warning') : ?>
        <svg class="wpstg-w-5 wpstg-h-5 wpstg-flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path>
        </svg>
    <?php else : ?>
        <svg class="wpstg-w-5 wpstg-h-5 wpstg-flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle><path d="m15 9-6 6"></path><path d="m9 9 6 6"></path>
        </svg>
    <?php endif; ?>
    <div class="wpstg-flex-1 wpstg-min-w-0">
        <span class="wpstg-banner-text"></span>
        <?php if (!empty($title)) : ?>
            <p class="wpstg-m-0 wpstg-font-semibold">
                <?php echo esc_html($title); ?>
            </p>
        <?php endif; ?>
        <?php if (!empty($desc)) : ?>
            <p class="wpstg-m-0 <?php echo !empty($title) ? 'wpstg-mt-1' : ''; ?>">
                <?php echo wp_kses_post($desc); ?>
            </p>
        <?php endif; ?>
        <?php if (!empty($buttonText)) : ?>
            <?php $url = !empty($buttonUrl) ? esc_url($buttonUrl) : '#'; ?>
            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" class="wpstg-button danger wpstg-banner-button wpstg-mt-2">
                <?php echo esc_html($buttonText); ?>
            </a>
        <?php endif; ?>
    </div>
    <?php if ($closeable) : ?>
        <button type="button" class="wpstg-banner-close" title="<?php esc_attr_e('Close', 'wp-staging'); ?>" aria-label="<?php esc_attr_e('Close', 'wp-staging'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 6 6 18"/><path d="m6 6 12 12"/>
            </svg>
        </button>
    <?php endif; ?>
</div>
