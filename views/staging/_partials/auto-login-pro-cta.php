<?php

/**
 * The Pro auto-login preview, shown beneath the button that opens a staging site.
 * Greyed to say the action is not this plugin's, and still a link so the badge
 * has somewhere to lead. Include it only where there is an address to open.
 *
 * Set $autoLoginCtaClass before including to render the control in a screen's own
 * button system; it is left undeclared above so the default stays reachable.
 *
 * @see \WPStaging\Pro\Staging\Ajax\OneTimeLogin::ajaxGenerateStagingLoginUrl
 */

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Language\Language;

if (!WPStaging::isBasic()) {
    return;
}

$ctaClass = isset($autoLoginCtaClass) ? $autoLoginCtaClass : 'wpstg-btn wpstg-btn-md wpstg-btn-pro-locked';
?>
<div class="wpstg-mt-2.5" data-wpstg-auto-login-cta>
    <a
        class="<?php echo esc_attr($ctaClass); ?>"
        href="<?php echo esc_url(Language::getUpgradeUrl('staging_auto_login')); ?>"
        data-wpstg-upgrade-cta="staging_auto_login"
        target="_blank"
        rel="noopener noreferrer"
        title="<?php esc_attr_e('WP Staging Pro opens your staging site already signed in, so you never have to log in to it.', 'wp-staging'); ?>"
    >
        <svg class="wpstg-btn-icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
            <path d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"></path>
        </svg>
        <?php esc_html_e('Open and Log In Automatically', 'wp-staging'); ?>
        <span class="wpstg-badge-pro"><?php esc_html_e('Pro', 'wp-staging'); ?></span>
    </a>
</div>
