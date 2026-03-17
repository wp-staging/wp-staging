<?php

/**
 * @see \WPStaging\Backup\Ajax\Listing::render
 */

use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Language\Language;

?>

<span class="wpstg-notice-alert">
    <?php echo sprintf(
        Escape::escapeHtml(__('The free version of WP Staging Backup Plugin does not support WordPress Multisite. You can consider upgrading to the <a href="%s" target="_blank">pro version</a> as needed.', 'wp-staging')),
        esc_url(Language::localizePricingUrl('https://wp-staging.com/#pricing'))
    ); ?>
</span>

<div id="wpstg-step-1" class="wpstg-flex wpstg-flex-wrap wpstg-items-center wpstg-gap-3 wpstg-mb-6">
    <!-- Primary: Create Backup -->
    <button
        type="button"
        id="wpstg-new-backup"
        class="wpstg-btn wpstg-btn-lg wpstg-btn-primary"
        disabled
    >
        <svg class="wpstg-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <?php esc_html_e('Create Backup', 'wp-staging'); ?>
    </button>

    <!-- Secondary: Upload Backup -->
    <button
        type="button"
        id="wpstg-upload-backup"
        class="wpstg-btn wpstg-btn-lg wpstg-btn-secondary"
        disabled
    >
        <svg class="wpstg-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
        </svg>
        <?php esc_html_e('Upload Backup', 'wp-staging'); ?>
    </button>

    <!-- Secondary: Manage Plans -->
    <button
        type="button"
        id="wpstg-manage-backup-schedules"
        class="wpstg-btn wpstg-btn-lg wpstg-btn-secondary"
        disabled
    >
        <svg class="wpstg-btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
        </svg>
        <?php esc_html_e('Manage Plans', 'wp-staging'); ?>
    </button>
</div>
