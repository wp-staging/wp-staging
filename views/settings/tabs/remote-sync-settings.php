<?php

/**
 * Connection Keys Settings Tab (Free Version)
 *
 * Shows an upgrade callout directing users to WP Staging Pro
 * for the Remote Sync feature. The Pro version of this view
 * lives at views/pro/settings/tabs/remote-sync-settings.php.
 */

?>

<div class="wpstg-remote-sync-settings wpstg-max-w-3xl wpstg-py-2">

    <!-- Header -->
    <header class="wpstg-mb-8">
        <h1 class="wpstg-text-2xl wpstg-font-bold wpstg-tracking-tight wpstg-text-gray-900 dark:wpstg-text-gray-100 wpstg-mb-2 wpstg-m-0">
            <?php esc_html_e('Connection Keys', 'wp-staging'); ?>
        </h1>
        <nav class="wpstg-flex wpstg-items-center wpstg-gap-1 wpstg-text-sm wpstg-text-gray-500 dark:wpstg-text-gray-400" aria-label="<?php echo esc_attr__('Breadcrumb', 'wp-staging'); ?>">
            <span class="hover:wpstg-text-gray-900 dark:hover:wpstg-text-gray-200 wpstg-transition-colors"><?php esc_html_e('WP Staging', 'wp-staging'); ?></span>
            <svg class="wpstg-h-4 wpstg-w-4 wpstg-opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="hover:wpstg-text-gray-900 dark:hover:wpstg-text-gray-200 wpstg-transition-colors"><?php esc_html_e('Settings', 'wp-staging'); ?></span>
            <svg class="wpstg-h-4 wpstg-w-4 wpstg-opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="wpstg-font-medium wpstg-text-gray-900 dark:wpstg-text-gray-100"><?php esc_html_e('Connection Keys', 'wp-staging'); ?></span>
        </nav>
    </header>

    <!-- Upgrade Callout -->
    <div class="wpstg-callout wpstg-callout-info wpstg-mb-6">
        <div class="wpstg-icon-box wpstg-icon-box-blue">
            <svg class="wpstg-h-5 wpstg-w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <div class="wpstg-flex-1">
            <h3 class="wpstg-heading-lg">
                <?php esc_html_e('Unlock Remote Sync', 'wp-staging'); ?>
            </h3>
            <p class="wpstg-text-body wpstg-text-sm wpstg-m-0">
                <?php esc_html_e('Pull a WordPress site from another server using a secure connection key.', 'wp-staging'); ?>
            </p>
            <p class="wpstg-text-body wpstg-text-sm wpstg-m-0 wpstg-mb-4">
                <?php esc_html_e('Remote Sync is available with a WP Staging Developer or Agency plan.', 'wp-staging'); ?>
            </p>
            <a href="https://wp-staging.com/get-developer-plan" target="_blank" rel="noopener noreferrer" class="wpstg-btn wpstg-btn-md wpstg-btn-primary wpstg-no-underline">
                <?php esc_html_e('Unlock Remote Sync', 'wp-staging'); ?>
                <svg class="wpstg-btn-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>
    </div>

</div>
