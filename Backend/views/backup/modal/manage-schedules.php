<?php

/**
 * @var \WPStaging\Framework\Adapter\Directory $directory
 * @var string $urlAssets
 */
$schedules = WPStaging\Core\WPStaging::make(\WPStaging\Backup\BackupScheduler::class)->getSchedules();
?>
<div
    id="wpstg--modal--backup--manage--schedules"
    data-cancelButtonText="<?php esc_attr_e('CANCEL', 'wp-staging'); ?>"
    style="display: none"
>
    <h2 class="wpstg--modal--backup--manage--schedules--title wpstg--grey">
        <?php esc_html_e('Manage Backup Plans', 'wp-staging') ?>
    </h2>
    <div id="wpstg--modal--backup--manage--schedules--content" class=""></div>
</div>
