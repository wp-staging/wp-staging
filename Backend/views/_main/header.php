<span class="wpstg-logo">
    <img src="<?php echo $this->url . "img/logo_clean_small_212_25.png" ?>">
</span>

<span class="wpstg-version">
    <?php if (defined('WPSTGPRO_VERSION')) echo "Pro" ?> Version <?php echo WPStaging\Core\WPStaging::getVersion() ?>
</span>

<div class="wpstg-header">
    <div class='wpstg-share-button-container'>
        <div class='wpstg-share-button wpstg-share-button-twitter' data-share-url="https://wordpress.org/plugins/wp-staging">
            <div class='box'>
                <a href="https://twitter.com/intent/tweet?button_hashtag=wpstaging&text=Check%20out%20this%20plugin%20for%20creating%20a%20one-click%20WordPress%20testing%20site&via=wpstg" target='_blank'>
                    <span class='wpstg-share'><?php echo __('Tweet #wpstaging', 'wp-staging'); ?></span>
                </a>
            </div>
        </div>
        <div class="wpstg-share-button wpstg-share-button-twitter">
            <div class="box">
                <a href="https://twitter.com/intent/follow?original_referer=http%3A%2F%2Fsrc.wordpress-develop.dev%2Fwp-admin%2Fadmin.php%3Fpage%3Dwpstg-settings&ref_src=twsrc%5Etfw&region=follow_link&screen_name=wpstg&tw_p=followbutton" target="_blank">
                    <span class='wpstg-share'><?php echo __('Follow @wpstg', 'wp-staging'); ?></span>
                </a>
            </div>
        </div>
        <div class="wpstg-share-button wpstg-share-button-facebook" data-share-url="https://wordpress.org/plugins/wp-staging">
            <div class="box">
                <a href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fwp-staging.com%2Fwow-cloning-wordpress-websites-has-never-been-easier%2F" target="_blank">
                    <span class='wpstg-share'><?php echo __('Share on Facebook', 'wp-staging'); ?></span>
                </a>
            </div>
        </div>
    </div>

    <?php if ($_GET['page'] === 'wpstg_clone') { ?>

        <div class="wpstg-fs-14">
            <?php _e("Tutorial:", "wp-staging") ?> <a href="https://wp-staging.com/docs/copy-staging-site-to-live-site/" target="_blank"><?php _e("Push staging site to production website", "wp-staging") ?></a>
        </div>

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
            <strong><?php echo sprintf(__("WP STAGING Pro %s is available!", 'wp-staging'), $latestReleasedVersion); ?></strong><br/>
            <?php echo sprintf(__('Important: Please update WP STAGING Pro before pushing the staging site to production site. <a href="%s" target="_blank">What\'s New?</a>', 'wp-staging'), 'https://wp-staging.com/wp-staging-pro-changelog'); ?>
        </div>

    <?php } ?>

</div>