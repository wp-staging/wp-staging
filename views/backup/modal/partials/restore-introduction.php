<?php

/**
 * @var string $urlPublic
 */

use WPStaging\Framework\Facades\Escape;

?>
<div class="wpstg--modal--backup--restore--introduction">
    <div class="wpstg--modal--backup--restore--wrapper">
        <div style="text-align: left; padding-left: 8px; padding-right: 8px;">
            <h4 class="wpstg--swal2-title"><strong><?php esc_html_e('Read First', 'wp-staging') ?></strong></h4>
            <?php echo Escape::escapeHtml(__(<<<HTML
<p>Back up your current website before you proceed!</p>
<p class="wpstg-backup-restore-contains-database">This backup restore will replace entirely your website's database. You will be required to log in again with the user/password combination that exists in the backup.</p>
<p class="wpstg-backup-restore-contains-database-multisite">If you restore a multisite backup, all existing network sites will be completely replaced by the network sites from the backup.</p>
<p class="wpstg-backup-restore-contains-files">This restore process extracts files from the backup file to the website and replaces all the files that exist in both the backup and the current website.</p> 
HTML
                    , 'wp-staging')) ?>
        </div>
    </div>
</div>
