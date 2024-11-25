<?php

/**
 * @var \WPStaging\Framework\Adapter\Directory $directory
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Backup\Service\BackupsFinder;

try {
    $uploadDirectory = str_replace(wp_normalize_path(ABSPATH), '', WPStaging::make(BackupsFinder::class)->getBackupsDirectory());
} catch (\Throwable $e) {
    ob_end_clean();
    if (wp_doing_ajax()) {
        wp_send_json_error($e->getMessage());
    }
}

?>
<div
    id="wpstg--modal--backup--upload"
    data-cancelButtonText="<?php esc_attr_e('CANCEL', 'wp-staging'); ?>"
    data-uploadSuccessMessage="<?php esc_attr_e('The backup file has been successfully uploaded. You can restore your website with this backup.', 'wp-staging'); ?>"
    style="display: none"
>
    <h2 class="wpstg--modal--backup--upload--title">
        <?php esc_html_e('Upload Backup', 'wp-staging') ?>
        <div class="wpstg--tooltip">
            <img class="wpstg--dashicons wpstg-dashicons-19" src="<?php echo esc_url($urlAssets); ?>svg/info-outline.svg"></img>
            <p class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                <?php esc_html_e("Upload a WP STAGING backup file (*.wpstg) and use it to restore your website at any time. This backup may have been created from this website or from another website. You can move a website in this way.", "wp-staging")?>
                <br><br>
                <?php esc_html_e("Videos:", "wp-staging")?>
                <br>
                <?php echo sprintf(
                    Escape::escapeHtml(__('&#8226; <a href="%s" target="_blank">How to backup WordPress</a>', 'wp-staging')),
                    'https://www.youtube.com/watch?v=q352aYduOUY'
                ); ?>
                <br>
                <?php echo sprintf(
                    Escape::escapeHtml(__('&#8226; <a href="%s" target="_blank">How to migrate WordPress</a>', 'wp-staging')),
                    'https://www.youtube.com/watch?v=DBaZQg1Efq4'
                ); ?>
            </p>
        </div>
    </h2>
    <div class="wpstg--modal--backup--upload--content">
        <div class="wpstg-linear-loader">
            <span class="wpstg-linear-loader-item"></span>
            <span class="wpstg-linear-loader-item"></span>
            <span class="wpstg-linear-loader-item"></span>
            <span class="wpstg-linear-loader-item"></span>
            <span class="wpstg-linear-loader-item"></span>
            <span class="wpstg-linear-loader-item"></span>
        </div>
        <?php
        /**
         * @var string $urlAssets
         */
        ?>
        <div class="wpstg--modal--backup--upload">
            <div id="wpstg-upload-select">
                <div class="wpstg--modal--backup--upload--container resumable-drop resumable-browse">
                    <img src="<?php echo esc_url($urlAssets . 'img/upload.svg'); ?>" alt="Upload Image"/>
                    <div class="wpstg-upload-text">
                        <?php
                            echo Escape::escapeHtml(__('Drop the backup file here to upload or <br><a>select from your computer</a>', 'wp-staging'));
                        ?>
                    </div>
                    <div class="wpstg-dragover-text">
                        <strong><?php echo esc_html__('Drop here to start the upload!', 'wp-staging') ?></strong>
                    </div>
                </div>
                <div class="wpstg-backup-url-container" >
                    <label for="wpstg-backup-url"><?php esc_html_e('Or upload a backup file from a URL:', 'wp-staging') ?></label>
                    <input id="wpstg-backup-url" class="wpstg--swal2-input" name="wpstg-backup-url" type="text" placeholder="https://example.com/.../backups/your-backup.wpstg"/>
                    <div class="wpstg-backup-url-container-desc"><?php esc_html_e("Get the link from Backup &#10132; Actions &#10132; Copy Backup Url.", "wp-staging") ?></div>
                </div>
                <p class="wpstg-backup-direct-upload-notice">
                    <?php esc_html_e('Or upload a backup file via FTP to:', 'wp-staging') ?><br>
                    <code><?php echo esc_html($uploadDirectory) ?></code>
                </p>
                <span class="wpstg-backup-direct-upload-reload-notice">
                    <?php echo sprintf(
                        esc_html__('%s this page after uploading via FTP!', 'wp-staging'),
                        '<a href="' . esc_url(get_admin_url() . 'admin.php?page=wpstg_backup') . '" target="_self">' . esc_html__('Reload', 'wp-staging') . '</a>'
                    ); ?>
                </span>
            </div>
        </div>
        <div id="wpstg-upload-progress">
            <div class="wpstg--modal--upload--process">
                <div class="wpstg--modal--upload--progress"></div>
                <h4 class="wpstg--modal--upload--progress--title">
                    <span><small><?php esc_html_e('Discovering optimal upload speed... This may take a while...', 'wp-staging'); ?></small></span>
                </h4>
            </div>
            <p class="wpstg-backup-upload-dont-close-notice"><?php esc_html_e('If you close this window the upload will be aborted.', 'wp-staging') ?></p>
        </div>
    </div>
</div>
