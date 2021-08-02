<?php

/**
 * @var \WPStaging\Framework\Adapter\Directory $directory
 */

use WPStaging\Core\WPStaging;
use WPStaging\Pro\Backup\Service\BackupsFinder;

$uploadDirectory = str_replace(wp_normalize_path(ABSPATH), '', WPStaging::make(BackupsFinder::class)->getBackupsDirectory());
?>
<div
    id="wpstg--modal--backup--upload"
    data-cancelButtonText="<?php esc_attr_e('CANCEL', 'wp-staging'); ?>"
    data-uploadSuccessMessage="<?php esc_attr_e('The backup file has been successfully uploaded. You can restore your website with this backup.', 'wp-staging'); ?>"
    style="display: none"
>
    <h2 class="wpstg--modal--backup--import--upload--title wpstg--grey"><?php esc_html_e('Uploading Backup', 'wp-staging') ?></h2>
    <div class="wpstg--modal--backup--import--upload--content">
        <?php
        /**
         * @var string $urlAssets
         */
        ?>

        <div class="wpstg-linear-loader">
            <span class="wpstg-linear-loader-item"></span>
            <span class="wpstg-linear-loader-item"></span>
            <span class="wpstg-linear-loader-item"></span>
            <span class="wpstg-linear-loader-item"></span>
            <span class="wpstg-linear-loader-item"></span>
            <span class="wpstg-linear-loader-item"></span>
        </div>

        <div class="wpstg--modal--backup--import--upload">

                <div id="wpstg-upload-select">
                    <div class="wpstg--modal--backup--import--upload--container resumable-drop resumable-browse">
                        <img src="<?php echo esc_url($urlAssets . 'img/upload.svg'); ?>" alt="Upload Image"/>
                        <div class="wpstg-upload-text">
                            <?php
                                echo wp_kses(
                                    __(sprintf('Drop the backup file here to upload or <br><a>select from your computer</a>'), 'wp-staging'),
                                    [
                                    // Allowed HTML
                                    'a' => [],
                                    'br' => []
                                    ]
                                ) ?>
                        </div>
                        <div class="wpstg-dragover-text">
                            <strong><?php echo esc_html('Drop here to start the upload!') ?></strong>
                        </div>
                    </div>
                    <p class="wpstg-backup-direct-upload-notice">
                        <?php esc_html_e('Did you know? You can also upload backups directly to this directory:', 'wp-staging') ?><br>
                        <strong><?php echo esc_html($uploadDirectory) ?></strong>
                    </p>
                </div>

                <div id="wpstg-upload-progress">
                    <div class="wpstg--modal--import--upload--process">
                        <div class="wpstg--modal--import--upload--progress"></div>
                        <h4 class="wpstg--modal--import--upload--progress--title">
                            <span><small><?php esc_html_e('Discovering optimal upload speed... This might a little while...', 'wp-staging'); ?></small></span>
                        </h4>
                    </div>
                    <p class="wpstg-backup-upload-dont-close-notice"><?php esc_html_e('If you close this window your backup will be aborted.', 'wp-staging') ?></p>
                </div>


        </div>

    </div>
</div>
