<?php
/**
 * @var string $logsDir
 * @see \WPStaging\Backend\Notices\Notices::messages
 */
?>
<div class="wpstg-error">
    <p>
        <strong>WP Staging Folder Permission error: </strong> <?php echo $logsDir; ?> is not write and/or readable.
        <br>
        Check if the folder <strong><?php echo $logsDir; ?></strong> exists! Folder permissions should be chmod 755 or higher.
    </p>
</div>
