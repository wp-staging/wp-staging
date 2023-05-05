<?php

use WPStaging\Framework\Facades\Escape;

?>
<div class="notice notice-error">
    <p>
        <strong>
            <?php
            echo sprintf(
                Escape::escapeHtml(__('WP Staging - Folder Permissions:</strong>
                <br>
                The folder <code>%1$s</code> is not write and/or readable.
                <br>
                Check if this folder is writeable by the php user %2$s or www-data.
                File permissions needs to be chmod 755 or 777.
                <br>
                <br>
                WP Staging\'s default behavior is to create a staging site in a subfolder of the live site.
                If you get this warning this will not be possible, but you can still create a staging site.
                <a href="%3$s" target="_blank">Create a staging site without write permission to root.</a>
                '), 'wp-staging'),
                esc_html(ABSPATH),
                esc_html(getenv('USERNAME')) ?: esc_html(getenv('USER')),
                esc_url('https://wp-staging.com/docs/create-staging-site-without-write-permission/')
            );
            ?>
    </p>
</div>
