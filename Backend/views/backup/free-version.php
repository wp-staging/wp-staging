<?php

use WPStaging\Framework\Facades\Escape;

/**
 * @see \WPStaging\Pro\Backup\Ajax\Listing::render
 */

?>

<div id="wpstg-free-version-backups">
        <div class="wpstg-clone wpstg-dark-alert">
            <span id="wpstg-premium-header"><?php esc_html_e('Backup and Migration - Go Premium!', 'wp-staging'); ?></span>
            <ul>
                <li>Create scheduled full site backups</li>
                <li>Move site to another hosting provider</li>
                <li>Store backups on Google Drive, Amazon S3 and SFTP</li>
                <li>Heavily tested code base... and many more features</li>
            </ul>
            <a href="https://wp-staging.com/?utm_source=wp-admin&utm_medium=wp-admin&utm_campaign=backup-restore&utm_term=backup-restore" target="_blank" id="wpstg-button-backup-upgrade" class="wpstg-button--primary wpstg-button--cta-red wpstg-border--violet"><?php esc_html_e('Get Started', 'wp-staging'); ?></a>
        </div>
</div>
<div id="wpstg-did-you-know" style="margin-bottom:12px">
    <strong><?php echo sprintf(
            Escape::escapeHtml(__('Did you know? You can upload a WP STAGING backup to another server to move a website. <a href="%s" target="_blank">Read more</a>', 'wp-staging')),
            'https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/'
        ); ?></strong>
</div>

<div id="wpstg-step-1">
    <button id="wpstg-new-backup" class="wpstg-next-step-link wpstg-blue-primary wpstg-button" disabled title="Premium: Create blazingly fast site backups">
        <?php esc_html_e('Create New Backup', 'wp-staging') ?>
    </button>
    <button id="wpstg-upload-backup" class="wpstg-next-step-link wpstg-blue-primary wpstg-button wpstg-ml-4" disabled title="Premium: Upload a backup file or import from another site">
        <?php esc_html_e('Upload Backup', 'wp-staging') ?>
    </button>
    <button id="wpstg-manage-backup-schedules" class="wpstg-next-step-link wpstg-blue-primary wpstg-button wpstg-ml-4" disabled title="Premium: Create scheduled backups">
        <?php esc_html_e('Edit Backup Plans', 'wp-staging') ?>
    </button>
</div>
