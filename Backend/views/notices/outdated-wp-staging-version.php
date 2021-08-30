<?php
/**
 * @var $this \WPStaging\Backend\Notices\OutdatedWpStagingNotice
 * @see \WPStaging\Backend\Notices\OutdatedWpStagingNotice::showNotice
 */
?>
<div class="wpstg-version-outdated-notice notice notice-error">
    <p>
        <strong><?php _e('WP STAGING - Version Outdated.', 'wp-staging'); ?></strong> <br/>
        <?php echo sprintf(__('WP STAGING v%s is outdated. Please <a href="%s">update</a> to latest version %s to make sure the plugin works with your WordPress version.', 'wp-staging'), $this->getCurrentWpstgVersion(), admin_url('plugins.php'), $this->getLatestWpstgVersion()); ?>
    </p>
</div>
