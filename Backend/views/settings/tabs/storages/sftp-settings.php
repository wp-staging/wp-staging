<?php

/**
 * @var string $providerId
 */

use WPStaging\Framework\Facades\Sanitize;

?>
<fieldset>
    <?php
    /** @var \WPStaging\Pro\Backup\Storage\Storages\SFTP\Auth */
    $storage = \WPStaging\Core\WPStaging::make(\WPStaging\Pro\Backup\Storage\Storages\SFTP\Auth::class);
    $options = $storage->getOptions();
    $ftpType = !empty($options['ftpType']) ? Sanitize::sanitizeString($options['ftpType']) : 'ftp';
    $host = !empty($options['host']) ? Sanitize::sanitizeString($options['host']) : '';
    $port = !empty($options['port']) ? Sanitize::sanitizeString($options['port']) : '';
    $username = !empty($options['username']) ? Sanitize::sanitizeString($options['username']) : '';
    $password = !empty($options['password']) ? Sanitize::sanitizePassword($options['password']) : '';
    $ssl = isset($options['ssl']) ? Sanitize::sanitizeBool($options['ssl']) : false;
    $passive = isset($options['passive']) ? Sanitize::sanitizeBool($options['passive']) : false;
    $privateKey = !empty($options['key']) ? Sanitize::sanitizeString($options['key']) : '';
    $passphrase = !empty($options['passphrase']) ? Sanitize::sanitizePassword($options['passphrase']) : '';
    $maxBackupsToKeep = isset($options['maxBackupsToKeep']) ? Sanitize::sanitizeInt($options['maxBackupsToKeep']) : 2;
    $location = isset($options['location']) ? Sanitize::sanitizeString($options['location']) : '';
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
                    <input type="checkbox" name="ssl" value="true" <?php echo $ssl === true ? 'checked ' : '' ?>/>
                </fieldset>

                <fieldset class="wpstg-fieldset only-ftp<?php echo $ftpType === 'ftp' ? '' : ' hidden' ?>">
                    <label><?php esc_html_e('Passive', 'wp-staging') ?></label>
                    <input type="checkbox" name="passive" value="true" <?php echo $passive === true ? 'checked ' : '' ?>/>
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
                <input class="wpstg-form-control" type="number" name="max_backups_to_keep" value="<?php echo esc_attr($maxBackupsToKeep); ?>" style="max-width: 60px" />
                <p><?php esc_html_e("Leave empty or zero for no limit", 'wp-staging') ?></p>
            </fieldset>

            <fieldset class="wpstg-fieldset">
                <label><?php esc_html_e('Location', 'wp-staging') ?></label>
                <input class="wpstg-form-control" type="text" name="location" value="<?php echo esc_attr($location); ?>" />
                <p><?php esc_html_e("Where to change directory to after logging in - often this is relative to your home directory. Needs to already exist", 'wp-staging') ?></p>
            </fieldset>

            <hr/>

            <button type="button" id="wpstg-btn-save-provider-settings" class="wpstg-link-btn wpstg-blue-primary"><?php esc_html_e("Save Settings", "wp-staging") ?></button>
        </form>
    </div>
</fieldset>
