<?php

use WPStaging\Framework\Facades\Escape;

?>
<div class="notice notice-error">
    <p>
        <strong><?php esc_html_e('WP STAGING - HTTP/HTTPS Scheme Error.', 'wp-staging'); ?></strong>
        <br>
        <?php echo sprintf(
            Escape::escapeHtml(__('Go to <a href="%s" target="_blank">Settings > General</a> and make sure that WordPress Address (URL) and Site Address (URL) both start wth either http or https scheme.'), 'wp-staging'),
            esc_url(admin_url()) . 'options-general.php'
        ); ?>
        <br>
        <?php esc_html_e('Otherwise your staging site will not be reachable after creation.', 'wp-staging'); ?></p>
</div>
