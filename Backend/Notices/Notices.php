<?php

namespace WPStaging\Backend\Notices;

/*
 *  Admin Notices | Warnings | Messages
 */

use WPStaging\Backend\Pro\Notices\Notices as ProNotices;
use WPStaging\Core\Utils\Cache;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\CloningProcess\ExcludedPlugins;
use WPStaging\Framework\Staging\CloneOptions;
use WPStaging\Framework\Staging\FirstRun;

/**
 * Class Notices
 * @package WPStaging\Backend\Notices
 *
 * @todo Have NoticeServiceProvider?
 */
class Notices
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var Assets
     */
    private $assets;

    /**
     * @var string The key that holds directory listing errors in the container.
     */
    public static $directoryListingErrors = 'directoryListingErrors';

    public function __construct($path, $assets)
    {
        $this->path = $path;
        $this->assets = $assets;
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

        // Never show disable mail message if free version
        $outgoingMailsDisabled = false;

        // Show all pro version notices
        if ($this->isPro()) {
            $proNotices = new ProNotices($this);
            // TODO: inject CloneOptions using DI
            // Check mails disabled against both the old and new way of emails disabled option
            $outgoingMailsDisabled = (bool)(new CloneOptions())->get(FirstRun::MAILS_DISABLED_KEY) || ((bool)get_option(FirstRun::MAILS_DISABLED_KEY, false));
            $proNotices->getNotices();
        }

        // Show notice about what disabled in the staging site. (Show only on staging site)
        if ((new DisabledItemsNotice())->isEnabled()) {
            // TODO: inject ExcludedPlugins using DI
            $excludedPlugins = (array)(new ExcludedPlugins())->getExcludedPlugins();
            // use require here instead of require_once otherwise unit tests will always fail,
            // as this notice is tested multiple times.
            require "{$viewsNoticesPath}disabled-items-notice.php";
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

        // WPSTAGING is not tested with current WordPress version
        if (!$this->isPro() && version_compare(WPStaging::getInstance()->get('WPSTG_COMPATIBLE'), get_bloginfo("version"), "<")) {
            require_once "{$viewsNoticesPath}wp-version-compatible-message.php";
        }

        // Different scheme in home and siteurl
        if ($this->isDifferentScheme()) {
            require_once "{$viewsNoticesPath}wrong-scheme.php";
        }

        // Outdated version of WP Staging Hooks
        if ($this->isUsingOutdatedWpstgHooksPlugin()) {
            require_once "{$viewsNoticesPath}outdated-wp-staging-hooks.php";
        }

        $this->showDirectoryListingWarningNotice($viewsNoticesPath);
    }

    /**
     * Displays the notice that we could not prevent
     * directory listing on a sensitive folder for some reason.
     *
     * @see \WPStaging\Framework\Filesystem\Filesystem::mkdir The place where all errors are enqueued
     *                                                        to be displayed as a single notice here.
     *
     * Note: When refactoring this, keep in mind this code should be
     * called only once, otherwise the message would be enqueued multiple times.
     *
     * @param string $viewsNoticesPath The path to the views folder.
     */
    private function showDirectoryListingWarningNotice($viewsNoticesPath)
    {
        $directoryListingErrors = WPStaging::getInstance()->getContainer()->getFromArray(static::$directoryListingErrors);

        // Early bail: No errors to show
        if (empty($directoryListingErrors)) {
            return;
        }

        // Early bail: These warnings were disabled by the user.
        if ((bool)apply_filters('wpstg.notices.hideDirectoryListingWarnings', false)) {
            return;
        }

        require_once "{$viewsNoticesPath}directory-listing-could-not-be-prevented.php";
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

    /**
     * Check if the user is using an outdated version of WP Staging Hooks plugin
     * @return boolean
     */
    private function isUsingOutdatedWpstgHooksPlugin()
    {
        // Minimum version to check
        $versionToCheck = '0.0.2';

        // Path to WP Staging Hooks plugins in a directory
        $wpstgHooksPath = 'wp-staging-hooks/wp-staging-hooks.php';

        // Plugin doesn't exist, so no need to show notice
        if (file_exists(WP_PLUGIN_DIR . '/' . $wpstgHooksPath)) {
            $wpstgHooksData = get_plugin_data(WP_PLUGIN_DIR . '/' . $wpstgHooksPath);
            // Only show notice if current version is below required min version.
            return version_compare($wpstgHooksData['Version'], $versionToCheck, '>=') ? false : true;
        }

        // Path to WP Staging Hooks plugins directly in plugins dir
        $wpstgHooksPath = 'wp-staging-hooks.php';

        // Plugin doesn't exist, so no need to show notice
        if (file_exists(WP_PLUGIN_DIR . '/' . $wpstgHooksPath)) {
            $wpstgHooksData = get_plugin_data(WP_PLUGIN_DIR . '/' . $wpstgHooksPath);
            // Only show notice if current version is below required min version.
            return version_compare($wpstgHooksData['Version'], $versionToCheck, '>=') ? false : true;
        }

        return false;
    }

    /**
     * Get the path of plugin
     * @return string
     */
    public function getPluginPath()
    {
        return $this->path;
    }

    /**
     * Get the assets helper
     * @return Assets
     */
    public function getAssets()
    {
        return $this->assets;
    }
}
