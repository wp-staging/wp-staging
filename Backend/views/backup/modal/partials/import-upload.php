<?php
/**
 * @var string $urlAssets
 */
?>
<div class="wpstg--modal--backup--import--upload">
    <div class="wpstg--modal--backup--import--upload--container">
        <div class="wpstg--uploader">
            <input type="file" name="wpstg--backup--import--upload--file" accept=".wpstg"/>
            <img src="<?php echo esc_url($urlAssets . 'img/upload.svg'); ?>" alt="Upload Image"/>
            <span class="wpstg--backup--import--selected-file"></span>
            <span class="wpstg--drag-or-upload">
            <?php esc_html_e('Drag a new export file here or choose another option', 'wp-staging') ?>
          </span>
            <span class="wpstg--drag">
            <?php esc_html_e('Drag and Drop a WPSTAGING Backup file to start import', 'wp-staging') ?>
                        <br>
            <small><?php esc_html_e('You can upload a file of any size here', 'wp-staging') ?></small>
          </span>
            <span class="wpstg--drop">
            <?php esc_html_e('Drop export file here', 'wp-staging') ?>
          </span>
            <div class="wpstg--backup--import--options">
                <button
                    class="wpstg-blue-primary wpstg-button wpstg-link-btn wpstg--backup--import--choose-option"
                    data-txtOther="<?php esc_attr_e('Import from', 'wp-staging') ?>"
                    data-txtChoose="<?php esc_attr_e('Choose an Option', 'wp-staging') ?>"
                >
                    <?php esc_html_e('Import from', 'wp-staging') ?>
                </button>
                <ul>
                    <li>
                        <button class="wpstg--backup--import--option wpstg-blue-primary" data-option="file">
                            <?php esc_html_e('Local Computer', 'wp-staging') ?>
                        </button>
                    </li>
                    <li>
                        <button class="wpstg--backup--import--option wpstg-blue-primary" data-option="filesystem">
                            <?php esc_html_e('Existing Backups', 'wp-staging') ?>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
        <div class="wpstg--modal--import--upload--process">
            <div class="wpstg--modal--import--upload--progress"></div>
            <h4 class="wpstg--modal--import--upload--progress--title">
                <?php echo sprintf(esc_html__('Uploading %s%%...', 'wp-staging'), '<span></span>') ?>
            </h4>
        </div>
    </div>
    <div
        class="wpstg--modal--backup--import--upload--status"
        data-txt-uploading="<?php esc_html_e('Uploading...', 'wp-staging') ?>"
        data-txt-done="<?php esc_html_e('Uploaded Successfully', 'wp-staging') ?>"
        data-txt-error="<?php esc_html_e('Error! {message}', 'wp-staging') ?>"
    >
    </div>
</div>
