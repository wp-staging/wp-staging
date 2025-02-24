<div class="wpstg--modal--backup--restore--introduction">
    <div class="wpstg--modal--backup--restore--wrapper">
        <div style="text-align: left;">
            <h4 class="wpstg--swal2-title wpstg-restore-title"><strong><?php esc_html_e('Read First', 'wp-staging') ?></strong></h4>
            <p><?php esc_html_e('Back up your current website before you proceed!', 'wp-staging') ?></p>
            <p class="wpstg-backup-restore-contains-database"><?php esc_html_e('Restoring this backup will completely replace your website\'s database. After the process has finished, you will need to log in again using the username/password combination for your backed up site.', 'wp-staging') ?></p>
            <p class="wpstg-backup-restore-contains-database-subsite"><?php esc_html_e('Restoring this backup will only replace your current subsite\'s database tables. Users will be imported from the backup which doesn\'t already exists in your site. After the process has finished, you will need to log in again using the username/password combination for your backed up site.', 'wp-staging') ?></p>
            <p class="wpstg-backup-restore-contains-database-multisite"><?php esc_html_e('Also all current network sites will be deleted and replaced by the sites in the backup.', 'wp-staging') ?></p>
            <p class="wpstg-backup-restore-contains-files"><?php esc_html_e('The restore process extracts files from the backup and replaces any existing files on your website with those from the backup unless excluded by filter.', 'wp-staging') ?></p>
            <p class="wpstg-backup-restore-contains-files-subsite"><?php esc_html_e('The restore process extracts files from the backup but only add those files which are not on your site and will preserve your existing files (except media) unless this behaviour is changed by filter.', 'wp-staging') ?></p>
        </div>
    </div>
</div>
