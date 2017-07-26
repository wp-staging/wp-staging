<div class="error">
    <p>
        <strong>WP Staging Folder Permission error: </strong>
        <?php echo \WPStaging\WPStaging::getContentDir()?>
        is not write and/or readable.
        <br>
        Check if the folder <strong><?php echo \WPStaging\WPStaging::getContentDir()?></strong> exists!
        File permissions should be chmod 755 or 777.
    </p>
</div>