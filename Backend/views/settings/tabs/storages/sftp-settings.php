<?php

namespace WPStaging\Storages\SftpSettings;

/**
 * @var string $providerId
 */

?>
<fieldset>
    <?php
    /** @var \WPStaging\Pro\Backup\Storage\Storages\SFTP\Auth */
    $storage = \WPStaging\Core\WPStaging::make(\WPStaging\Pro\Backup\Storage\Storages\SFTP\Auth::class);
    $options = $storage->getOptions();
    $ftpType = !empty($options['ftpType']) ? $options['ftpType'] : 'ftp';
    $host = !empty($options['host']) ? $options['host'] : '';
    $port = !empty($options['port']) ? $options['port'] : '';
    $username = !empty($options['username']) ? $options['username'] : '';
    $password = !empty($options['password']) ? $options['password'] : '';
    $ssl = isset($options['ssl']) ? $options['ssl'] : false;
    $passive = isset($options['passive']) ? $options['passive'] : false;
    $privateKey = !empty($options['key']) ? $options['key'] : '';
    $passphrase = !empty($options['passphrase']) ? $options['passphrase'] : '';
    $maxBackupsToKeep = isset($options['maxBackupsToKeep']) ? $options['maxBackupsToKeep'] : 2;
    $maxBackupsToKeep = $maxBackupsToKeep > 0 ? $maxBackupsToKeep : 15;
    $location = isset($options['location']) ? $options['location'] : '';

    ?>
    <p>
        <strong class="wpstg-fs-14"> <?php esc_html_e('FTP/SFTP', 'wp-staging'); ?></strong>
    </p>
    <div class="wpstg-form-group">
        <form class="wpstg-provider-settings-form" id="wpstg-provider-settings-form" method="post">
            <div id="wpstg-provider-test-connection-fields">
                <strong><?php esc_html_e('Connection Detail', 'wp-staging') ?></strong>

                <input type="hidden" name="provider" value="<?php echo esc_attr($providerId); ?>" />

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('FTP/SFTP', 'wp-staging') ?></label>
                    <select class="wpstg-form-control" name="ftp_type">
                        <option value="ftp"<?php echo $ftpType === 'ftp' ? ' selected' : '' ?>><?php esc_html_e('FTP', 'wp-staging') ?></option>
                        <option value="sftp"<?php echo $ftpType === 'sftp' ? ' selected' : '' ?>><?php esc_html_e('SFTP', 'wp-staging') ?></option>
                    </select>
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('Host', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="text" name="host" value="<?php echo esc_attr($host); ?>" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('Port', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="number" name="port" value="<?php echo esc_attr($port); ?>" style="max-width: 60px" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('Username', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="text" name="username" value="<?php echo esc_attr($username); ?>" />
                </fieldset>

                <fieldset class="wpstg-fieldset">
                    <label><?php esc_html_e('Password', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="password" name="password" autocomplete="new-password" value="<?php echo esc_attr($password); ?>" />
                    <p class="only-sftp<?php echo $ftpType === 'sftp' ? '' : ' hidden' ?>"><?php esc_html_e("Your login may be either password or key-based - you only need to enter one, not both.", 'wp-staging') ?></p>
                </fieldset>

                <fieldset class="wpstg-fieldset only-ftp<?php echo $ftpType === 'ftp' ? '' : ' hidden' ?>">
                    <label><?php esc_html_e('SSL', 'wp-staging') ?></label>
                    <input type="checkbox" class="wpstg-checkbox" name="ssl" value="true" <?php echo $ssl === true ? 'checked ' : '' ?>/>
                </fieldset>

                <fieldset class="wpstg-fieldset only-ftp<?php echo $ftpType === 'ftp' ? '' : ' hidden' ?>">
                    <label><?php esc_html_e('Passive', 'wp-staging') ?></label>
                    <input type="checkbox" class="wpstg-checkbox" name="passive" value="true" <?php echo $passive === true ? 'checked ' : '' ?>/>
                </fieldset>

                <fieldset class="wpstg-fieldset only-sftp<?php echo $ftpType === 'sftp' ? '' : ' hidden' ?>">
                    <label><?php esc_html_e('Key', 'wp-staging') ?></label>
                    <textarea class="wpstg-form-control" name="key" style="width:350px;height:200px;"><?php echo esc_textarea($privateKey); ?></textarea>
                    <p><?php esc_html_e("PKCS1 (PEM header: BEGIN RSA PRIVATE KEY), XML and PuTTY format keys are accepted.", 'wp-staging') ?></p>
                </fieldset>

                <fieldset class="wpstg-fieldset only-sftp<?php echo $ftpType === 'sftp' ? '' : ' hidden' ?>">
                    <label><?php esc_html_e('Passphrase', 'wp-staging') ?></label>
                    <input class="wpstg-form-control" type="password" name="passphrase" value="<?php echo esc_attr($passphrase); ?>" />
                    <p><?php esc_html_e("Passphrase for the key.", 'wp-staging') ?></p>
                </fieldset>
            </div>
            <button type="button" id="wpstg-btn-provider-test-connection" class="wpstg-link-btn wpstg-blue-primary"><?php esc_html_e("Test Connection", "wp-staging") ?></button>

            <hr/>
            <strong><?php esc_html_e('Upload Settings', 'wp-staging') ?></strong>
            <fieldset class="wpstg-fieldset">
                <label><?php esc_html_e('Max Backups to Keep', 'wp-staging') ?></label>
                <input class="wpstg-form-control" type="number" name="max_backups_to_keep" value="<?php echo esc_attr($maxBackupsToKeep); ?>" min="1" style="max-width: 60px" />
            </fieldset>

            <fieldset class="wpstg-fieldset">
                <label><?php esc_html_e('Directory Path', 'wp-staging') ?></label>
                <input class="wpstg-form-control" style="min-width:300px;" type="text" placeholder="/backups/example.com/" name="location" value="<?php echo esc_attr($location); ?>" />
                <br><br><?php esc_html_e("Add the directory to which you want to upload the backup files.", 'wp-staging') ?>
                <br>
                <?php esc_html_e("This directory must already exist and be relative to the FTP user's home directory.", 'wp-staging') ?>
            </fieldset>

            <button type="button" id="wpstg-btn-save-provider-settings" class="wpstg-button wpstg-blue-primary"><?php esc_html_e("Save Settings", "wp-staging") ?></button><?php require_once "{$this->path}views/settings/tabs/storages/last-saved-notice.php"; ?>
        </form>
    </div>
</fieldset>
