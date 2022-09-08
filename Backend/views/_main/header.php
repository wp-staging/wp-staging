

<div>
<span class="wpstg-logo">
    <img src="<?php echo esc_url($this->assets->getAssetsUrl("img/logo.svg")) ?>" width="212">
</span>

<span class="wpstg-version">
    <?php if (defined('WPSTGPRO_VERSION')) {
        echo "PRO";
    } ?> v. <?php echo esc_html(WPStaging\Core\WPStaging::getVersion()) ?>
</span>
</div>
<div class="wpstg-header">
    <?php if (isset($_GET['page']) && $_GET['page'] === 'wpstg_clone') { ?>
        <?php
        $latestReleasedVersion = get_option('wpstg_version_latest');
        $display = 'none;';

        if (defined('WPSTGPRO_VERSION')) {
            $outdatedVersionCheck = new WPStaging\Backend\Notices\OutdatedWpStagingNotice();
            $latestReleasedVersion = $outdatedVersionCheck->getLatestWpstgProVersion();
            if ($outdatedVersionCheck->isOutdatedWpStagingProVersion()) {
                $display = 'block;';
            }
        }
        ?>

        <div id="wpstg-update-notify" style="display:<?php echo esc_attr($display); ?>">
            <strong><?php echo sprintf(__("New: WP STAGING PRO v. %s is available.", 'wp-staging'), esc_html($latestReleasedVersion)); ?></strong><br/>
            <?php echo sprintf(__('Important: Please update the plugin before pushing the staging site to production site. <a href="%s" target="_blank">What\'s New?</a>', 'wp-staging'), 'https://wp-staging.com/wp-staging-pro-changelog'); ?>
        </div>

    <?php } ?>

</div>
