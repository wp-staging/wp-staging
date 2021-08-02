<?php
/**
 * @var \WPStaging\Framework\Adapter\Directory $directory
 */
$baseDirectory = \WPStaging\Core\WPStaging::make(\WPStaging\Pro\Backup\Service\BackupsFinder::class)->getBackupsDirectory();
?>
<div
    id="wpstg--modal--backup--import"
    data-confirmButtonText="<?php esc_attr_e('RESTORE', 'wp-staging'); ?>"
    data-nextButtonText="<?php esc_attr_e('NEXT', 'wp-staging'); ?>"
    data-cancelButtonText="<?php esc_attr_e('CANCEL', 'wp-staging'); ?>"
    data-baseDirectory="<?php echo esc_attr($baseDirectory); ?>"
    style="display: none"
>
    <h2 class="wpstg--modal--backup--import--upload--title wpstg--grey"><?php esc_html_e('Restore Backup', 'wp-staging') ?></h2>
    <div style="padding: .75em; margin: 1em auto;">
        <?php include(__DIR__ . '/partials/import-introduction.php'); ?>
        <?php include(__DIR__ . '/partials/import-database-search-replace.php'); ?>
    </div>
</div>
