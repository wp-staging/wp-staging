<?php

namespace WPStaging\Backend\Notices;

/*
 *  Admin Notices | Warnings | Messages
 */

use Exception;
use DateTime;
use wpdb;
use WPStaging\Backend\Pro\Notices\Notices as ProNotices;
use WPStaging\Core\Utils\Cache;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\CloningProcess\ExcludedPlugins;
use WPStaging\Framework\Staging\CloneOptions;
use WPStaging\Framework\Staging\FirstRun;
use WPStaging\Framework\Support\ThirdParty\FreemiusScript;
use WPStaging\Framework\Support\ThirdParty\WordFence;

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

    //** For testing all notices  */
    const SHOW_ALL_NOTICES = false;

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
            "wpstg-settings", "wpstg-addons", "wpstg-tools", "wpstg-clone", "wpstg_clone", "wpstg_backup"
        ];

        return !(!is_admin() || !in_array($currentPage, $availablePages, true));
    }

    /**
     * check whether the plugin is pro version
     *
     * @return  boolean
     * @todo    Implement this in a separate class related to plugin. Then replace it with dependency injection. Add filters
     *
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

        $now = new DateTime("now");

        // Check if user clicked on "rate later" button and if there is a valid 'later' date
        if (wpstg_is_valid_date($dbOption)) {
            // Do not show before this date
            $show = new DateTime($dbOption);
            if ($now < $show) {
                return false;
            }
        }


        // Show X days after installation
        $installDate = new DateTime(get_option("wpstg_installDate"));

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

        throw new Exception('Function get_current_screen does not exist. WP < 3.0.1.');
    }


    /**
     * Load admin notices
     * @throws Exception
     */
    public function messages()
    {
        $viewsNoticesPath = "{$this->path}views/notices/";

        // Show notice "rate the plugin". Free version only
        if (self::SHOW_ALL_NOTICES || (!$this->isPro() && ($this->canShow("wpstg_rating", 7) && $this->getCurrentScreen() !== 'page' && $this->getCurrentScreen() !== 'post'))) {
            require_once "{$viewsNoticesPath}rating.php";
        }

        // Never show disable mail message if it's free version
        $outgoingMailsDisabled = false;

        // Show all notices of the pro version
        if ($this->isPro()) {
            $proNotices = new ProNotices($this);
            // Check mails disabled against both the old and new way of emails disabled option
            $outgoingMailsDisabled = (bool)(new CloneOptions())->get(FirstRun::MAILS_DISABLED_KEY) || ((bool)get_option(FirstRun::MAILS_DISABLED_KEY, false));
            $proNotices->getNotices();
        }

        // Show notice about what disabled in the staging site. (Show only on staging site)
        if (self::SHOW_ALL_NOTICES || ((new DisabledItemsNotice())->isEnabled())) {
            $excludedPlugins = (array)(new ExcludedPlugins())->getExcludedPlugins();
            // Show freemius notice if freemius options were deleted during cloning.
            $freemiusOptionsCleared = (new FreemiusScript())->isNoticeEnabled();
            // use require here instead of require_once otherwise unit tests will always fail,
            // as this notice is tested multiple times.
            require "{$viewsNoticesPath}disabled-items-notice.php";
        }

        $db = WPStaging::getInstance()->get('wpdb');
        $optionTable = $db->prefix . 'options';
        if (self::SHOW_ALL_NOTICES || (current_user_can("manage_options") && $this->isOptionTablePrimaryKeyMissing($db, $optionTable))) {
            require "{$viewsNoticesPath}wp-options-missing-pk.php";
        }

        // Show notice if WordFence Firewall is disabled
        /** @var WordFence */
        WPStaging::make(WordFence::class)->showNotice($viewsNoticesPath);

        /**
         * Display all notices below this line in WP STAGING admin pages only and only to administrators!
         */
        if (!current_user_can("update_plugins") || !$this->isAdminPage()) {
            return;
        }

        // Show notice if uploads dir is outside ABSPATH
        /** @var Directory */
        $dirUtils = WPStaging::make(Directory::class);
        if (self::SHOW_ALL_NOTICES || !$dirUtils->isPathInWpRoot($dirUtils->getUploadsDirectory())) {
            require "{$viewsNoticesPath}uploads-outside-wp-root.php";
        }

        /**
         * Display outdated WP Staging version notice (Free Only)
         */
        /** @var OutdatedWpStagingNotice */
        WPStaging::make(OutdatedWpStagingNotice::class)
            ->showNotice($viewsNoticesPath);

        // Show notice Cache directory is not writable
        /** @var Cache $cache */
        $cache = WPStaging::getInstance()->get("cache");
        $cacheDir = $cache->getCacheDir();
        if (self::SHOW_ALL_NOTICES || (!is_dir($cacheDir) || !is_writable($cacheDir))) {
            require_once "{$viewsNoticesPath}/cache-directory-permission-problem.php";
        }

        // Show notice Logger directory is not writable
        /** @var Logger $logger */
        $logger = WPStaging::getInstance()->get("logger");
        $logsDir = $logger->getLogDir();
        if (self::SHOW_ALL_NOTICES || (!is_dir($logsDir) || !is_writable($logsDir))) {
            require_once "{$viewsNoticesPath}/logs-directory-permission-problem.php";
        }

        // Show notice Staging directory is not writable
        if (self::SHOW_ALL_NOTICES || (!is_writable(ABSPATH))) {
            require_once "{$viewsNoticesPath}/staging-directory-permission-problem.php";
        }

        // Show notice WP STAGING is not tested with current WordPress version
        if (self::SHOW_ALL_NOTICES || (!$this->isPro() && version_compare(WPStaging::getInstance()->get('WPSTG_COMPATIBLE'), get_bloginfo("version"), "<"))) {
            require_once "{$viewsNoticesPath}wp-version-compatible-message.php";
        }

        // Show notice Different scheme in home and siteurl
        if (self::SHOW_ALL_NOTICES || ($this->isDifferentScheme())) {
            require_once "{$viewsNoticesPath}wrong-scheme.php";
        }

        // Show notice Outdated version of WP Staging Hooks
        if (self::SHOW_ALL_NOTICES || ($this->isUsingOutdatedWpstgHooksPlugin())) {
            require_once "{$viewsNoticesPath}outdated-wp-staging-hooks.php";
        }

        // Show notice Failed to prevent directory listing
        $this->showDirectoryListingWarningNotice($viewsNoticesPath);
    }

    /**
     * Check whether the wp_options table is missing primary key | auto increment
     * @param wpdb $db
     * @param string $optionTable
     *
     * @return boolean
     */
    private function isOptionTablePrimaryKeyMissing($db, $optionTable)
    {
        $result = $db->dbh->query("SELECT option_id FROM {$optionTable} LIMIT 1");
        $fInfo = $result->fetch_field();
        $result->free_result();

        // Check whether the flag have primary key and auto increment flag
        if (($fInfo->flags & MYSQLI_PRI_KEY_FLAG) && ($fInfo->flags & MYSQLI_AUTO_INCREMENT_FLAG)) {
            return false;
        }

        return true;
    }

    /**
     * Displays the notice that we could not prevent
     * directory listing on a sensitive folder for some reason.
     *
     * @param string $viewsNoticesPath The path to the views folder.
     * @see \WPStaging\Framework\Filesystem\Filesystem::mkdir The place where all errors are enqueued
     *                                                        to be displayed as a single notice here.
     *
     * Note: When refactoring this, keep in mind this code should be
     * called only once, otherwise the message would be enqueued multiple times.
     *
     */
    private function showDirectoryListingWarningNotice($viewsNoticesPath)
    {

            $directoryListingErrors = WPStaging::getInstance()->getContainer()->getFromArray(static::$directoryListingErrors);

            // Early bail: No errors to show
        if (!self::SHOW_ALL_NOTICES && empty($directoryListingErrors)) {
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
        $homeScheme = parse_url(get_option('home'), PHP_URL_SCHEME);

        return !($siteurlScheme === $homeScheme);
    }

    /**
     * Check if the user is using an outdated version of WP Staging Hooks plugin
     * @return boolean
     */
    private function isUsingOutdatedWpstgHooksPlugin()
    {
        // Minimum version to check
        $versionToCheck = '0.0.4';

        // Path to WP Staging Hooks plugins in a directory
        $wpstgHooksPath = 'wp-staging-hooks/wp-staging-hooks.php';

        // Only show notice if plugin exists for above path
        if (file_exists(WP_PLUGIN_DIR . '/' . $wpstgHooksPath)) {
            $wpstgHooksData = get_plugin_data(WP_PLUGIN_DIR . '/' . $wpstgHooksPath);
            // Only show notice if current version is below required min version.
            return version_compare($wpstgHooksData['Version'], $versionToCheck, '>=') ? false : true;
        }

        // Path to WP Staging Hooks plugins directly in plugins dir
        $wpstgHooksPath = 'wp-staging-hooks.php';

        // Only show notice if plugin exists for above path
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

    /**
     * Render the notice dismiss action
     *
     * @param string $viewsNoticesPath
     * @param string $wpstgNotice
     * @param string $cssClassSelectorDismiss
     * @param string $cssClassSelectorNotice
     */
    public static function renderNoticeDismissAction($viewsNoticesPath, $wpstgNotice, $cssClassSelectorDismiss, $cssClassSelectorNotice)
    {
        require "{$viewsNoticesPath}_partial/notice_dismiss_action.php";
    }
}
