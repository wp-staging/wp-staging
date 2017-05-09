<div class="error">
    <p>
        <strong>
        <?php echo sprintf(__('WP Staging Folder Permission error:</strong>
        %1$s is not write and/or readable.
        <br>
        Check if the folder <strong>%1$s</strong> is writeable by php user www-data.
        File permissions should be chmod 755 or 777.','wpstg'), get_home_path() );
        ?>
    </p>
</div>