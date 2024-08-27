<?php
/**
 * @var \WPStaging\Framework\Adapter\Directory $directory
 */

try {
    $baseDirectory = \WPStaging\Core\WPStaging::make(\WPStaging\Backup\Service\BackupsFinder::class)->getBackupsDirectory();
} catch (\Exception $e) { // TODO: remove the double catch and switch with Throwable when the support of php 5.6 is dropped!
    ob_end_clean();
    if (wp_doing_ajax()) {
        wp_send_json_error($e->getMessage());
    }
} catch (\Error $e) {
    ob_end_clean();
    if (wp_doing_ajax()) {
        wp_send_json_error($e->getMessage());
    }
}

?>
<div
    id="wpstg--modal--backup--restore"
    data-confirmButtonText="<?php esc_attr_e('RESTORE', 'wp-staging'); ?>"
    data-nextButtonText="<?php esc_attr_e('NEXT', 'wp-staging'); ?>"
    data-cancelButtonText="<?php esc_attr_e('CANCEL', 'wp-staging'); ?>"
    data-baseDirectory="<?php echo esc_attr($baseDirectory); ?>"
    style="display: none"
>
    <h2 class="wpstg--modal--backup--restore--title wpstg--grey"><?php esc_html_e('Restore Backup', 'wp-staging') ?></h2>
    <div style="padding: .75em; margin: 1em auto;">
        <?php include(__DIR__ . '/partials/restore-introduction.php'); ?>
        <?php include(__DIR__ . '/partials/restore-database-search-replace.php'); ?>
    </div>
</div>
