<?php

/**
 * @var string $providerId
 */

use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Facades\Sanitize;

?>
<fieldset>
    <?php
    /** @var \WPStaging\Pro\Backup\Storage\Storages\Amazon\S3 */
    $amazonS3Storage = \WPStaging\Core\WPStaging::make(\WPStaging\Pro\Backup\Storage\Storages\Amazon\S3::class);
    $isStorageAuthenticated = $amazonS3Storage->isAuthenticated();
    $options = $amazonS3Storage->getOptions();
    $accessKey = empty($options['accessKey']) ? '' : Sanitize::sanitizeString($options['accessKey']);
    $secretKey = empty($options['secretKey']) ? '' : Sanitize::sanitizeString($options['secretKey']);
    $region = empty($options['region']) ? '' : Sanitize::sanitizeString($options['region']);
    $maxBackupsToKeep = empty($options['maxBackupsToKeep']) ? 2 : Sanitize::sanitizeInt($options['maxBackupsToKeep']);
    $location = empty($options['location']) ? '' : Sanitize::sanitizeString($options['location']);
    ?>
    <p>
        <strong class="wpstg-fs-14"><?php esc_html_e('Amazon S3', 'wp-staging'); ?></strong>
        <br/>
        <br/>
        <?php echo esc_html__('Upload backup files to your personal Amazon S3 account.', 'wp-staging'); ?>
        <br>
        <?php echo sprintf(
            Escape::escapeHtml(__('None of your backup data is sent to any other party! <a href="%s" target="_blank">Our privacy policy</a>.', 'wp-staging')),
            'https://wp-staging.com/privacy-policy/#'
        ); ?>
        <br/>
    </p>
    <div class="wpstg-form-group">
        <form class="wpstg-provider-settings-form" id="wpstg-provider-settings-form" method="post">
            <div id="wpstg-provider-test-connection-fields">
                <strong><?php esc_html_e('API Keys', 'wp-staging') ?></strong>

                <input type="hidden" name="provider" value="<?php echo esc_attr($providerId); ?>" />

                <p>
                    <?php echo sprintf(
                        Escape::escapeHtml(__('<a href="%s" target="_blank">How to create Amazon API keys and S3 bucket</a>.', 'wp-staging')),
                        'https://wp-staging.com/docs/how-to-backup-website-to-amazon-s3-bucket/'
                    ); ?>
                </p>

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('Access Key', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="text" name="access_key" value="<?php echo esc_attr($accessKey); ?>" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('Secret Key', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="text" name="secret_key" value="<?php echo esc_attr($secretKey); ?>" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('Region', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="text" name="region" value="<?php echo esc_attr($region); ?>" />
                </fieldset>
            </div>
            <button type="button" id="wpstg-btn-provider-test-connection" class="wpstg-link-btn wpstg-blue-primary"><?php esc_html_e("Test Connection", "wp-staging") ?></button>

            <hr/>
            <strong><?php esc_html_e('Upload Settings', 'wp-staging') ?></strong>
            <fieldset class="wpstg-fieldset">
                <label><?php esc_html_e('Max Backups to Keep', 'wp-staging') ?></label>
                <input class="wpstg-form-control" type="number" name="max_backups_to_keep" value="<?php echo esc_attr($maxBackupsToKeep); ?>" style="max-width: 60px" />
                <p><?php esc_html_e("Leave empty or zero for no limit", 'wp-staging') ?></p>
            </fieldset>

            <fieldset class="wpstg-fieldset">
                <label><?php esc_html_e('Amazon S3 Bucket Location', 'wp-staging') ?></label>
                <span>s3:</span><input class="wpstg-form-control" type="text" name="location" value="<?php echo esc_attr($location); ?>" />
                <p>
                    <?php echo sprintf(
                        esc_html__("Create the bucket beforhand in your Amazon S3 account and add it here! %s To add a subdirectory you can write <code>s3:[bucket-name]/[directory-name]</code>. <br>The directory will be created by WP STAGING automatically during backup upload. ", 'wp-staging'),
                        '<br>'
                    ); ?>
                </p>
            </fieldset>

            <hr/>

            <button type="button" id="wpstg-btn-save-provider-settings" class="wpstg-link-btn wpstg-blue-primary"><?php esc_html_e("Save Settings", "wp-staging") ?></button>
        </form>
    </div>
</fieldset>
