<?php

namespace WPStaging\Storages\SftpSettings;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\UI\Checkbox;
use WPStaging\Pro\Backup\Storage\Storages\SFTP\Auth;

/**
 * @var string $providerId
 */
?>
<fieldset>
    <?php
    /** @var Auth */
    $storage = WPStaging::make(Auth::class);

    $ftpModeOptions = [
        Auth::FTP_UPLOAD_MODE_PUT => 'PUT MODE',
        Auth::FTP_UPLOAD_MODE_APPEND => 'APPEND MODE',
        Auth::FTP_UPLOAD_MODE_NON_BLOCKING => 'NON-BLOCKING MODE',
    ];

    $ftpTypeOptions = [
        Auth::CONNECTION_TYPE_FTP => 'FTP',
        Auth::CONNECTION_TYPE_SFTP => 'SFTP',
    ];

    $providerName = esc_html__('FTP/SFTP', 'wp-staging');
    if ($storage->isEncrypted()) {
        require_once WPSTG_VIEWS_DIR . "settings/tabs/storages/encrypted-notice.php";
    }

    $options          = $storage->getOptions();
    $ftpType          = !empty($options['ftpType']) ? $options['ftpType'] : Auth::CONNECTION_TYPE_FTP;
    $host             = !empty($options['host']) ? $options['host'] : '';
    $port             = !empty($options['port']) ? $options['port'] : '';
    $username         = !empty($options['username']) ? $options['username'] : '';
    $password         = !empty($options['password']) ? $options['password'] : '';
    $ssl              = isset($options['ssl']) ? $options['ssl'] : false;
    $passive          = isset($options['passive']) ? $options['passive'] : false;
    $useFtpExtension  = isset($options['useFtpExtension']) ? $options['useFtpExtension'] : false;
    $ftpMode          = isset($options['ftpMode']) ? $options['ftpMode'] : Auth::FTP_UPLOAD_MODE_PUT;
    $privateKey       = !empty($options['key']) ? $options['key'] : '';
    $passphrase       = !empty($options['passphrase']) ? $options['passphrase'] : '';
    $maxBackupsToKeep = isset($options['maxBackupsToKeep']) ? $options['maxBackupsToKeep'] : 2;
    $maxBackupsToKeep = $maxBackupsToKeep > 0 ? $maxBackupsToKeep : 15;
    $location         = isset($options['location']) ? $options['location'] : '';

    ?>
    <p>
        <strong class="wpstg-fs-18"> <?php echo esc_html($providerName); ?></strong>
    </p>
    <div class="wpstg-form-group">
        <form class="wpstg-provider-settings-form" id="wpstg-provider-settings-form" method="post">
            <div id="wpstg-provider-test-connection-fields">
                <strong><?php esc_html_e('Connection Detail', 'wp-staging') ?></strong>

                <input type="hidden" name="provider" value="<?php echo esc_attr($providerId); ?>" />

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-ftp-type"><?php esc_html_e('FTP/SFTP', 'wp-staging') ?></label>
                    <select id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-ftp-type" class="wpstg-form-control" name="ftp_type">
                        <?php foreach ($ftpTypeOptions as $optionValue => $optionText) : ?>
                        <option value="<?php echo esc_attr($optionValue) ?>"<?php echo $ftpType === $optionValue ? ' selected' : '' ?>><?php echo esc_html($optionText) ?></option>
                        <?php endforeach; ?>
                    </select>
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-host"><?php esc_html_e('Host', 'wp-staging') ?></label>
                    <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-host" class="wpstg-form-control" type="text" name="host" value="<?php echo esc_attr($host); ?>" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-port"><?php esc_html_e('Port', 'wp-staging') ?></label>
                    <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-port" class="wpstg-form-control wpstg-sftp-port-input" type="number" name="port" value="<?php echo esc_attr($port); ?>" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-username"><?php esc_html_e('Username', 'wp-staging') ?></label>
                    <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-username" class="wpstg-form-control" type="text" name="username" value="<?php echo esc_attr($username); ?>" autocomplete="off" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-password"><?php esc_html_e('Password', 'wp-staging') ?></label>
                    <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-password" class="wpstg-form-control" type="password" name="password" autocomplete="new-password" value="<?php echo esc_attr($password); ?>" />
                    <p class="wpstg-only-sftp<?php echo $ftpType === Auth::CONNECTION_TYPE_SFTP ? '' : ' hidden' ?>"><?php esc_html_e("Your login may be either password or key-based - you only need to enter one, not both.", 'wp-staging') ?></p>
                </fieldset>

                <fieldset class="wpstg-fieldset wpstg-only-ftp<?php echo $ftpType === Auth::CONNECTION_TYPE_FTP ? '' : ' hidden' ?>">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-ssl"><?php esc_html_e('SSL', 'wp-staging') ?></label>
                    <?php Checkbox::render("wpstg-storage-provider-{$providerId}-ssl", 'ssl', 'true', $ssl === true); ?>
                </fieldset>

                <fieldset class="wpstg-fieldset wpstg-only-ftp<?php echo $ftpType === Auth::CONNECTION_TYPE_FTP ? '' : ' hidden' ?>">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-passive"><?php esc_html_e('Passive', 'wp-staging') ?></label>
                    <?php Checkbox::render("wpstg-storage-provider-{$providerId}-passive", 'passive', 'true', $passive === true); ?>
                </fieldset>

                <fieldset class="wpstg-fieldset wpstg-only-ftp<?php echo $ftpType === Auth::CONNECTION_TYPE_FTP ? '' : ' hidden' ?>">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-use-ftp-extension"><?php esc_html_e('Activate ftp extension instead of curl', 'wp-staging') ?></label>
                    <?php Checkbox::render("wpstg-storage-provider-{$providerId}-use-ftp-extension", 'use_ftp_extension', 'true', $useFtpExtension === true); ?>
                </fieldset>

                <fieldset class="wpstg-fieldset wpstg-only-ftp<?php echo $ftpType === Auth::CONNECTION_TYPE_FTP ? '' : ' hidden' ?>">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-ftp-mode"><?php esc_html_e('FTP Mode', 'wp-staging') ?></label>
                    <select id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-ftp-mode" class="wpstg-form-control" name="ftp_mode" autocomplete="off">
                        <?php foreach ($ftpModeOptions as $optionValue => $optionText) : ?>
                        <option value="<?php echo esc_attr($optionValue) ?>"<?php echo $ftpMode === $optionValue ? ' selected' : '' ?>><?php echo esc_html($optionText) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p>
                        <?php esc_html_e("Note: APPEND and NON-BLOCKING mode only works if the ftp extension option is activated!", 'wp-staging') ?>
                    </p>
                </fieldset>

                <fieldset class="wpstg-fieldset wpstg-only-sftp<?php echo $ftpType === Auth::CONNECTION_TYPE_SFTP ? '' : ' hidden' ?>">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-key"><?php esc_html_e('Key', 'wp-staging') ?></label>
                    <textarea id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-key" class="wpstg-form-control wpstg-sftp-key-input" name="key"><?php echo esc_textarea($privateKey); ?></textarea>
                    <p><?php esc_html_e("PKCS1 (PEM header: BEGIN RSA PRIVATE KEY), XML and PuTTY format keys are accepted.", 'wp-staging') ?></p>
                </fieldset>

                <fieldset class="wpstg-fieldset wpstg-only-sftp<?php echo $ftpType === Auth::CONNECTION_TYPE_SFTP ? '' : ' hidden' ?>">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-passphrase"><?php esc_html_e('Passphrase', 'wp-staging') ?></label>
                    <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-passphrase" class="wpstg-form-control" type="password" name="passphrase" value="<?php echo esc_attr($passphrase); ?>" autocomplete="off"/>
                    <p><?php esc_html_e("Passphrase for the key.", 'wp-staging') ?></p>
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-folder-name"><?php esc_html_e('Backups Directory Path', 'wp-staging') ?></label>
                    <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-folder-name" class="wpstg-form-control wpstg-sftp-location-input" type="text" placeholder="/backups/example.com/" name="location" value="<?php echo esc_attr($location); ?>" />
                    <p>
                        <?php esc_html_e("This directory must already exist and be an absolute path.", 'wp-staging') ?>
                    </p>
                </fieldset>
            </div>
            <button type="button" id="wpstg-btn-provider-test-connection" class="wpstg-link-btn wpstg-blue-primary"><?php esc_html_e("Test Connection", "wp-staging") ?></button>
            <hr/>
            <strong><?php esc_html_e('Upload Settings', 'wp-staging') ?></strong>
            <fieldset class="wpstg-fieldset">
                <label for="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-max-backups-to-keep"><?php esc_html_e('Max Backups to Keep', 'wp-staging') ?></label>
                <input id="wpstg-storage-provider-<?php echo esc_attr($providerId); ?>-max-backups-to-keep" class="wpstg-form-control wpstg-sftp-port-input" type="number" name="max_backups_to_keep" value="<?php echo esc_attr($maxBackupsToKeep); ?>" min="1" />
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
