<?php

use WPStaging\Framework\Facades\Escape;

?>
<div class="notice notice-error" id="wpstg-corrupt-settings-notice">
    <p>
        <strong><?php esc_html_e('WP STAGING - Settings Error.', 'wp-staging'); ?></strong>
        <br>
        <?php echo Escape::escapeHtml(__('The WP STAGING settings are broken! Use the link below to restore the default settings. <strong>Note:</strong> This will remove all entries from the list of staging sites but the staging sites will still be available and not physically deleted. If you are unsure about this, please contact us at support@wp-staging.com before restoring the settings.', 'wp-staging')); ?>
        <br>
        <a href="javascript:void(0);" id="wpstg-link-restore-settings" title="<?php esc_html_e('Restore Settings', 'wp-staging') ?>">
            <?php esc_html_e('Restore Settings', 'wp-staging') ?>
        </a>
    </p>
</div>
<script type="text/javascript" src="<?php echo esc_url($this->assets->getAssetsUrlWithVersion("js/dist/wpstg-admin-corrupt-settings.js")) ?>"></script>
