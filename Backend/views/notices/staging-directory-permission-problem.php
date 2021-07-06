<div class="notice notice-error">
    <p>
        <strong>
        <?php
        echo sprintf(__('WP STAGING - Folder Permission error.</strong>
        <br>
        The folder <code>%1$s</code> is not write and/or readable.
        <br>
        Check if this folder is writeable by php user %2$s or www-data .
        File permissions should be chmod 755 or 777.', 'wp-staging'), ABSPATH, getenv('USERNAME') ?: getenv('USER'));
        ?>
    </p>
</div>