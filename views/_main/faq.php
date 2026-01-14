<?php

/**
 * FAQ/Knowledgebase view template
 *
 * Renders the FAQ section in the WP Staging admin sidebar.
 * Supports collapsible state with localStorage persistence.
 */

use WPStaging\Core\WPStaging;

?>
<div id="wpstg-faq" class="wpstg-faq-container wpstg-block">
    <div class="wpstg-faq-header" role="button" aria-expanded="false">
        <h3 class="wpstg-faq-title">📚 <?php esc_html_e('Knowledgebase', 'wp-staging'); ?></h3>
        <span class="wpstg-faq-toggle-link">
            <span class="wpstg-faq-toggle-text"><?php esc_html_e('View Details', 'wp-staging'); ?></span>
            <span class="wpstg-faq-toggle-icon">›</span>
        </span>
    </div>

    <div class="wpstg-faq-content" style="display: none;">
        <ul class="wpstg-faq-list">
            <li class="wpstg-faq-item"><a href="https://wp-staging.com/docs/how-to-migrate-your-wordpress-site-to-a-new-host/" target="_blank" rel="external"><?php esc_html_e('Migrate Website to Another Server or Domain', 'wp-staging'); ?></a></li>
            <li class="wpstg-faq-item"><a href="https://wp-staging.com/docs/staging-site-redirects-live-site/" target="_blank" rel="external"><?php esc_html_e('Can not login to staging site', 'wp-staging'); ?></a></li>
            <li class="wpstg-faq-item"><a href="https://wp-staging.com/docs/staging-site-redirects-live-site/" target="_blank" rel="external"><?php esc_html_e('Staging site redirects to production site', 'wp-staging'); ?></a></li>
            <li class="wpstg-faq-item"><a href="https://wp-staging.com/docs/fix-white-or-blank-page-after-pushing-fatal-error-500/" target="_blank" rel="external"><?php esc_html_e('Staging site returns blank white page', 'wp-staging'); ?></a></li>
            <li class="wpstg-faq-item"><a href="https://wp-staging.com/docs/set-up-a-staging-site-on-an-external-server-with-wp-staging/" target="_blank" rel="external"><?php esc_html_e('Set Up a Staging Site on an External Server', 'wp-staging'); ?></a></li>
            <li class="wpstg-faq-item"><a href="https://wp-staging.com/docs/css-layout-broken-after-push/" target="_blank" rel="external"><?php esc_html_e('CSS & layout looks different after push', 'wp-staging'); ?></a></li>
            <?php if (is_plugin_active('woocommerce/woocommerce.php')) : ?>
                <li class="wpstg-faq-item"><a href="https://wp-staging.com/docs/skip-woocommerce-orders-and-products/" target="_blank" rel="external"><?php esc_html_e('Skip WooCommerce Orders and Products', 'wp-staging'); ?></a></li>
                <li class="wpstg-faq-item"><a href="https://wp-staging.com/docs/delete-all-woocommerce-orders-and-transactions/" target="_blank" rel="external"><?php esc_html_e('Delete All WooCommerce Orders on Staging Site', 'wp-staging'); ?></a></li>
                <li class="wpstg-faq-item"><a href="https://wp-staging.com/docs/how-to-disable-woocommerce-subscriptions-on-a-staging-site/" target="_blank" rel="external"><?php esc_html_e('Disable WooCommerce Subscriptions on Staging', 'wp-staging'); ?></a></li>
            <?php endif; ?>
            <li class="wpstg-faq-item"><a href="https://wp-staging.com/docs/can-not-update-wp-staging-staging-site/" target="_blank" rel="external"><?php esc_html_e('Can not update WP STAGING plugin', 'wp-staging'); ?></a></li>
            <li class="wpstg-faq-item"><a href="https://wp-staging.com/docs/page-not-found-error-404-after-pushing/" target="_blank" rel="external"><?php esc_html_e('Page not found – Error 404 after Pushing', 'wp-staging'); ?></a></li>
            <li class="wpstg-faq-item"><a href="https://wp-staging.com/docs/pagebuilders-do-not-work/" target="_blank" rel="external"><?php esc_html_e('Pagebuilder plugin does not open (Divi, Elementor)', 'wp-staging'); ?></a></li>
        </ul>
    </div>

    <div class="wpstg-faq-footer">
        <div class="wpstg-faq-contact">
            <?php esc_html_e('Still questions?', 'wp-staging'); ?>
            <a href="https://wp-staging.com/support" target="_blank" rel="external nofollow"><?php esc_html_e('Contact us', 'wp-staging'); ?></a>
        </div>
        <div class="wpstg-faq-links">
            <a href="https://wp-staging.com/docs/documentation/" target="_blank" class="wpstg-faq-all-articles">
                <?php esc_html_e('View documentation', 'wp-staging'); ?> &rarr;
            </a>
            <div class="wpstg-faq-social">
                <a href="https://x.com/intent/follow?screen_name=wpstg" target="_blank" title="<?php esc_attr_e('Follow us on X', 'wp-staging'); ?>">
                    <img src="<?php echo esc_url($this->assets->getAssetsUrl('svg/twitter-x.svg')); ?>" alt="X" width="14" height="14">
                </a>
                <a href="https://github.com/wp-staging/wp-staging" target="_blank" title="<?php esc_attr_e('Follow us on GitHub', 'wp-staging'); ?>">
                    <img src="<?php echo esc_url($this->assets->getAssetsUrl('svg/github-logo.svg')); ?>" alt="GitHub" width="14" height="14">
                </a>
            </div>
        </div>
    </div>
</div>
