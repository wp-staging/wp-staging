<?php

/**
 * @see \WPStaging\Backup\Ajax\Listing::render
 */

use WPStaging\Framework\Facades\Escape;

?>

<div id="wpstg-did-you-know" style="margin-bottom:12px">
    <strong><?php echo sprintf(Escape::escapeHtml(__('Get <a href="%s" target="_blank">WP Staging Pro</a> to upload and restore backups to another server to migrate this website. <a href="%s" target="_blank">Read more</a>', 'wp-staging')), 'https://wp-staging.com', 'https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/'); ?></strong>
</div>

<div id="wpstg-step-1">
    <button id="wpstg-new-backup" class="wpstg-next-step-link wpstg-blue-primary wpstg-button" disabled title="<?php esc_html_e('Premium: Create lightning-fast backups', 'wp-staging'); ?>">
        <?php esc_html_e('Create Backup', 'wp-staging') ?>
    </button>
    <button id="wpstg-upload-backup" class="wpstg-next-step-link wpstg-blue-primary wpstg-button wpstg-ml-4" disabled title="<?php esc_html_e('Premium: Upload a backup to restore or move a site', 'wp-staging'); ?>">
        <?php esc_html_e('Upload Backup', 'wp-staging') ?>
    </button>
    <button id="wpstg-manage-backup-schedules" class="wpstg-next-step-link wpstg-blue-primary wpstg-button wpstg-ml-4" disabled title="<?php esc_html_e('Premium: Create scheduled backups', 'wp-staging'); ?>">
        <?php esc_html_e('Edit Backup Plans', 'wp-staging') ?>
    </button>
</div>
