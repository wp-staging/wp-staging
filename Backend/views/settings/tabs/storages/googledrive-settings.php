<?php

/**
 * @var string $providerId
 */

use WPStaging\Framework\Facades\Sanitize;

?>
<fieldset>
    <?php
    /** @var \WPStaging\Pro\Backup\Storage\Storages\GoogleDrive\Auth */
    $googleDriveStorage = \WPStaging\Core\WPStaging::getInstance()->get(\WPStaging\Pro\Backup\Storage\Storages\GoogleDrive\Auth::class);
    $isGoogleDriveAuthenticated = $googleDriveStorage->isAuthenticated();
    $options = $googleDriveStorage->getOptions();

    $maxBackupsToKeep = isset($options['maxBackupsToKeep']) ? Sanitize::sanitizeInt($options['maxBackupsToKeep']) : 2;
    $folderName = isset($options['folderName']) ? Sanitize::sanitizeString($options['folderName']) : \WPStaging\Pro\Backup\Storage\Storages\GoogleDrive\Auth::FOLDER_NAME;

    $googleClientId = isset($options['googleClientId']) ? Sanitize::sanitizeString($options['googleClientId']) : '';
    $googleClientSecret = isset($options['googleClientSecret']) ? Sanitize::sanitizeString($options['googleClientSecret']) : '';
    $defaultApiAuthorizeURL = add_query_arg(
        [
            'action' => 'wpstg-googledrive-api-auth',
        ],
        network_admin_url('admin-post.php')
    );

    $googleRedirectURI = isset($options['googleRedirectURI']) ? Sanitize::sanitizeString($options['googleRedirectURI']) : $defaultApiAuthorizeURL;
    ?>
    <p>
        <strong class="wpstg-fs-14"> <?php _e('Google Drive', 'wp-staging'); ?></strong>
        <br/>
        <?php echo __('Upload backup files to your personal Google Drive account.', 'wp-staging'); ?>
        <br>
        <?php echo sprintf(__('None of your backup data is sent to any other party! <a href="%s" target="_blank">Our privacy policy</a>.', 'wp-staging'), 'https://wp-staging.com/privacy-policy/#Google_Drive'); ?>
    </p>
    <div class="wpstg-form-group">
        <?php
        if ($isGoogleDriveAuthenticated) {
            ?>
            <strong class="wpstg-mr-10px">
                <?php
                _e('You are authenticated to Google Drive.', 'wp-staging');
                ?>
            </strong>
            <br>
            <form class="wpstg-provider-revoke-form" id="wpstg-provider-revoke-form" method="post">
                <input type="hidden" name="provider" value="<?php echo $providerId; ?>" />
                <button type="button" id="wpstg-btn-provider-revoke" class="wpstg-link-btn wpstg-btn-danger"><?php _e("Logout from Google", "wp-staging") ?></button>
            </form>
            <br/>
            <?php
        } else {
            $authURL = $googleDriveStorage->getAuthenticationURL();
            if ($authURL === false) {
                ?>
                <b class="wpstg-error"><?php _e('Unable to generate Google Authentication URL. Google API keys are not correct!', 'wp-staging'); ?></b>
                <?php
            } else {
                ?>
            <a href="<?php echo esc_url($authURL); ?>" id="wpstg_google_drive_connect" class="wpstg-btn-google"> <img src="<?php echo esc_url(WPSTG_PLUGIN_URL . 'assets/img/google-g.png'); ?>" /> <?php _e("Sign in with Google", "wp-staging") ?></a>
            <span><?php _e("OR", "wp-staging") ?></span> &nbsp; <a onclick="WPStaging.handleToggleElement(this)" data-wpstg-target="#wpstg-custom-google-credentials" href="javascript:void(0);"><?php _e("Connect with API Credentials", "wp-staging") ?></a>
                <?php
            }
        }
        ?>
        <form class="wpstg-provider-settings-form" id="wpstg-provider-settings-form" method="post">
            <input type="hidden" name="provider" value="<?php echo $providerId; ?>" />

            <div class="hidden" id="wpstg-custom-google-credentials">
                <strong><?php _e('API Keys', 'wp-staging') ?></strong>

                <p>
                    <?php echo sprintf(__('You can use your own Google API keys. This is optional. <a href="%s" target="_blank">How to create your own Google API keys</a>.', 'wp-staging'), 'https://wp-staging.com/docs/create-google-api-credentials-to-authenticate-to-google-drive/'); ?>
                </p>

                <fieldset class="wpstg-fieldset">
                    <label><?php _e('Google Client Id', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="text" name="google_client_id" value="<?php echo $googleClientId; ?>" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label><?php _e('Google Client Secret', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="text" name="google_client_secret" value="<?php echo $googleClientSecret; ?>" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label><?php _e('Google Redirect URI', 'wp-staging') ?></label>
                    <div class="wpstg-with-icon">
                        <input class="wpstg-form-control" type="text" name="google_redirect_uri" id="google-redirect-uri" value="<?php echo $googleRedirectURI; ?>" />
                        <a href="javascript:void(0);" class="wpstg-fieldset-icon" onclick="WPStaging.handleCopyToClipboard(this)" data-wpstg-source="#google-redirect-uri">
                            <img src="<?php echo esc_url(WPSTG_PLUGIN_URL . 'assets/svg/copy.svg'); ?>" alt="<?php _e("Copy to Clipboard", 'wp-staging') ?>" title="<?php _e("Copy to Clipboard", 'wp-staging') ?>" />
                        </a>
                    </div>
                </fieldset>
            </div>
            <hr/>
            <strong><?php _e('Upload Settings', 'wp-staging') ?></strong>
            <fieldset class="wpstg-fieldset">
                <label><?php _e('Max Backups to Keep', 'wp-staging') ?></label>
                <input class="wpstg-form-control" type="number" name="max_backups_to_keep" value="<?php echo $maxBackupsToKeep; ?>" style="max-width: 60px" />
                <p><?php _e("Leave empty or zero for no limit", 'wp-staging') ?></p>
            </fieldset>

            <fieldset class="wpstg-fieldset">
                <label><?php _e('Backup Folder Name', 'wp-staging') ?></label>
                <input class="wpstg-form-control" type="text" name="folder_name" value="<?php echo $folderName; ?>" />
            </fieldset>

            <hr/>

            <button type="button" id="wpstg-btn-save-provider-settings" class="wpstg-link-btn wpstg-blue-primary"><?php _e("Save Settings", "wp-staging") ?></button>
        </form>
    </div>
</fieldset>
