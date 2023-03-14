<div id="wpstg-error-wrapper">
    <div id="wpstg-error-details"></div>
</div>
<div id='wpstg-footer' class="" style="">
    <strong class="wpstg-footer--title"><?php esc_html_e("Knowledgebase", "wp-staging") ?></strong>
    <ul>
        <li><a href="https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/" target="_blank" rel="external"><?php esc_html_e("Migrate Website to Another Server or Domain", "wp-staging") ?></a></li>
        <li><a href="https://wp-staging.com/docs/staging-site-redirects-live-site/" target="_blank" rel="external"><?php esc_html_e("Can not login to staging site", "wp-staging") ?></a></li>
        <li><a href="https://wp-staging.com/docs/staging-site-redirects-live-site/" target="_blank" rel="external"><?php esc_html_e("Staging site redirects to production site", "wp-staging") ?></a></li>
        <li><a href="https://wp-staging.com/docs/fix-white-or-blank-page-after-pushing-fatal-error-500/" target="_blank" rel="external"><?php esc_html_e("Staging site returns blank white page", "wp-staging") ?></a></li>
        <li><a href="https://wp-staging.com/docs/css-layout-broken-after-push/" target="_blank" rel="external"><?php esc_html_e("CSS & layout broken after push", "wp-staging") ?></a></li>
        <li><a href="https://wp-staging.com/docs/skip-woocommerce-orders-and-products/" target="_blank" rel="external"><?php esc_html_e("Skip WooCommerce Orders and Products", "wp-staging") ?></a></li>
        <li><a href="https://wp-staging.com/docs/can-not-update-wp-staging-staging-site/" target="_blank" rel="external"><?php esc_html_e("Can not update WP STAGING plugin", "wp-staging") ?></a></li>
        <li><a href="https://wp-staging.com/docs/page-not-found-error-404-after-pushing/" target="_blank" rel="external"><?php esc_html_e("Page not found – Error 404 after Pushing", "wp-staging") ?></a></li>
        <li><a href="https://wp-staging.com/docs/pagebuilders-do-not-work/" target="_blank" rel="external"><?php esc_html_e("Pagebuilders like DIVI or Elementor do not work", "wp-staging") ?></a></li>
        <li><a href="https://wp-staging.com/docs/troubleshooting-try-this-first/" target="_blank" rel="external"><?php esc_html_e("All articles", "wp-staging") ?></a></li>
    </ul>
    <div id="footer-link-support-ticket ">
        <?php esc_html_e('Still questions?', 'wp-staging'); ?>
        <?php echo wp_kses_post(sprintf(__('Please <a href="%s" target="_blank" rel="external nofollow" class="wpstg--blue">contact us.</a>', 'wp-staging'), 'https://wp-staging.com/support')); ?>
    </div>

    <div class="wpstg-social-footer">
        <div class="wpstg-social-row">
            <div class="wpstg-social-col">
                Find us on:
            </div>
            <div class="wpstg-social-col">
                <div class="wpstg-share-button">
                    <a href="https://twitter.com/intent/follow?ref_src=twsrc%5Etfw&region=follow_link&screen_name=wpstg&tw_p=followbutton" target="_blank">
                        <img src="<?php echo esc_url($this->assets->getAssetsUrl("img/twitter-logo.svg")) ?>" id="twitter-logo-svg" style="width:23px;" alt="WP STAGING on Twitter" title="Follow us on Twitter">
                    </a>
                </div>
            </div>
            <div class="wpstg-social-col">
                <a href="https://github.com/wp-staging/wp-staging" target="_blank" class="wpstg-share-button">
                    <img src="<?php echo esc_url($this->assets->getAssetsUrl("img/github-logo.svg")) ?>" id="github-logo-svg" style="width:23px;" alt="WP STAGING on GitHub" title="Follow us on GitHub">
                </a>
            </div>
        </div>
    </div>
</div>
<div class="wpstg-footer-logo" style="">
    <a href="https://wp-staging.com/tell-me-more/"><img src="<?php echo esc_url($this->assets->getAssetsUrl("img/logo.svg")) ?>" width="140"></a>
</div>

