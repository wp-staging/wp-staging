<?php

/**
 * @see \WPStaging\Framework\Notices\Notices::renderNotices
 */

?>
<div class="notice notice-warning wpstg-entire-clone-server-config-notice">
    <p>
        <strong><?php esc_html_e('WP STAGING: All-In-One Security (AIOS) Salt Prefix Enabled', 'wp-staging'); ?></strong> <br/>
        <?php
            esc_html_e("You enabled the salt postfix option in WP Security options. Due to that WP Staging could not exclude WP Security from being executed while backups are created or during push and pull operations. If you want to increase WP Staging speed and reliability during processing, we recommend to disable the salt option in WP Security.", "wp-staging");
        ?>
    </p>
</div>
