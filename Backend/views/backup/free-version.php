<?php

/**
 * @see \WPStaging\Pro\Backup\Ajax\Listing::render
 */

?>

<div id="wpstg-free-version-backups">
    <ul>
        <li class="wpstg-clone wpstg-dark-alert">
            <p><strong><?php esc_html_e('Backup & Migration is a PRO feature!', 'wp-staging'); ?></strong></p>
            <p><?php _e('<a href="https://wp-staging.com/?utm_source=wp-admin&utm_medium=wp-admin&utm_campaign=backup-restore&utm_term=backup-restore" target="_blank" id="wpstg-button-backup-upgrade" class="wpstg-button--primary wpstg-button--cta-red wpstg-border--violet">Unlock</a>', 'wp-staging'); ?></p>
        </li>
    </ul>
</div>

<div id="wpstg-did-you-know" style="margin-bottom:12px">
    <strong><?php echo sprintf(__('Did you know? You can upload backup files to another website to transfer it. <a href="%s" target="_blank">Read more</a>', 'wp-staging'), 'https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/'); ?></strong>
</div>

<div id="wpstg-step-1">
    <button id="wpstg-new-backup" class="wpstg-next-step-link wpstg-blue-primary wpstg-button" disabled>
        <?php esc_html_e('Create New Backup', 'wp-staging') ?>
    </button>
    <button id="wpstg-upload-backup" class="wpstg-next-step-link wpstg-blue-primary wpstg-button wpstg-ml-4" disabled>
        <?php esc_html_e('Upload Backup', 'wp-staging') ?>
    </button>
    <button id="wpstg-manage-backup-schedules" class="wpstg-next-step-link wpstg-blue-primary wpstg-button wpstg-ml-4" disabled>
        <?php esc_html_e('Edit Backup Plans', 'wp-staging') ?>
    </button>
</div>
