<?php
/**
 * @var \WPStaging\Framework\Adapter\Directory $directory
 */
?>
<div
    id="wpstg--modal--backup--import"
    data-confirmButtonText="<?php esc_attr_e('IMPORT', 'wp-staging'); ?>"
    data-nextButtonText="<?php esc_attr_e('NEXT', 'wp-staging'); ?>"
    data-cancelButtonText="<?php esc_attr_e('CANCEL', 'wp-staging'); ?>"
    data-baseDirectory="<?php echo esc_attr($directory->getPluginUploadsDirectory()); ?>"
    style="display: none"
>
    <h2 class="wpstg--modal--backup--import--upload--title"><?php esc_html_e('Import Backup', 'wp-staging') ?></h2>
    <div style="padding: .75em; margin: 1em auto;">
        <?php include(__DIR__ . '/partials/import-upload.php'); ?>
        <?php include(__DIR__ . '/partials/import-filesystem.php'); ?>
        <?php include(__DIR__ . '/partials/import-configure.php'); ?>
    </div>
</div>
