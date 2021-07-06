<div class="notice notice-error">
    <p>
        <strong><?php _e('WP STAGING - HTTP/HTTPS Scheme Error.', 'wp-staging'); ?></strong>
        <br>
        <?php echo sprintf(__('Go to <a href="%s" target="_blank">Settings > General</a> and make sure that WordPress Address (URL) and Site Address (URL) both start wth either http or https scheme.', 'wp-staging'), admin_url() . 'options-general.php'); ?>
        <br>
        <?php _e('Otherwise your staging site will not be reachable after creation.', 'wp-staging'); ?></p>
</div>