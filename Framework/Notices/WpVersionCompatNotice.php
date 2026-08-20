<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\Security\Auth;








class WpVersionCompatNotice
{





    const OPTION_KEY = 'wpstg_compat_notice_dismissed';




    private $auth;




    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }






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








    public function ajaxDismissCompatNotice()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        update_option(self::OPTION_KEY, WPStaging::getVersion(), false);
        wp_send_json_success();
    }








    private function shouldShowNotice($wpVersion, $pluginVersion)
    {
        $compatible = WPStaging::getInstance()->get('WPSTG_COMPATIBLE');

        if (version_compare($compatible, $wpVersion, '>=')) {
            return false;
        }

        $dismissedForVersion = get_option(self::OPTION_KEY, '');

        return $dismissedForVersion !== $pluginVersion;
    }







    private function getWpMajorMinor($version)
    {
        if (preg_match('/^(\d+\.\d+)/', $version, $m)) {
            return $m[1];
        }

        return $version;
    }
}
