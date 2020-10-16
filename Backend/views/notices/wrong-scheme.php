<div class="wpstg-error">
    <p>
        <strong><?php _e( 'WP Staging HTTP/HTTPS Scheme Error:', 'wp-staging' ); ?></strong> 
        <?php echo sprintf(__( 'Go to <a href="%s" target="_blank">Settings > General</a> and make sure that WordPress Address (URL) and Site Address (URL) do both contain the same http / https scheme.', 'wp-staging' ), admin_url() . 'options-general.php'); ?> 
        <br>
        <?php _e( 'Otherwise your staging site will not be reachable after creation.', 'wp-staging' ); ?></p>
</div>