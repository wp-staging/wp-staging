<div class="notice notice-warning">
    <p>
        <strong><?php esc_html_e('WP STAGING:', 'wp-staging'); ?></strong>
        <?php echo sprintf(__('You have symlinked the folder <code>%s</code> on this staging site. If you update images on this site it will affect your live site. 
        This feature should be used only if you know what you do! 
        If you did this unintentionally, create a new staging site and disable the symlink option.', 'wp-staging'), esc_html__(wp_upload_dir()['path'], 'wp-staging'))?>
    </p>
</div>