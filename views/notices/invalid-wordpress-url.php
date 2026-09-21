<?php

/**
 * Guides administrators to repair invalid WordPress URL options.
 */

use WPStaging\Framework\Facades\Escape;

?>
<div class="notice notice-error wpstg-invalid-wordpress-url-notice" id="wpstg-invalid-wordpress-url-notice">
    <p><strong><?php esc_html_e('WP STAGING found an invalid WordPress Home or Site URL.', 'wp-staging'); ?></strong></p>
    <p>
        <?php if (is_multisite()) : ?>
            <?php esc_html_e('Ask your network administrator to correct the WordPress Home and Site URLs for this site. Both must include http:// or https://.', 'wp-staging'); ?>
        <?php else : ?>
            <?php echo sprintf(
                Escape::escapeHtml(__('Open <a href="%s" target="_blank" rel="noopener noreferrer">Settings > General</a> and enter complete URLs, including http:// or https://, for WordPress Address (URL) and Site Address (URL).', 'wp-staging')),
                esc_url('options-general.php')
            ); ?>
        <?php endif; ?>
    </p>
    <p><?php esc_html_e('If WP_HOME or WP_SITEURL is defined in wp-config.php, correct the URL there or ask your site administrator for help.', 'wp-staging'); ?></p>
</div>
