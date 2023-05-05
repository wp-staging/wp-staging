<?php

/**
 * @var string $providerId
 */

use WPStaging\Framework\Facades\Escape;
use WPStaging\Pro\Backup\Storage\Storages\GoogleDrive\Auth;

?>
<fieldset>
    <?php
    /** @var \WPStaging\Pro\Backup\Storage\Storages\GoogleDrive\Auth */
    $googleDriveStorage = \WPStaging\Core\WPStaging::make(Auth::class);
    $isGoogleDriveAuthenticated = $googleDriveStorage->isAuthenticated();
    $options = $googleDriveStorage->getOptions();

    $maxBackupsToKeep = isset($options['maxBackupsToKeep']) ? $options['maxBackupsToKeep'] : 2;
    $maxBackupsToKeep = $maxBackupsToKeep > 0 ? $maxBackupsToKeep : 15;
    $folderName = isset($options['folderName']) ? $options['folderName'] : \WPStaging\Pro\Backup\Storage\Storages\GoogleDrive\Auth::FOLDER_NAME;
    $lastUpdated = empty($options['lastUpdated']) ? 0 : $options['lastUpdated'];

    $googleClientId = isset($options['googleClientId']) ? $options['googleClientId'] : '';
    $googleClientSecret = isset($options['googleClientSecret']) ? $options['googleClientSecret'] : '';
    $defaultApiAuthorizeURL = add_query_arg(
        [
            'action' => 'wpstg-googledrive-api-auth',
        ],
        network_admin_url('admin-post.php')
    );

    $googleRedirectURI = isset($options['googleRedirectURI']) ? $options['googleRedirectURI'] : $defaultApiAuthorizeURL;
    ?>
    <p>
        <strong class="wpstg-fs-14"> <?php esc_html_e('Google Drive', 'wp-staging'); ?></strong>
        <br/>
        <?php echo esc_html__('Upload backup files to your personal Google Drive account.', 'wp-staging'); ?>
        <br>
        <?php echo sprintf(
            Escape::escapeHtml(__('None of your backup data is sent to any other party! <a href="%s" target="_blank">Our privacy policy</a>.', 'wp-staging')),
            'https://wp-staging.com/privacy-policy/#Google_Drive'
        ); ?>
    </p>
    <div class="wpstg-form-group">
        <?php
        if ($isGoogleDriveAuthenticated) {
            ?>
            <strong class="wpstg-mr-10px">
                <?php
                esc_html_e('You are authenticated to Google Drive.', 'wp-staging');
                ?>
            </strong>
            <br>
            <form class="wpstg-provider-revoke-form" id="wpstg-provider-revoke-form" method="post">
                <input type="hidden" name="provider" value="<?php echo esc_attr($providerId); ?>" />
                <button type="button" id="wpstg-btn-provider-revoke" class="wpstg-button--primary wpstg-button--blue"><?php esc_html_e("Logout from Google", "wp-staging") ?></button>
            </form>
            <br/>
            <?php
        } else {
            $authURL = $googleDriveStorage->getAuthenticationURL();
            if ($authURL === false) {
                ?>
                <b class="wpstg-error"><?php esc_html_e('Unable to generate Google Authentication URL. Google API keys are not correct!', 'wp-staging'); ?></b>
                <?php
            } else {
                ?>
            <a href="<?php echo esc_url($authURL); ?>" id="wpstg_google_drive_connect" class="wpstg-btn-google"> <img src="<?php echo esc_url(WPSTG_PLUGIN_URL . 'assets/img/google-g.png'); ?>" /> <?php esc_html_e("Sign in with Google", "wp-staging") ?></a>
            <span><?php esc_html_e("OR", "wp-staging") ?></span> &nbsp; <a onclick="WPStaging.handleToggleElement(this)" data-wpstg-target="#wpstg-custom-google-credentials" href="javascript:void(0);"><?php esc_html_e("Connect with API Credentials", "wp-staging") ?></a>
                <?php
            }
        }
        ?>
    </div>
    <div class="wpstg-form-group">
        <form class="wpstg-provider-settings-form" id="wpstg-provider-settings-form" method="post">
            <input type="hidden" name="provider" value="<?php echo esc_attr($providerId); ?>" />

            <div class="hidden" id="wpstg-custom-google-credentials">
                <strong><?php esc_html_e('API Keys', 'wp-staging') ?></strong>

                <p>
                    <?php echo sprintf(
                        Escape::escapeHtml(__('You can use your own Google API keys. This is optional. <a href="%s" target="_blank">How to create your own Google API keys</a>.', 'wp-staging', 'wp-staging')),
                        'https://wp-staging.com/docs/create-google-api-credentials-to-authenticate-to-google-drive/'
                    ); ?>
                </p>

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('Google Client Id', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="text" name="google_client_id" value="<?php echo esc_attr($googleClientId); ?>" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('Google Client Secret', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="text" name="google_client_secret" value="<?php echo esc_attr($googleClientSecret); ?>" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('Google Redirect URI', 'wp-staging') ?></label>
                    <div class="wpstg-with-icon">
                        <input class="wpstg-form-control" type="text" name="google_redirect_uri" id="google-redirect-uri" value="<?php echo esc_url($googleRedirectURI); ?>" />
                        <a href="javascript:void(0);" class="wpstg-fieldset-icon" onclick="WPStaging.handleCopyToClipboard(this)" data-wpstg-source="#google-redirect-uri">
                            <img src="<?php echo esc_url(WPSTG_PLUGIN_URL . 'assets/svg/copy.svg'); ?>" alt="<?php esc_html_e("Copy to Clipboard", 'wp-staging') ?>" title="<?php esc_html_e("Copy to Clipboard", 'wp-staging') ?>" />
                        </a>
                    </div>
                </fieldset>
            </div>
            <hr/>
            <strong><?php esc_html_e('Upload Settings', 'wp-staging') ?></strong>
            <fieldset class="wpstg-fieldset">
                <label><?php esc_html_e('Max Backups to Keep', 'wp-staging') ?></label>
                <input class="wpstg-form-control" type="number" name="max_backups_to_keep" value="<?php echo esc_attr($maxBackupsToKeep); ?>" min="1" style="max-width: 60px" />
            </fieldset>

            <fieldset class="wpstg-fieldset">
                <label><?php esc_html_e('Backup Location', 'wp-staging') ?></label>
                <span>//Google Drive/</span><input class="wpstg-form-control" type="text" style="min-width:300px;" placeholder="backups/example.com/" name="folder_name" value="<?php echo esc_attr($folderName); ?>" />
            </fieldset>

            <hr/>

            <button type="button" id="wpstg-btn-save-provider-settings" class="wpstg-button wpstg-blue-primary"><?php esc_html_e("Save Settings", "wp-staging") ?></button><?php require_once "{$this->path}views/settings/tabs/storages/last-saved-notice.php"; ?>
        </form>
    </div>
</fieldset>
