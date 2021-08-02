<?php
/**
 * @var string $urlPublic
 */
?>
<div class="wpstg--modal--backup--import--introduction">
    <div class="wpstg--modal--backup--import--wrapper">
        <div style="text-align: left; padding-left: 8px; padding-right: 8px;">
            <h4 class="wpstg--swal2-title"><strong><?php esc_html_e('Read First', 'wp-staging') ?></strong></h4>
            <?php echo wp_kses_post(__(<<<HTML
<p>Restoring a Backup is a beta feature. Back up your current website first before proceeding.</p>
<p class="wpstg-backup-restore-contains-database">This site's database is going to be entirely replaced with the database from the backup, and you will be required to log in again with the user/password that exists in the backup.</p>
<p class="wpstg-backup-restore-contains-files">The restore process will add the files from the backup to the site, replacing the ones that exist both in the backup and in the site.</p> 
HTML
                    , 'wp-staging')) ?>
        </div>
    </div>
</div>
