<div class="error">
    <p>
        <strong>WP Staging Folder Permission error: </strong>
        <?php echo ABSPATH . "wp-content/uploads/" . WPStaging::SLUG?>
        is not write and/or readable.
        <br>
        Check if the folder <strong><?php echo ABSPATH . "wp-content/uploads/" . WPStaging::SLUG?></strong> exists!
        File permissions should be chmod 755 or 777.
    </p>
</div>