<?php

namespace WPStaging\Backend\Notices;

/*
 *  Admin Notices | Warnings | Messages
 */

// No Direct Access
if (!defined("WPINC")) {
    die;
}

use WPStaging\Backend\Pro\Notices\Notices as ProNotices;
use WPStaging\Core\Utils\Cache;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;

/**
 * Class Notices
 * @package WPStaging\Backend\Notices
 */
class Notices
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $url;

    public function __construct($path, $url)
    {
        $this->path = $path;
        $this->url = $url;
    }

    /**
     * Check whether the page is admin page or not
     * @return bool
     */
    public function isAdminPage()
    {
        $currentPage = (isset($_GET["page"])) ? $_GET["page"] : null;

        $availablePages = [
            "wpstg-settings", "wpstg-addons", "wpstg-tools", "wpstg-clone", "wpstg_clone"
        ];

        return !(!is_admin() || !in_array($currentPage, $availablePages, true));
    }

    /**
     * check whether the plugin is pro version
     *
     * @todo    Implement this in a separate class related to plugin. Then replace it with dependency injection. Add filters
     *
     * @return  boolean
     */
    protected function isPro()
    {
        return defined('WPSTGPRO_VERSION');
    }

    /**
     * Check if notice should be shown after certain days of installation
     * @param int $days default 10
     * @return bool
     */
    private function canShow($option, $days = 10)
    {
        // Do not show notice
        if (empty($option)) {
            return false;
        }

        $dbOption = get_option($option);

        // Do not show notice
        if ($dbOption === "no") {
            return false;
        }

        $now = new \DateTime("now");

        // Check if user clicked on "rate later" button and if there is a valid 'later' date
        if (wpstg_is_valid_date($dbOption)) {
            // Do not show before this date
            $show = new \DateTime($dbOption);
            if ($now < $show) {
                return false;
            }
        }


        // Show X days after installation
        $installDate = new \DateTime(get_option("wpstg_installDate"));

        // get number of days between installation date and today
        $difference = $now->diff($installDate)->days;

        return $days <= $difference;
    }
    /**
     * Get current page
     * @return string post, page
     */
    private function getCurrentScreen()
    {
        if (function_exists('get_current_screen')) {
            return \get_current_screen()->post_type;
        }

        throw new \Exception('Function get_current_screen does not exist. WP < 3.0.1.');
    }


    /**
     * Load admin notices
     * @throws \Exception
     */
    public function messages()
    {
        $viewsNoticesPath = "{$this->path}views/notices/";

        // Show "rate the plugin". Free version only
        if (!$this->isPro()) {
            if ($this->canShow("wpstg_rating", 7) && $this->getCurrentScreen() !== 'page' && $this->getCurrentScreen() !== 'post') {
                require_once "{$viewsNoticesPath}rating.php";
            }
        }

        // Show all pro version notices
        if ($this->isPro()) {
            $proNotices = new ProNotices($this);
            $proNotices->getNotices();
        }

        // Show notice about cache being disabled in the staging site. (Show only on staging site)
        if ((new DisabledCacheNotice())->isEnabled()) {
            require_once "{$viewsNoticesPath}disabled-cache.php";
        }

        // Display notices below in wp staging admin pages only
        if (!current_user_can("update_plugins") || !$this->isAdminPage()) {
            return;
        }

        // Cache directory is not writable
        /** @var Cache $cache */
        $cache = WPStaging::getInstance()->get("cache");
        $cacheDir = $cache->getCacheDir();
        if (!is_dir($cacheDir) || !is_writable($cacheDir)) {
            require_once "{$viewsNoticesPath}/cache-directory-permission-problem.php";
        }

        // Logger directory is not writable
        /** @var Logger $logger */
        $logger = WPStaging::getInstance()->get("logger");
        $logsDir = $logger->getLogDir();
        if (!is_dir($logsDir) || !is_writable($logsDir)) {
            require_once "{$viewsNoticesPath}/logs-directory-permission-problem.php";
        }

        // Staging directory is not writable
        if (!is_writable(ABSPATH)) {
            require_once "{$viewsNoticesPath}/staging-directory-permission-problem.php";
        }

        // Version Control for Free
        if(!$this->isPro() && version_compare(WPStaging::getInstance()->get('WPSTG_COMPATIBLE'), get_bloginfo("version"), "<")) {
            require_once "{$viewsNoticesPath}wp-version-compatible-message.php";
        }

        // Different scheme in home and siteurl
        if ($this->isDifferentScheme()) {
            require_once "{$viewsNoticesPath}wrong-scheme.php";
        }
    }

    /**
     * Check if the url scheme of siteurl and home is identical
     * @return boolean
     */
    private function isDifferentScheme() 
    {
        $siteurlScheme = parse_url(get_option('siteurl'), PHP_URL_SCHEME);
        $homeScheme    = parse_url(get_option('home'), PHP_URL_SCHEME);

        return !($siteurlScheme === $homeScheme);
    }

}