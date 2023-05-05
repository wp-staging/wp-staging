<?php

/**
 * This file is currently being called for both FREE and PRO version:
 * src/Backend/views/clone/ajax/scan.php:62
 *
 * @var \WPStaging\Backend\Modules\Jobs\Scan $scan
 * @var stdClass                             $options
 * @var boolean                              $isPro
 * @var \wpdb                                $db
 *
 * @see \WPStaging\Backend\Modules\Jobs\Scan::start For details on $options.
 */

use WPStaging\Framework\Facades\Sanitize;

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
?>
<p class="wpstg--advance-settings--checkbox">

    <?php if (!$isPro) { // Show this on only FREE version ?>
    <p class="wpstg-dark-alert"><?php esc_html_e('These are premium features ', 'wp-staging'); ?>
        <a href="https://wp-staging.com/?utm_source=wp-admin&utm_medium=wp-admin&utm_campaign=db-external&utm_term=db-external" target="_blank" class="wpstg-button--primary wpstg-button--cta-red wpstg-border--violet"><?php esc_html_e("Get Started", "wp-staging"); ?></a>
    </p>
    <?php } ?>

    <label for="wpstg-ext-db"><?php esc_html_e('Change Database'); ?></label>
    <input type="checkbox" id="wpstg-ext-db" name="wpstg-ext-db" value="true" class="wpstg-toggle-advance-settings-section wpstg-checkbox" data-id="wpstg-external-db-section" <?php echo $isPro === true ? '' : 'disabled' ?>>
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo esc_attr($scan->getInfoIcon()); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php echo wp_kses_post(__('You can clone the staging site into a separate database. The Database must be created manually in advance before starting the cloning proccess.<br/><br/><strong>Note:</strong> If there are already tables with the same database prefix and name in this database, the cloning process will be aborted without any further asking!', 'wp-staging')); ?>
        </span>
    </span>
</p>
<div id="wpstg-external-db-section" <?php echo $isPro === true ? 'style="display: none;"' : '' ?>>
    <div class="wpstg-form-group wpstg-text-field">
        <label><?php esc_html_e('Server: ', 'wp-staging'); ?> </label>
        <input type="text" class="wpstg-textbox" name="wpstg_db_server" id="wpstg_db_server" value="<?php echo esc_attr($server); ?>" title="wpstg_db_server" placeholder="localhost" autocapitalize="off" <?php echo $isDisabled ? 'disabled' : '' ?> readonly>
    </div>
    <div class="wpstg-form-group wpstg-text-field">
        <label for="wpstg_db_username"><?php esc_html_e('User: ', 'wp-staging'); ?></label>
        <input type="text" class="wpstg-textbox" name="wpstg_db_username" id="wpstg_db_username" value="<?php echo esc_attr($username); ?>" autocapitalize="off" <?php echo $isDisabled ? 'disabled' : '' ?> readonly />
    </div>
    <div class="wpstg-form-group wpstg-text-field">
        <label for="wpstg_db_password"><?php esc_html_e('Password: ', 'wp-staging'); ?></label>
        <input type="password" class="wpstg-textbox" name="wpstg_db_password" id="wpstg_db_password" value="<?php echo esc_attr($password); ?>" <?php echo $isDisabled ? 'disabled' : '' ?> readonly />
    </div>
    <div class="wpstg-form-group wpstg-text-field">
        <label for="wpstg_db_database"><?php esc_html_e('Database: ', 'wp-staging'); ?></label>
        <input type="text" class="wpstg-textbox" name="wpstg_db_database" id="wpstg_db_database" value="<?php echo esc_attr($database); ?>" autocapitalize="off" <?php echo $isDisabled ? 'disabled' : '' ?> readonly />
    </div>
    <div class="wpstg-form-group wpstg-text-field">
        <label for="wpstg_db_prefix"><?php esc_html_e('Database Prefix: ', 'wp-staging'); ?></label>
        <input type="text" class="wpstg-textbox" name="wpstg_db_prefix" id="wpstg_db_prefix" value="<?php echo esc_attr($prefix); ?>" placeholder="<?php echo $db->prefix; ?>" autocapitalize="off" <?php echo $isDisabled ? 'disabled' : '' ?> readonly />
    </div>
    <div class="wpstg-form-group">
        <div class="wpstg-checkbox">
            <label for="wpstg_db_ssl"><?php esc_html_e('Enable SSL: ', 'wp-staging'); ?></label>
            <input type="checkbox" id="wpstg_db_ssl" name="wpstg_db_ssl" value="true" class="wpstg-checkbox" <?php echo $useSsl ? "checked" : '';?> readonly />
        </div>
    </div>
    <div class="wpstg-form-group wpstg-text-field wpstg-mt-10px">
        <a href="#" id="wpstg-db-connect"><?php esc_html_e("Test Database Connection", "wp-staging"); ?></a>
    </div>
    <hr />
</div>
