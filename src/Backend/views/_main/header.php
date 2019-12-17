<span class="wp-staginglogo">
    <img src="<?php echo $this->url . "img/logo_clean_small_212_25.png" ?>">
</span>

<span class="wpstg-version">
    <?php if( WPStaging\WPStaging::getSlug() === "wp-staging-pro" ) echo "Pro" ?> Version <?php echo WPStaging\WPStaging::getVersion() ?>
</span>

<div class="wpstg-header">
    <div class='wpstg-share-button-container'>
        <div class='wpstg-share-button wpstg-share-button-twitter' data-share-url="https://wordpress.org/plugins/wp-staging">
            <div clas='box'>
                <a href="https://twitter.com/intent/tweet?button_hashtag=wpstaging&text=Check%20out%20this%20plugin%20for%20creating%20a%20one-click%20WordPress%20testing%20site&via=wpstg" target='_blank'>
                    <span class='wpstg-share'><?php echo __( 'Tweet #wpstaging', 'wp-staging' ); ?></span>
                </a>
            </div>
        </div>
        <div class="wpstg-share-button wpstg-share-button-twitter">
            <div class="box">
                <a href="https://twitter.com/intent/follow?original_referer=http%3A%2F%2Fsrc.wordpress-develop.dev%2Fwp-admin%2Fadmin.php%3Fpage%3Dwpstg-settings&ref_src=twsrc%5Etfw&region=follow_link&screen_name=wpstg&tw_p=followbutton" target="_blank">
                    <span class='wpstg-share'><?php echo __( 'Follow @wpstg', 'wp-staging' ); ?></span>
                </a>
            </div>
        </div>
        <div class="wpstg-share-button wpstg-share-button-facebook" data-share-url="https://wordpress.org/plugins/wp-staging">
            <div class="box">
                <a href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fwp-staging.com%2Fwow-cloning-wordpress-websites-has-never-been-easier%2F" target="_blank">
                    <span class='wpstg-share'><?php echo __( 'Share on Facebook', 'wp-staging' ); ?></span>
                </a>
            </div>
        </div>
    </div>
    <div style="font-size:14px;">
        <?php _e("Tutorial:", "wp-staging")?> <a href="https://wp-staging.com/docs/copy-staging-site-to-live-site/" target="_blank"><?php _e("Push staging site to production website", "wp-staging")?></a>
        <!--<span style="float:right;"><a href="https://wordpress.org/support/plugin/wp-staging/reviews/?filter=5" target="_blank" rel="external noopener">Rate it &#9733;&#9733;&#9733;</a></span>//-->

    </div>

    <?php
    $version = get_option( 'wpstg_version_latest' );

    $display = 'none;';

    if(defined('WPSTGPRO_VERSION')) {
        if (!empty($version) && version_compare(WPStaging\WPStaging::getVersion(), $version, '<')) {
            $display = 'block;';
        }
    }
    ?>

    <div id="wpstg-update-notify" style="display:<?php echo $display; ?>">
        <strong><?php echo sprintf( __( 'WP Staging Pro %s is available!', 'wp-staging' ), $version ); ?></strong> <br/>
        <?php echo sprintf( __( 'Important: Please update WP Staging Pro before pushing the staging site to production site. <a href="%s" target="_blank">What\'s New? ', 'wp-staging' ), 'https://wp-staging.com/wp-staging-pro-changelog' ); ?></a>
    </div>

</div>