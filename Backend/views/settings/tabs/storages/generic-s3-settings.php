<?php

/**
 * @var string $providerId
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Pro\Backup\Storage\Storages\GenericS3\Auth;
use WPStaging\Pro\Backup\Storage\Storages\GenericS3\Providers;

?>
<fieldset>
    <?php
    /** @var Auth */
    $auth = WPStaging::make(Auth::class);
    $providers = Providers::PROVIDERS;
    $isStorageAuthenticated = $auth->isAuthenticated();
    $options = $auth->getOptions();
    $s3provider = empty($options['provider']) ? '' : $options['provider'];
    $accessKey = empty($options['accessKey']) ? '' : $options['accessKey'];
    $secretKey = empty($options['secretKey']) ? '' : $options['secretKey'];
    $region = empty($options['region']) ? '' : $options['region'];
    $maxBackupsToKeep = empty($options['maxBackupsToKeep']) ? 2 : $options['maxBackupsToKeep'];
    $maxBackupsToKeep = $maxBackupsToKeep > 0 ? $maxBackupsToKeep : 15;
    $location = empty($options['location']) ? '' : $options['location'];
    $lastUpdated = empty($options['lastUpdated']) ? 0 : $options['lastUpdated'];

    if ($s3provider === '') {
        $customProviderName = empty($options['providerName']) ? '' : $options['providerName'];
        $endpoint = empty($options['endpoint']) ? '' : $options['endpoint'];
        $version = empty($options['version']) ? '' : $options['version'];
        $ssl = isset($options['ssl']) ? $options['ssl'] : false;
        $usePathStyleEndpoint = isset($options['usePathStyleEndpoint']) ? $options['usePathStyleEndpoint'] : false;
    }

    $locationName = empty($locationName) ? 'Bucket' : $locationName;

    ?>
    <p>
        <strong class="wpstg-fs-14"><?php esc_html_e('Generic S3', 'wp-staging'); ?></strong>
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
                    <label><?php esc_html_e('S3 Compatible Provider', 'wp-staging') ?></label>
                    <select class="wpstg-form-select" name="s3_provider" style="min-width:300px;">
                    <option value="" <?php echo ('' === $s3provider) ? 'selected' : '' ; ?>><?php esc_html_e('Custom Provider', 'wp-staging'); ?></option>
                        <?php foreach ($providers as $providerArr) : ?>
                            <option value="<?php echo esc_attr($providerArr['key']); ?>" <?php echo ($providerArr['key'] === $s3provider) ? 'selected' : '' ; ?>><?php echo esc_html($providerArr['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <div id="wpstg-s3-custom-provider-fields" class="hidden" <?php echo ('' === $s3provider) ? 'style="display: block;"' : '' ; ?>>
                    
                    <strong><?php esc_html_e('Custom Provider', 'wp-staging') ?></strong>

                    <fieldset class="wpstg-fieldset">
                        <label><?php esc_html_e('Name', 'wp-staging') ?></label>
                        <input class="wpstg-form-control" type="text" style="min-width:300px;" name="provider_name" value="<?php echo esc_attr($customProviderName); ?>" />
                    </fieldset>

                    <fieldset class="wpstg-fieldset">
                        <label><?php esc_html_e('Endpoint', 'wp-staging') ?></label>
                        <input class="wpstg-form-control" type="text" name="endpoint" value="<?php echo esc_attr($endpoint); ?>" style="min-width:300px;" />
                    </fieldset>

                    <fieldset class="wpstg-fieldset">
                        <label><?php esc_html_e('Version', 'wp-staging') ?></label>
                        <input class="wpstg-form-control" type="text" name="version" value="<?php echo esc_attr($version); ?>" style="min-width:300px;" />
                        <br><br>
                            <?php echo Escape::escapeHtml(__("If your S3 provider does not specify a version in their guide, enter <code>latest</code> or <code>2006-03-01</code>.", 'wp-staging')); ?>
                    </fieldset>

                    <fieldset class="wpstg-fieldset">
                        <label><?php esc_html_e('SSL', 'wp-staging') ?></label>
                        <input type="checkbox" class="wpstg-checkbox" name="ssl" value="true" <?php echo $ssl === true ? 'checked ' : '' ?>/>
                    </fieldset>

                    <fieldset class="wpstg-fieldset">
                        <label><?php esc_html_e('Use path style endpoint', 'wp-staging') ?></label>
                        <input type="checkbox" class="wpstg-checkbox" name="use_path_style_endpoint" value="true" <?php echo $usePathStyleEndpoint === true ? 'checked ' : '' ?>/>
                    </fieldset>
                </div>

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('Access Key', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="text" style="min-width:300px;" name="access_key" value="<?php echo esc_attr($accessKey); ?>" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('Secret Key', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="text" name="secret_key" value="<?php echo esc_attr($secretKey); ?>" style="min-width:300px;" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('Region', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="text" style="min-width:300px;" name="region" value="<?php echo esc_attr($region); ?>" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('Bucket Name', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="text" style="min-width:300px;" name="location" value="<?php echo esc_attr($location); ?>" />
                    <br><br>
                    <?php echo sprintf(
                        Escape::escapeHtml(__('To add a directory you can write <code>s3:[%s]/[directory-name]</code>.<br>The directory will be created automatically during backup upload. ', 'wp-staging')),
                        esc_html($locationName),
                        '<br>'
                    ); ?>
                </fieldset>
            </div>
            <button type="button" id="wpstg-btn-provider-test-connection" class="wpstg-link-btn wpstg-blue-primary"><?php esc_html_e("Connection Test", "wp-staging") ?></button>

            <hr/>
            <strong><?php esc_html_e('Upload Settings', 'wp-staging') ?></strong>
            <fieldset class="wpstg-fieldset">
                <label><?php esc_html_e('Max Backups to Keep', 'wp-staging') ?></label>
                <input class="wpstg-form-control" type="number" name="max_backups_to_keep" value="<?php echo esc_attr($maxBackupsToKeep); ?>" min="1" style="max-width: 60px" />
            </fieldset>

            <hr/>

            <button type="button" id="wpstg-btn-save-provider-settings" class="wpstg-button wpstg-blue-primary"><?php esc_html_e("Save Settings", "wp-staging") ?></button><?php require_once "{$this->path}views/settings/tabs/storages/last-saved-notice.php"; ?>

        </form>
    </div>
</fieldset>
