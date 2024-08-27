<?php
/**
 * @var \WPStaging\Framework\Analytics\AnalyticsConsent $this
 */

?>
<div class="notice notice-warning">
    <p><strong><?php echo esc_html__('WP STAGING - Communication with WP STAGING server failed', 'wp-staging') ?></strong></p>
    <p><?php echo esc_html__('We could not reach WP STAGING servers, so sending diagnostic information has been disabled .', 'wp-staging') ?></p>
    <p><?php echo esc_html__('This can be caused by a firewall, WordPress blocking external requests, a security plugin, or WP STAGING servers might be down.', 'wp-staging') ?></p>
    <p><?php echo sprintf(wp_kses('No action is necessary on your part. You can always re-enable WP STAGING diagnostic monitoring on the <a href="%s" target="_blank">settings page</a>.', 'wp-staging'), esc_url(admin_url() . 'admin.php?page=wpstg-settings')) ?></p>
</div>
