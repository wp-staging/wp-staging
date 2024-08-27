<div class="notice notice-warning is-dismissible">
    <p><strong><?php echo esc_html__('WP STAGING', 'wp-staging') ?></strong></p>
    <p><?php echo
        wp_kses_post(
            sprintf(
                /* translators: URL to edit WPSTAGING settings. */
                __('WP STAGING will not send diagnostic usage information. If you want an improved technical support experience, you can always re-enable this on the <a href="%s">WP STAGING Settings</a> page.', 'wp-staging'),
                esc_url(admin_url('admin.php?page=wpstg-settings'))
            )
        )
        ?></p>
    <p><?php echo esc_html__('Usage diagnostic monitoring allows us to provide a better support, as we can debug issues faster when you reach out to our support.', 'wp-staging') ?></p>
</div>
