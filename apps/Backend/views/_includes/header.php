<span class="wp-staginglogo">
    <img src="<?php echo $this->url . "img/logo_clean_small_212_25.png"?>">
</span>

<span class="wpstg-version">
    <?php if (WPStaging\WPStaging::getSlug() === "wp-staging-pro") echo "Pro" ?> Version <?php echo \WPStaging\WPStaging::VERSION ?>
</span>

<div class="wpstg-header">
    <div class='wpstg-share-button-container'>
        <div class='wpstg-share-button wpstg-share-button-twitter' data-share-url="https://wordpress.org/plugins/wp-staging">
            <div clas='box'>
                <a href="https://twitter.com/intent/tweet?button_hashtag=wpstaging&text=Check%20out%20this%20plugin%20for%20creating%20a%20one%20click%20WordPress%20testing%20site&via=wpstg" target='_blank'>
                    <span class='wpstg-share'><?php echo __('Tweet #wpstaging','wpstg'); ?></span>
                </a>
            </div>
        </div>
        <div class="wpstg-share-button wpstg-share-button-twitter">
            <div class="box">
                <a href="https://twitter.com/intent/follow?original_referer=http%3A%2F%2Fsrc.wordpress-develop.dev%2Fwp-admin%2Fadmin.php%3Fpage%3Dwpstg-settings&ref_src=twsrc%5Etfw&region=follow_link&screen_name=renehermenau&tw_p=followbutton" target="_blank">
                    <span class='wpstg-share'><?php echo __('Follow @wpstaging','wpstg'); ?></span>
                </a>
            </div>
        </div>
        <div class="wpstg-share-button wpstg-share-button-facebook" data-share-url="https://wordpress.org/plugins/wp-staging">
            <div class="box">
                <a href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fwordpress.org%2Fplugins%2Fwp-staging" target="_blank">
                    <span class='wpstg-share'><?php echo __('Share on Facebook','wpstg'); ?></span>
                </a>
            </div>
        </div>
    </div>
    <div style="font-size:14px;">
        Tutorial: <a href="https://wp-staging.com/docs/copy-staging-site-to-live-site/" target="_blank">Learn how to push changes to live website</a>
        <span style="float:right;"><a href="https://wordpress.org/support/plugin/wp-staging/reviews/?filter=5" target="_blank" rel="external noopener">Rate the plugin</a></span>
    </div>
</div>