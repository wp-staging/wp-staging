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
<table cellspacing="0" id="wpstg-external-db">
    <tbody>
        <tr><th>Server</th><td><input type="text" name="wpstg_db_server" id="wpstg_db_server" value="" title="wpstg_db_server" placeholder="localhost" autocapitalize="off" readonly>
            </td></tr>
        <tr><th>User</th><td><input type="text" name="wpstg_db_username" id="wpstg_db_username" value="" autocapitalize="off" class="" readonly>
            </td></tr>
        <tr><th>Password</th><td><input type="password" name="wpstg_db_password" id="wpstg_db_password" class="" readonly>
            </td></tr>
        <tr><th>Database</th><td><input type="text" name="wpstg_db_database" id="wpstg_db_database" value="" autocapitalize="off" readonly>
            </td></tr>
        <tr><th>Database Prefix</th><td><input type="text" name="wpstg_db_prefix" id="wpstg_db_prefix" value="" placeholder="<?php echo $db->prefix; ?>" autocapitalize="off" readonly>
            </td></tr>
        <tr><th></th> <td><a href="#" id="wpstg-db-connect"><?php _e("Test Database Connection", "wp-staging")?></a></td></tr>
    </tbody>
</table>
</fieldset>

