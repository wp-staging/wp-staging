<?php
/**
 * @var \WPStaging\Framework\Notices\BackupPluginsNotice $this
 */
?>

<div class="wpstg-backup-plugin-notice-container">
    <div class="wpstg-arrow-up"></div>
    <div class="wpstg-backup-plugin-notice-wrapper">
        <p>
            <?php esc_html_e('We\'ve noticed that you\'re using another plugin for creating WordPress backups.
             Did you know that WP Staging is significant faster than other popular backup plugins?', 'wp-staging');?>
        </p>
        <p>
            <?php esc_html_e('Even the WP Staging free version supports scheduled backups without any file size limitation!', 'wp-staging');?>
        </p>
        <p class="wpstg-backup-plugin-tryout">
            <strong><?php esc_html_e('Try out now!', 'wp-staging');?></strong>
        </p>
        <div class="wpstg-backup-plugin-action-container">
            <div>
                <a href="<?php echo esc_url('https://wp-staging.com/wp-staging-benchmarks/', 'wp-staging');?>" target="_blank"
                   class="wpstg-backup-read-report-button"><?php esc_html_e('Read the performance report', 'wp-staging'); ?></a>
            </div>
            <div class="wpstg-backup-plugin-actions-button">
                <a href="javascript:void(0)" id="wpstg-backup-plugin-notice-close"><?php esc_html_e('Close', 'wp-staging'); ?></a>
                <span class="wpstg-backup-plugin-actions-separator">|</span>
                <a href="javascript:void(0)" id="wpstg-backup-plugin-notice-remind-me"><?php esc_html_e('Show again in 3 days', 'wp-staging'); ?></a>
            </div>
        </div>
    </div>
</div>
