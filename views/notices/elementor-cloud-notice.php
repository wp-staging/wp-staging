<div class="notice notice-warning wpstg-elementor-cloud-notice">
    <p>
        <?php
        esc_html_e('This website runs on Elementor Cloud, which doesn\'t support the staging feature due to technical limitations. ', 'wp-staging');
        echo sprintf(
            esc_html__('Instead, you can use the WP Staging %s to create a staging site on a separate host.', 'wp-staging'),
            '<a href="' . esc_url(admin_url('admin.php?page=wpstg_backup')) . '" rel="noopener">' . esc_html__('backup feature', 'wp-staging') . '</a>'
        );
        ?>
    </p>
    <p>
        <?php
        echo sprintf(
            esc_html__('Read %s on how to create a staging site on another server.', 'wp-staging'),
            '<a href="https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/" target="_blank" rel="noopener">' . esc_html__('this article', 'wp-staging') . '</a>'
        );
        ?>
    </p>
</div>
