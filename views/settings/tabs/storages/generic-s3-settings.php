<?php

/**
 * @var string $providerId
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Facades\UI\Checkbox;
use WPStaging\Pro\Backup\Storage\Storages\GenericS3\Auth;
use WPStaging\Pro\Backup\Storage\Storages\GenericS3\Providers;

?>
<fieldset>
    <?php
    /** @var Auth */
    $auth = WPStaging::make(Auth::class);

    $providerName = esc_html__('Generic S3', 'wp-staging');
    if ($auth->isEncrypted()) {
        require_once WPSTG_VIEWS_DIR . "settings/tabs/storages/encrypted-notice.php";
    }

    $providers              = Providers::PROVIDERS;
    $isStorageAuthenticated = $auth->isAuthenticated();
    $options                = $auth->getOptions();
    $s3provider             = empty($options['provider']) ? '' : $options['provider'];
    $accessKey              = empty($options['accessKey']) ? '' : $options['accessKey'];
    $secretKey              = empty($options['secretKey']) ? '' : $options['secretKey'];
    $region                 = empty($options['region']) ? '' : $options['region'];
    $maxBackupsToKeep       = empty($options['maxBackupsToKeep']) ? 2 : $options['maxBackupsToKeep'];
    $maxBackupsToKeep       = $maxBackupsToKeep > 0 ? $maxBackupsToKeep : 15;
    $location               = empty($options['location']) ? '' : $options['location'];
    $lastUpdated            = empty($options['lastUpdated']) ? 0 : $options['lastUpdated'];

    if ($s3provider === '') {
        $customProviderName   = empty($options['providerName']) ? '' : $options['providerName'];
        $endpoint             = empty($options['endpoint']) ? '' : $options['endpoint'];
        $version              = empty($options['version']) ? '' : $options['version'];
        $ssl                  = isset($options['ssl']) ? $options['ssl'] : false;
        $usePathStyleEndpoint = isset($options['usePathStyleEndpoint']) ? $options['usePathStyleEndpoint'] : false;
    }

    $locationName = empty($locationName) ? 'Bucket' : $locationName;
    $assetsUrl    = trailingslashit(WPSTG_PLUGIN_URL);

    ?>
    <p>
        <strong class="wpstg-fs-18"><?php echo esc_html($providerName); ?></strong>
        <br/>
        <br/>
        <?php echo esc_html__('Upload backup files to your personal Generic S3 account.', 'wp-staging'); ?>
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

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-s3"><?php esc_html_e('S3 Compatible Provider', 'wp-staging') ?></label>
                    <select id="wpstg-storage-provider-s3" class="wpstg-form-select wpstg-storage-provider-input-field" name="s3_provider">
                    <option value="" <?php echo ($s3provider === '') ? 'selected' : '' ; ?>><?php esc_html_e('Custom Provider', 'wp-staging'); ?></option>
                        <?php foreach ($providers as $providerArr) : ?>
                            <option value="<?php echo esc_attr($providerArr['key']); ?>" <?php echo ($providerArr['key'] === $s3provider) ? 'selected' : '' ; ?>><?php echo esc_html($providerArr['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <div id="wpstg-s3-custom-provider-fields" class="hidden" <?php echo ($s3provider === '') ? 'style="display: block;"' : '' ; ?>>

                    <strong><?php esc_html_e('Custom Provider', 'wp-staging') ?></strong>

                    <fieldset class="wpstg-fieldset">
                        <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-name"><?php esc_html_e('Name', 'wp-staging') ?></label>
                        <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-name" class="wpstg-form-control wpstg-storage-provider-input-field" type="text" name="provider_name" value="<?php echo esc_attr($customProviderName); ?>" placeholder="Provider Name" />
                    </fieldset>

                    <fieldset class="wpstg-fieldset">
                        <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-endpoint"><?php esc_html_e('Endpoint', 'wp-staging') ?></label>
                        <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-endpoint" class="wpstg-form-control wpstg-storage-provider-input-field" type="text" name="endpoint" value="<?php echo esc_attr($endpoint); ?>" placeholder="https://example.com:8888" />
                    </fieldset>

                    <fieldset class="wpstg-fieldset">
                        <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-version">
                            <?php esc_html_e('Version', 'wp-staging') ?>
                        </label>
                        <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-version" class="wpstg-form-control wpstg-storage-provider-input-field" type="text" name="version" value="<?php echo esc_attr($version); ?>" />
                        <p>
                            <?php echo Escape::escapeHtml(__("If your S3 provider does not specify a version in their guide, enter <code>latest</code> or <code>2006-03-01</code>.", 'wp-staging')); ?>
                        </p>
                    </fieldset>

                    <fieldset class="wpstg-fieldset">
                        <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-ssl">
                            <?php esc_html_e('SSL', 'wp-staging') ?>
                            <span class='wpstg--tooltip wpstg--tooltip-sftp'>
                                <img class='wpstg--dashicons wpstg--grey' src='<?php echo esc_html($assetsUrl); ?>assets/svg/info-outline.svg' alt='info'/>
                                <span class='wpstg--tooltiptext'>
                                    <?php esc_html_e('Enable SSL for secure encrypted connections to the S3 server.', 'wp-staging'); ?>
                                </span>
                            </span>
                        </label>
                        <?php Checkbox::render("wpstg-storage-provider-{$providerId}-ssl", 'ssl', 'true', $ssl === true); ?>
                    </fieldset>

                    <fieldset class="wpstg-fieldset">
                        <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-use-path-style-endpoint">
                            <?php esc_html_e('Use path style endpoint', 'wp-staging') ?>
                            <span class='wpstg--tooltip wpstg--tooltip-sftp'>
                                <img class='wpstg--dashicons wpstg--grey' src='<?php echo esc_html($assetsUrl); ?>assets/svg/info-outline.svg' alt='info'/>
                                <span class='wpstg--tooltiptext'>
                                    <?php esc_html_e('Use path-style URLs for accessing buckets (e.g., s3.example.com/bucket).', 'wp-staging'); ?>
                                </span>
                            </span>
                        </label>
                        <?php Checkbox::render("wpstg-storage-provider-{$providerId}-use-path-style-endpoint", 'use_path_style_endpoint', 'true', $usePathStyleEndpoint === true); ?>
                    </fieldset>
                </div>

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-access-key"><?php esc_html_e('Access Key', 'wp-staging') ?></label>
                    <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-access-key" class="wpstg-form-control wpstg-storage-provider-input-field" type="password" name="access_key" value="<?php echo esc_attr($accessKey); ?>" autocomplete="off" />
                    <p>
                        <?php esc_html_e('Unique identifier for your account, provided by your S3 service.', 'wp-staging'); ?>
                    </p>
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-secret-key"><?php esc_html_e('Secret Key', 'wp-staging') ?></label>
                    <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-secret-key" class="wpstg-form-control wpstg-storage-provider-input-field" type="password" name="secret_key" value="<?php echo esc_attr($secretKey); ?>"  autocomplete="off"/>
                    <p>
                        <?php esc_html_e('Private key for authentication. Keep this key secure.', 'wp-staging'); ?>
                    </p>
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-region"><?php esc_html_e('Region', 'wp-staging') ?></label>
                    <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-region" class="wpstg-form-control wpstg-storage-provider-input-field" type="text" name="region" value="<?php echo esc_attr($region); ?>" autocomplete="off"/>
                    <p>
                        <?php esc_html_e('The geographic region of your S3 storage. Match your bucket\'s region.', 'wp-staging'); ?>
                    </p>
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-folder-name"><?php esc_html_e('Bucket Name', 'wp-staging') ?></label>
                    <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-folder-name" class="wpstg-form-control wpstg-storage-provider-input-field" type="text" name="location" value="<?php echo esc_attr($location); ?>" />
                    <p>
                    <?php echo sprintf(
                        Escape::escapeHtml(__('To add a directory you can write <code>s3:[%s]/[directory-name]</code>.<br>The directory will be created automatically during backup upload. ', 'wp-staging')),
                        esc_html($locationName)
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
