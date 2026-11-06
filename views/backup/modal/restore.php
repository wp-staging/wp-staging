<?php
try {
    $baseDirectory = \WPStaging\Core\WPStaging::make(\WPStaging\Backup\Service\BackupsFinder::class)->getBackupsDirectory();
} catch (\Throwable $e) {
    ob_end_clean();
    if (wp_doing_ajax()) {
        wp_send_json_error($e->getMessage());
    }
}

?>
<div
    id="wpstg--modal--backup--restore"
    data-baseDirectory="<?php echo esc_attr($baseDirectory); ?>"
    style="display: none"
>
    <div id="wpstg-restore-confirm-content"></div>
</div>
