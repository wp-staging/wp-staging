<?php

/**
 * @var \WPStaging\Pro\Backup\Storage\Storages\BaseS3\S3Auth $auth
 * @var string $providerId
 * @var string $providerName
 * @var string $settingText
 * @var string $settingLink
 * @var string $settingText1
 * @var string $settingLink1
 * @var string $locationName
 */

use WPStaging\Framework\Facades\Escape;

?>
<fieldset>
    <?php

    if ($auth->isEncrypted()) {
        require_once WPSTG_VIEWS_DIR . "settings/tabs/storages/encrypted-notice.php";
    }

    $regions                = $auth->getRegions();
    $isStorageAuthenticated = $auth->isAuthenticated();
    $options                = $auth->getOptions();
    $accessKey              = empty($options['accessKey']) ? '' : $options['accessKey'];
    $secretKey              = empty($options['secretKey']) ? '' : $options['secretKey'];
    $region                 = empty($options['region']) ? '' : $options['region'];
    $maxBackupsToKeep       = empty($options['maxBackupsToKeep']) ? 2 : $options['maxBackupsToKeep'];
    $maxBackupsToKeep       = $maxBackupsToKeep > 0 ? $maxBackupsToKeep : 15;
    $location               = empty($options['location']) ? '' : $options['location'];
    $lastUpdated            = empty($options['lastUpdated']) ? 0 : $options['lastUpdated'];
    $locationName           = empty($locationName) ? 'Bucket' : $locationName;
    ?>
    <p>
        <strong class="wpstg-fs-18"><?php echo esc_html($providerName); ?></strong>
        <br/>
        <br/>
        <?php echo sprintf(esc_html__('Upload backup files to your  %s account.', 'wp-staging'), esc_html($providerName)); ?>
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

                <?php if (!empty($settingLink) && !empty($settingText)) : ?>
                <p>
                    <a href="<?php echo esc_attr($settingLink); ?>" target="_blank"><?php echo esc_html($settingText); ?></a>
                </p>
                <?php endif; ?>

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-access-key"><?php esc_html_e('Access Key', 'wp-staging') ?></label>
                    <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-access-key" class="wpstg-form-control wpstg-storage-provider-input-field" type="password" name="access_key" value="<?php echo esc_attr($accessKey); ?>" autocomplete="off" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-secret-key"><?php esc_html_e('Secret Key', 'wp-staging') ?></label>
                    <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-secret-key" class="wpstg-form-control wpstg-storage-provider-input-field" type="password" name="secret_key" value="<?php echo esc_attr($secretKey); ?>" autocomplete="off" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-region"><?php esc_html_e('Region', 'wp-staging') ?></label>
                    <?php if (!empty($regions)) { ?>
                        <select id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-region" class="wpstg-form-select wpstg-storage-provider-input-field" name="region" autocomplete="off">
                            <?php foreach ($regions as $regionKey => $regionName) : ?>
                                <option value="<?php echo esc_attr($regionKey); ?>" <?php echo ($regionKey === $region) ? 'selected' : '' ; ?>><?php echo esc_html($regionName) . ' ' . esc_html($regionKey); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php } else { ?>
                        <input class="wpstg-form-control" type="text" name="region" value="<?php echo esc_attr($region); ?>" />
                    <?php } ?>
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-backup-folder-name"><?php esc_html_e($locationName, 'wp-staging') ?></label>
                    <span>s3:</span><input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-backup-folder-name" class="wpstg-form-control wpstg-storage-provider-backup-location-field" type="text" name="location" value="<?php echo esc_attr($location); ?>" />
                    <?php if (!empty($settingLink1) && !empty($settingText1)) : ?>
                            <a href="<?php echo esc_attr($settingLink1); ?>" target="_blank"><?php echo esc_html($settingText1); ?></a>
                    <?php endif; ?>
                        <p>
                        <?php echo sprintf(
                            Escape::escapeHtml(__('To add a directory you can write <code>s3:[%s]/[directory-name]</code>.<br>The directory will be created automatically during backup upload. ', 'wp-staging')),
                            esc_html($locationName),
                            '<br>'
                        ); ?>
                        </p>
                </fieldset>
            </div>
            <button type="button" id="wpstg-btn-provider-test-connection" class="wpstg-link-btn wpstg-blue-primary"><?php esc_html_e("Connection Test", "wp-staging") ?></button>
            <hr/>
            <strong><?php esc_html_e('Upload Settings', 'wp-staging') ?></strong>
            <fieldset class="wpstg-fieldset">
                <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-max-backups-to-keep"><?php esc_html_e('Max Backups to Keep', 'wp-staging') ?></label>
                <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-max-backups-to-keep" class="wpstg-form-control wpstg-storage-backup-retention-field" type="number" name="max_backups_to_keep" value="<?php echo esc_attr($maxBackupsToKeep); ?>" min="1" />
            </fieldset>
            <?php require_once WPSTG_VIEWS_DIR . "settings/tabs/storages/storage-notice.php";?>
            <hr/>

            <div class="wpstg-storage-provider-action-container">
                <button type="button" id="wpstg-btn-save-provider-settings" class="wpstg-button wpstg-blue-primary"><?php esc_html_e("Save Settings", "wp-staging") ?></button>
                <button type="button" id="wpstg-btn-delete-provider-settings" class="wpstg-button wpstg--error"><?php esc_html_e("Delete Storage Settings", "wp-staging") ?></button><?php require_once WPSTG_VIEWS_DIR . "settings/tabs/storages/last-saved-notice.php"; ?>
            </div>

        </form>
    </div>
</fieldset>
