<fieldset disabled style="opacity:0.8;">
<p><?php _e('Clone the staging site to another separate database. You need to create the database in advance!
    Leave User and Password empty to clone the staging site to the current main database.', 'wp-staging'); ?></p>
<table cellspacing="0" id="wpstg-external-db">
    <tbody>
        <tr><th>Server</th><td><input type="text" name="wpstg_db_server" id="wpstg_db_server" value="" title="wpstg_db_server" placeholder="localhost" autocapitalize="off">
            </td></tr>
        <tr><th>User</th><td><input type="text" name="wpstg_db_username" id="wpstg_db_username" value="" autocapitalize="off" class="">
            </td></tr>
        <tr><th>Password</th><td><input type="password" name="wpstg_db_password" id="wpstg_db_password" class="">
            </td></tr>
        <tr><th>Database</th><td><input type="text" name="wpstg_db_database" id="wpstg_db_database" value="" autocapitalize="off">
            </td></tr>
        <tr><th>Database Prefix</th><td><input type="text" name="wpstg_db_prefix" id="wpstg_db_prefix" value="" placeholder="<?php echo $db->prefix; ?>" autocapitalize="off">
            </td></tr>
        <tr><th><a href="#" id="wpstg-db-connect">Test Database Connection</a></th><td>
            </td></tr>
    </tbody>
</table>
</fieldset>
<p style="font-weight:bold;background-color:#e6e6e6;padding:15px;"><?php _e('This is WP Staging Pro feature', 'wp-staging'); ?>
    <br>
    <a href="https://wp-staging.com" target="_blank" class="quads-button green wpstg-button" style="border-radius:2px;font-size: 14px;">Get WP Staging Pro</a>
</p>
