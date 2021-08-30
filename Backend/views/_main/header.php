

<div>
<span class="wpstg-logo">
    <img src="<?php echo $this->assets->getAssetsUrl("img/logo.svg") ?>" width="212">
</span>

<span class="wpstg-version">
    <?php if (defined('WPSTGPRO_VERSION')) {
        echo "PRO";
    } ?> v. <?php echo WPStaging\Core\WPStaging::getVersion() ?>
</span>
</div>
<div class="wpstg-header">
    <?php if ($_GET['page'] === 'wpstg_clone') { ?>
        <?php
        $latestReleasedVersion = get_option('wpstg_version_latest');
        $display = 'none;';

        if (defined('WPSTGPRO_VERSION')) {
            if (!empty($latestReleasedVersion) && version_compare(WPStaging\Core\WPStaging::getVersion(), $latestReleasedVersion, '<')) {
                $display = 'block;';
            }
        }
        ?>

        <div id="wpstg-update-notify" style="display:<?php echo $display; ?>">
            <strong><?php echo sprintf(__("New: WP STAGING PRO v. %s is available.", 'wp-staging'), $latestReleasedVersion); ?></strong><br/>
            <?php echo sprintf(__('Important: Please update the plugin before pushing the staging site to production site. <a href="%s" target="_blank">What\'s New?</a>', 'wp-staging'), 'https://wp-staging.com/wp-staging-pro-changelog'); ?>
        </div>

    <?php } ?>

</div>