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

$database   = '';
$username   = '';
$password   = '';
$prefix     = '';
$server     = '';
$isDisabled = false;

if (!$isPro) {
    $isDisabled = true;
}

if ($isPro && !empty($options->current) && $options->current !== null) {
    $database   = isset($options->existingClones[$options->current]['databaseDatabase']) ? $options->existingClones[$options->current]['databaseDatabase'] : '';
    $username   = isset($options->existingClones[$options->current]['databaseUser']) ? $options->existingClones[$options->current]['databaseUser'] : '';
    $prefix     = isset($options->existingClones[$options->current]['databasePrefix']) ? $options->existingClones[$options->current]['databasePrefix'] : '';
    $server     = isset($options->existingClones[$options->current]['databaseServer']) ? $options->existingClones[$options->current]['databaseServer'] : '';
    $isDisabled = true;
    $password   = '*********';
}
?>
<p class="wpstg--advance-settings--checkbox">

    <?php if (!$isPro) { // Show this on only FREE version ?>
    <p class="wpstg-dark-alert"><?php _e('These are Pro Features ', 'wp-staging'); ?>
        <a href="https://wp-staging.com/?utm_source=wp-admin&utm_medium=wp-admin&utm_campaign=db-external&utm_term=db-external" target="_blank" class="wpstg-button--primary wpstg-button--cta-red wpstg-border--violet"><?php _e("Get Started", "wp-staging"); ?></a>
    </p>
    <?php } ?>

    <label for="wpstg-ext-db"><?php _e('Change Database'); ?></label>
    <input type="checkbox" id="wpstg-ext-db" name="wpstg-ext-db" value="true" class="wpstg-toggle-advance-settings-section" data-id="wpstg-external-db-section" <?php echo $isPro === true ? '' : 'disabled' ?> >
    <span class="wpstg--tooltip">
        <img class="wpstg--dashicons" src="<?php echo $scan->getInfoIcon(); ?>" alt="info" />
        <span class="wpstg--tooltiptext">
            <?php _e('You can clone the staging site into a separate database. The Database must be created manually in advance before starting the cloning proccess.<br/><br/><strong>Note:</strong> If there are already tables with the same database prefix and name in this database, the cloning process will be aborted without any further asking!', 'wp-staging'); ?>
        </span>
    </span>
</p>
<div id="wpstg-external-db-section" <?php echo $isPro === true ? 'style="display: none;"' : '' ?> >
    <div class="wpstg-form-group wpstg-text-field">
        <label><?php _e('Server: ', 'wp-staging'); ?> </label>
        <input type="text" class="wpstg-textbox" name="wpstg_db_server" id="wpstg_db_server" value="<?php echo $server; ?>" title="wpstg_db_server" placeholder="localhost" autocapitalize="off" <?php echo $isDisabled ? 'disabled' : '' ?> readonly>
    </div>
    <div class="wpstg-form-group wpstg-text-field">
        <label for="wpstg_db_username"><?php _e('User: ', 'wp-staging'); ?></label>
        <input type="text" class="wpstg-textbox" name="wpstg_db_username" id="wpstg_db_username" value="<?php echo $username; ?>" autocapitalize="off" <?php echo $isDisabled ? 'disabled' : '' ?> readonly />
    </div>
    <div class="wpstg-form-group wpstg-text-field">
        <label for="wpstg_db_password"><?php _e('Password: ', 'wp-staging'); ?></label>
        <input type="password" class="wpstg-textbox" name="wpstg_db_password" id="wpstg_db_password" value="<?php echo $password; ?>" <?php echo $isDisabled ? 'disabled' : '' ?> readonly />
    </div>
    <div class="wpstg-form-group wpstg-text-field">
        <label for="wpstg_db_database"><?php _e('Database: ', 'wp-staging'); ?></label>
        <input type="text" class="wpstg-textbox" name="wpstg_db_database" id="wpstg_db_database" value="<?php echo $database; ?>" autocapitalize="off" <?php echo $isDisabled ? 'disabled' : '' ?> readonly />
    </div>
    <div class="wpstg-form-group wpstg-text-field">
        <label for="wpstg_db_prefix"><?php _e('Database Prefix: ', 'wp-staging'); ?></label>
        <input type="text" class="wpstg-textbox" name="wpstg_db_prefix" id="wpstg_db_prefix" value="<?php echo $prefix; ?>" placeholder="<?php echo $db->prefix; ?>" autocapitalize="off" <?php echo $isDisabled ? 'disabled' : '' ?> readonly />
    </div>
    <div class="wpstg-form-group wpstg-text-field">
        <a href="#" id="wpstg-db-connect"><?php _e("Test Database Connection", "wp-staging"); ?></a>
    </div>
    <hr />
</div>
