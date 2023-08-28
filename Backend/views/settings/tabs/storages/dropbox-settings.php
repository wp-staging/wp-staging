<?php

/**
 * @var string $providerId
 */

use WPStaging\Framework\Facades\Escape;
use WPStaging\Pro\Backup\Storage\Storages\Dropbox\Auth;

?>
<fieldset>
    <?php
    /** @var Auth */
    $dropboxStorage         = \WPStaging\Core\WPStaging::make(Auth::class);
    $isDropboxAuthenticated = $dropboxStorage->isAuthenticated();
    $options                = $dropboxStorage->getOptions();
    $maxBackupsToKeep       = isset($options['maxBackupsToKeep']) ? $options['maxBackupsToKeep'] : 2;
    $folderName             = isset($options['folderName']) ? $options['folderName'] : Auth::FOLDER_NAME;
    $lastUpdated            = empty($options['lastUpdated']) ? 0 : $options['lastUpdated'];
    ?>
    <p>
        <strong class="wpstg-fs-14"> <?php esc_html_e('Dropbox', 'wp-staging'); ?></strong>
        <br/>
        <?php echo esc_html__('Upload backup files to your Dropbox account.', 'wp-staging'); ?>
        <br>
        <?php echo sprintf(
            Escape::escapeHtml(__('Your backup data will not be sent to us! <a href="%s" target="_blank">Our privacy policy</a>.', 'wp-staging')),
            'https://wp-staging.com/privacy-policy/#Dropbox'
        ); ?>
    </p>
    <div class="wpstg-form-group">
        <?php
        if ($isDropboxAuthenticated) {
            ?>
            <strong class="wpstg-mr-10px">
                <?php
                esc_html_e('You are authenticated to Dropbox.', 'wp-staging');
                ?>
            </strong>
            <br>
            <form class="wpstg-provider-revoke-form" id="wpstg-provider-revoke-form" method="post">
                <input type="hidden" name="provider" value="<?php echo esc_attr($providerId); ?>" />
                <button type="button" id="wpstg-btn-provider-revoke" class="wpstg-button--primary wpstg-button--blue"><?php esc_html_e("Logout from Dropbox", "wp-staging") ?></button>
            </form>
            <br/>
            <?php
        } else {
            $authURL = $dropboxStorage->getAuthenticationURL();
            ?>
            <a href="<?php echo esc_url($authURL); ?>" id="wpstg_dropbox_connect" class="wpstg-btn-dropbox">
                <img src="<?php echo esc_url(WPSTG_PLUGIN_URL . 'assets/img/dropbox-icon.svg'); ?>">
                <?php esc_html_e("Sign in with Dropbox", "wp-staging") ?>
            </a>
            <?php
        }
        ?>
    </div>
    <div class="wpstg-form-group">
        <form class="wpstg-provider-settings-form" id="wpstg-provider-settings-form" method="post">
            <input type="hidden" name="provider" value="<?php echo esc_attr($providerId); ?>" />
            <strong><?php esc_html_e('Upload Settings', 'wp-staging') ?></strong>
            <fieldset class="wpstg-fieldset">
                <label><?php esc_html_e('Max Backups to Keep', 'wp-staging') ?></label>
                <input class="wpstg-form-control" type="number" name="max_backups_to_keep" value="<?php echo esc_attr($maxBackupsToKeep); ?>" min="1" style="max-width: 60px" />
            </fieldset>

            <fieldset class="wpstg-fieldset">
                <label><?php esc_html_e('Backup Location', 'wp-staging') ?></label>
                <span>/</span><input class="wpstg-form-control" type="text" style="min-width:300px;" placeholder="/backups" name="folder_name" value="<?php echo esc_attr($folderName); ?>" />
            </fieldset>
            <hr/>
            <button type="button" id="wpstg-btn-save-provider-settings" class="wpstg-button wpstg-blue-primary"><?php esc_html_e("Save Settings", "wp-staging") ?></button><?php require_once "{$this->path}views/settings/tabs/storages/last-saved-notice.php"; ?>
        </form>
    </div>
</fieldset>
