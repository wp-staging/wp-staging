<?php

/**
 * @var $this \WPStaging\Framework\Notices\OutdatedWpStagingNotice
 * @see \WPStaging\Framework\Notices\OutdatedWpStagingNotice::showNotice
 */

use WPStaging\Framework\Facades\Escape;

?>
<div class="wpstg-version-outdated-notice notice notice-error">
    <p>
        <strong><?php esc_html_e('WP STAGING - Version Outdated.', 'wp-staging'); ?></strong> <br/>
        <?php echo sprintf(
            Escape::escapeHtml(__('WP STAGING v%s is outdated. Please <a href="%s">update</a> to latest version %s to make sure the plugin works with your WordPress version.', 'wp-staging')),
            esc_html($this->getCurrentWpstgVersion()),
            esc_url(admin_url('plugins.php')),
            esc_html($this->getLatestWpstgVersion())
        ); ?>
    </p>
</div>
