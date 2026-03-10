<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\Security\Auth;

/**
 * Displays a dismissible compatibility status banner when WP STAGING has not yet
 * been validated for the currently installed WordPress version.
 *
 * Dismissal is stored in wp_options. The stored value is the plugin version at the
 * time of dismissal, so the notice automatically reappears after a plugin update.
 */
class WpVersionCompatNotice
{
    /**
     * Option key for storing the dismissed plugin version.
     *
     * @var string
     */
    const OPTION_KEY = 'wpstg_compat_notice_dismissed';

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @param Auth $auth
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Conditionally render the compatibility notice inside the plugin UI.
     *
     * @return void
     */
    public function maybeShow()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $wpVersion     = get_bloginfo('version');
        $pluginVersion = WPStaging::getVersion();

        if (!$this->shouldShowNotice($wpVersion, $pluginVersion)) {
            return;
        }

        $wpMajorMinor  = $this->getWpMajorMinor($wpVersion);

        $changelogUrl  = WPStaging::isPro()
            ? 'https://wp-staging.com/wp-staging-pro-changelog/'
            : 'https://wp-staging.com/changelog/';
        $supportUrl    = Language::localizeSupportUrl('https://wp-staging.com/support/');
        $systemInfoUrl = admin_url('admin.php?page=wpstg-tools');

        $notice = WPSTG_VIEWS_DIR . 'notices/wp-version-compat-notice.php';
        if (!file_exists($notice)) {
            return;
        }

        include $notice;
    }

    /**
     * AJAX handler to persist dismissal.
     *
     * Stores the current plugin version so the notice reappears after a plugin update.
     *
     * @return void
     */
    public function ajaxDismissCompatNotice()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        update_option(self::OPTION_KEY, WPStaging::getVersion(), false);
        wp_send_json_success();
    }

    /**
     * Determine whether the notice should be shown.
     *
     * @param string $wpVersion
     * @param string $pluginVersion
     * @return bool
     */
    private function shouldShowNotice($wpVersion, $pluginVersion)
    {
        $compatible = WPStaging::getInstance()->get('WPSTG_COMPATIBLE');

        if (version_compare($compatible, $wpVersion, '>=')) {
            return false;
        }

        $dismissedForVersion = get_option(self::OPTION_KEY, '');

        return $dismissedForVersion !== $pluginVersion;
    }

    /**
     * Extract the major.minor portion from a WordPress version string.
     *
     * @param string $version e.g. "6.6.1" or "7.0-alpha-61697"
     * @return string e.g. "6.6" or "7.0"
     */
    private function getWpMajorMinor($version)
    {
        if (preg_match('/^(\d+\.\d+)/', $version, $m)) {
            return $m[1];
        }

        return $version;
    }
}
