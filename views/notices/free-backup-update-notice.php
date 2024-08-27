<?php

use WPStaging\Framework\Notices\FreeBackupUpdateNotice;

?>

<div class="notice-warning notice is-dismissible wpstg-free-backup-update-notice">
    <p>
        <strong><?php esc_html_e('New: ', 'wp-staging'); ?></strong>
        <?php esc_html_e('We\'ve added our premium backup feature to this free version of WP STAGING.', 'wp-staging')?>
        <br>
        <?php esc_html_e('Backup and restore your website with the same high-performance backup engine that is build into the premium version ', 'wp-staging')?><a href="https://wp-staging.com" target="_blank">WP STAGING | PRO</a>!
        <br>
        <?php esc_html_e('All this without any backup size limitation!', 'wp-staging')?>
        <a href="https://wp-staging.com/how-to-backup-and-restore-your-wordpress-website/" target="_blank"><?php esc_html_e('Read More', 'wp-staging') ?></a>
    </p>
</div>

<script>
    jQuery(document).ready(function($) {
        $('.notice.is-dismissible.wpstg-free-backup-update-notice').on('click', '.notice-dismiss', function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    'action': 'wpstg_dismiss_notice',
                    'wpstg_notice': '<?php echo FreeBackupUpdateNotice::OPTION_NAME_FREE_BACKUP_NOTICE_DISMISSED; ?>',
                    'accessToken': wpstg.accessToken,
                    'nonce': wpstg.nonce,
                }
            });
        });
    });
</script>