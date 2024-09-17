<?php

/**
 * This file is currently being called for both FREE and PRO version:
 * src/views/clone/ajax/scan.php
 *
 * @var \WPStaging\Backend\Modules\Jobs\Scan $scan
 * @var stdClass                             $options
 * @var boolean                              $isPro
 * @var \wpdb                                $db
 *
 * @see \WPStaging\Backend\Modules\Jobs\Scan::start For details on $options.
 */

use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Facades\UI\Checkbox;

$database   = '';
$username   = '';
$password   = '';
$prefix     = '';
$server     = '';
$useSsl     = false;
$isDisabled = false;

if (!$isPro) {
    $isDisabled = true;
}

if ($isPro && !empty($options->current) && $options->current !== null) {
    $database   = isset($options->existingClones[$options->current]['databaseDatabase']) ? Sanitize::sanitizeString($options->existingClones[$options->current]['databaseDatabase']) : '';
    $username   = isset($options->existingClones[$options->current]['databaseUser']) ? Sanitize::sanitizeString($options->existingClones[$options->current]['databaseUser']) : '';
    $prefix     = isset($options->existingClones[$options->current]['databasePrefix']) ? Sanitize::sanitizeString($options->existingClones[$options->current]['databasePrefix']) : '';
    $server     = isset($options->existingClones[$options->current]['databaseServer']) ? Sanitize::sanitizeString($options->existingClones[$options->current]['databaseServer']) : '';
    $useSsl     = !empty($options->existingClones[$options->current]['databaseSsl']);
    $isDisabled = true;
    $password   = '*********';
}

/**
 * Avoid renaming the 'wpstg-db-user' field to 'wpstg-db-username' or simply 'username',
 * and 'wpstg-db-pass' to 'wpstg-db-password' or 'password'.
 * Renaming may lead to unintended autofill behavior if the fields are disabled.
 */
?>

<div class="wpstg--advanced-settings--checkbox">
    <label for="wpstg-ext-db"><?php esc_html_e('Change Database', 'wp-staging'); ?></label>
    <?php Checkbox::render('wpstg-ext-db', 'wpstg-ext-db', 'true', false, ['classes' => 'wpstg-toggle-advance-settings-section', 'isDisabled' => !$isPro], ['id' => 'wpstg-external-db-section']); ?>
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo esc_attr($scan->getInfoIcon()); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php echo wp_kses_post(__('You can clone the staging site into a separate database. The Database must be created manually in advance before starting the cloning proccess.<br/><br/><strong>Note:</strong> If there are already tables with the same database prefix and name in this database, the cloning process will be aborted without any further asking!', 'wp-staging')); ?>
        </span>
    </span>
</div>
<div id="wpstg-external-db-section" <?php echo $isPro === true ? 'style="display: none;"' : '' ?>>
    <div class="wpstg-form-group wpstg-text-field">
        <label for="wpstg-db-server"><?php esc_html_e('Server: ', 'wp-staging'); ?> </label>
        <input type="text" class="wpstg-textbox" name="wpstg-db-server" id="wpstg-db-server" value="<?php echo esc_attr($server); ?>" title="wpstg-db-server" placeholder="localhost" autocapitalize="off" <?php echo $isDisabled ? 'disabled' : '' ?> readonly>
    </div>
    <div class="wpstg-form-group wpstg-text-field">
        <label for="wpstg-db-user"><?php esc_html_e('User: ', 'wp-staging'); ?></label>
        <input type="text" class="wpstg-textbox" name="wpstg-db-user" id="wpstg-db-user" value="<?php echo esc_attr($username); ?>" autocapitalize="off" <?php echo $isDisabled ? 'disabled' : '' ?> readonly />
    </div>
    <form>
        <div class="wpstg-form-group wpstg-text-field">
            <label for="wpstg-db-pass"><?php esc_html_e('Password: ', 'wp-staging'); ?></label>
            <input type="password" class="wpstg-textbox" name="wpstg-db-pass" id="wpstg-db-pass" value="<?php echo esc_attr($password); ?>" <?php echo $isDisabled ? 'disabled' : '' ?> readonly autocomplete="off" />
        </div>
    </form>
    <div class="wpstg-form-group wpstg-text-field">
        <label for="wpstg-db-database"><?php esc_html_e('Database: ', 'wp-staging'); ?></label>
        <input type="text" class="wpstg-textbox" name="wpstg-db-database" id="wpstg-db-database" value="<?php echo esc_attr($database); ?>" autocapitalize="off" <?php echo $isDisabled ? 'disabled' : '' ?> readonly />
    </div>
    <div class="wpstg-form-group wpstg-text-field">
        <label for="wpstg-db-prefix"><?php esc_html_e('Database Prefix: ', 'wp-staging'); ?></label>
        <input type="text" class="wpstg-textbox" name="wpstg-db-prefix" id="wpstg-db-prefix" value="<?php echo esc_attr($prefix); ?>" placeholder="<?php echo $db->prefix; ?>" autocapitalize="off" <?php echo $isDisabled ? 'disabled' : '' ?> readonly />
    </div>
    <div class="wpstg--advanced-settings--checkbox">
        <label for="wpstg-db-ssl"><?php esc_html_e('Enable SSL: ', 'wp-staging'); ?></label>
        <?php Checkbox::render('wpstg-db-ssl', 'wpstg-db-ssl', 'true', $useSsl); ?>
    </div>
    <div class="wpstg-form-group wpstg-text-field wpstg-mt-10px">
        <a href="#" id="wpstg-db-connect"><?php esc_html_e("Test Database Connection", "wp-staging"); ?></a>
    </div>
    <hr />
</div>
