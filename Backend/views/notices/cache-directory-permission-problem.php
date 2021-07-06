<?php
/**
 * @var string $cacheDir
 * @see \WPStaging\Backend\Notices\Notices::messages
 */
?>
<div class="notice notice-error">
    <p><strong>WP STAGING - Folder Permission error. </strong>
        <br>
        The folder <code><?php echo $cacheDir; ?></code> is not write and/or readable.
        <br>
        Check if this folder exists! Folder permissions should be chmod 755 or higher.
    </p>
</div>
