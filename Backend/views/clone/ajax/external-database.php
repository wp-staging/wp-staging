<?php
/**
 * This file is currently being called only for the Free version:
 * src/Backend/views/clone/ajax/scan.php:113
 *
 * @file src/Backend/Pro/views/clone/ajax/external-database.php For the Pro counterpart.
 */
?>
<fieldset disabled class="wpstg-opacity-80">
    <p><strong class="wpstg-fs-14">
        <?php _e('Copy Staging Site to Separate Database', 'wp-staging'); ?></strong>
        <br><?php _e('Database must be created manually in advance!', 'wp-staging'); ?>
    </p>
    <div id="wpstg-external-db">
        <div class="wpstg-form-group wpstg-text-field">
            <label><?php _e('Server: ', 'wp-staging'); ?> </label>
            <input type="text" name="wpstg_db_server" id="wpstg_db_server" value="" title="wpstg_db_server" placeholder="localhost" autocapitalize="off" readonly>
        </div>
        <div class="wpstg-form-group wpstg-text-field">
            <label><?php _e('User: ', 'wp-staging'); ?></label>
            <input type="text" name="wpstg_db_username" id="wpstg_db_username" value="" autocapitalize="off" class="" readonly>
        </div>
        <div class="wpstg-form-group wpstg-text-field">
            <label><?php _e('Password: ', 'wp-staging'); ?></label>
            <input type="password" name="wpstg_db_password" id="wpstg_db_password" class="" readonly>
        </div>
        <div class="wpstg-form-group wpstg-text-field">
            <label><?php _e('Database: ', 'wp-staging'); ?></label>
            <input type="text" name="wpstg_db_database" id="wpstg_db_database" value="" autocapitalize="off" readonly>
        </div>
        <div class="wpstg-form-group wpstg-text-field">
            <label><?php _e('Database Prefix: ', 'wp-staging'); ?></label>
            <input type="text" name="wpstg_db_prefix" id="wpstg_db_prefix" value="" placeholder="<?php echo $db->prefix; ?>" autocapitalize="off" readonly>
        </div>
        <div class="wpstg-form-group wpstg-text-field">
            <label>&nbsp;</label>
            <a href="#" id="wpstg-db-connect"><?php _e("Test Database Connection", "wp-staging"); ?></a>
        </div>
    </div>
</fieldset>

