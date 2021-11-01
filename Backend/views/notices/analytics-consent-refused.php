<div class="notice notice-warning is-dismissible">
    <p><strong><?php echo esc_html__('WP STAGING', 'wp-staging') ?></strong></p>
    <p><?php echo wp_kses_post(__(sprintf('WP STAGING will not send usage information about how you use the plugin. If you want an improved technical support experience, you can always re-enable this on <a href="%s">WP STAGING Settings</a> page.', esc_url(admin_url('admin.php?page=wpstg-settings'))), 'wp-staging')) ?></p>
    <p><?php echo esc_html__('Usage information allows us to provide a better support, as we can debug your issue faster when you reach out to our support.', 'wp-staging') ?></p>
</div>
