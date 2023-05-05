<?php

namespace WPStaging\Framework\Notices;

use Exception;
use DateTime;
use wpdb;
use WPStaging\Core\Utils\Cache;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\CloningProcess\ExcludedPlugins;
use WPStaging\Framework\Staging\CloneOptions;
use WPStaging\Framework\Staging\FirstRun;
use WPStaging\Framework\Support\ThirdParty\FreemiusScript;
use WPStaging\Framework\Support\ThirdParty\Jetpack;
use WPStaging\Framework\Support\ThirdParty\WordFence;
use WPStaging\Framework\Traits\NoticesTrait;

/**
 * Show Admin Notices | Warnings | Messages
 *
 * Class Notices
 * @package WPStaging\Framework\Notices
 * @todo maybe split this class into multiple classes like staging notices, permission notices etc
 * to avoid dependency hell without using service locator?
 */
class Notices
{
    use NoticesTrait;

    const PRO_NOTICES_ACTION = 'wpstg.notices.show_pro_notices';

    /** @var Assets */
    private $assets;

    /** @var Directory */
    private $dirUtil;

    /** @var Cache */
    private $cache;

    /** @var Logger */
    private $logger;

    /** @var CloneOptions */
    private $cloneOptions;

    /** @var ExcludedPlugins */
    private $excludedPlugins;

    /** @var FreemiusScript */
    private $freemiusScript;

    /** @var WordFence */
    private $wordfence;

    /** @var DisabledItemsNotice */
    private $disabledItemsNotice;

    /** @var WarningsNotice */
    private $warningsNotice;

    /** @var OutdatedWpStagingNotice */
    private $outdatedWpStagingNotice;

    /** @var wpdb */
    private $db;

    //** For testing all notices  */
    const SHOW_ALL_NOTICES = false;

    /**
     * @var string The key that holds directory listing errors in the container.
     */
    public static $directoryListingErrors = 'directoryListingErrors';

    public function __construct(Assets $assets)
    {
        $this->assets = $assets;

        // To avoid dependency hell and smooth transition we will be using service locator for below dependencies
        $this->dirUtil = WPStaging::make(Directory::class);
        $this->wordfence = WPStaging::make(WordFence::class);
        $this->cloneOptions = WPStaging::make(CloneOptions::class);
        $this->freemiusScript = WPStaging::make(FreemiusScript::class);
        $this->excludedPlugins = WPStaging::make(ExcludedPlugins::class);
        $this->logger = WPStaging::getInstance()->get("logger");
        $this->cache = WPStaging::getInstance()->get("cache");
        $this->db = WPStaging::getInstance()->get('wpdb');

        // Notices
        $this->disabledItemsNotice = WPStaging::make(DisabledItemsNotice::class);
        $this->warningsNotice = WPStaging::make(WarningsNotice::class);
        $this->outdatedWpStagingNotice = WPStaging::make(OutdatedWpStagingNotice::class);
    }

    /**
     * Check whether the plugin is pro version
     *
     * @return  bool
     */
    protected function isPro()
    {
        return WPStaging::isPro();
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
     * Get current page.
     * Note: This can not be moved to wpAdapter class as it is only available very late
     * at add admin_init and not available most of the time.
     *
     * @return string post, page
     */
    private function getCurrentPage()
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
    public function renderNotices()
    {
        $viewsNoticesPath = "{$this->getPluginPath()}/Backend/views/notices/";

        // Show notice "rate the plugin". Free version only
        if (self::SHOW_ALL_NOTICES || (!$this->isPro() && ($this->canShow("wpstg_rating", 7) && $this->getCurrentPage() !== 'page' && $this->getCurrentPage() !== 'post'))) {
            require_once "{$viewsNoticesPath}rating.php";
        }

        // Never show disable mail message if it's free version
        $outgoingMailsDisabled = false;

        // Show all notices of the pro version
        if ($this->isPro()) {
            // Check mails disabled against both the old and new way of emails disabled option
            $outgoingMailsDisabled = (bool)$this->cloneOptions->get(FirstRun::MAILS_DISABLED_KEY) || ((bool)get_option(FirstRun::MAILS_DISABLED_KEY, false));
            // This hook is for internal use only. Used in PRO version to display PRO version related notices.
            do_action(self::PRO_NOTICES_ACTION);
        }

        // Show notice about what disabled in the staging site. (Show only on staging site)
        if (self::SHOW_ALL_NOTICES || $this->disabledItemsNotice->isEnabled()) {
            $excludedPlugins = (array)$this->excludedPlugins->getExcludedPlugins();
            // Show freemius notice if freemius options were deleted during cloning.
            $freemiusOptionsCleared = $this->freemiusScript->isNoticeEnabled();
            // Show jetpack staging mode notice if the constant is set on staging site
            $isJetpackStagingModeActive = defined(Jetpack::STAGING_MODE_CONST) && constant(Jetpack::STAGING_MODE_CONST) === true;
            // use require here instead of require_once otherwise unit tests will always fail,
            // as this notice is tested multiple times.
            require "{$viewsNoticesPath}disabled-items-notice.php";
        }

        $optionTable = $this->db->prefix . 'options';
        if (self::SHOW_ALL_NOTICES || (current_user_can("manage_options") && $this->isOptionTablePrimaryKeyMissing($this->db, $optionTable))) {
            require "{$viewsNoticesPath}wp-options-missing-pk.php";
        }

        // Show notice if WordFence Firewall is disabled
        $this->wordfence->showNotice($viewsNoticesPath);

        $settings = get_option('wpstg_settings', []);
        if (self::SHOW_ALL_NOTICES || (!is_array($settings) && !is_object($settings))) {
            require "{$viewsNoticesPath}settings_option_corrupt.php";
        }

        /**
         * Display all notices below this line in WP STAGING admin pages only and only to administrators!
         */
        if (!current_user_can("update_plugins") || !$this->isWPStagingAdminPage()) {
            return;
        }

        // Show notice if uploads dir is outside ABSPATH
        if (self::SHOW_ALL_NOTICES || !$this->dirUtil->isPathInWpRoot($this->dirUtil->getUploadsDirectory())) {
            require "{$viewsNoticesPath}uploads-outside-wp-root.php";
        }

        /**
         * Display outdated WP Staging version notice (Free Only)
         */
        $this->outdatedWpStagingNotice->showNotice($viewsNoticesPath);

        // Show notice Cache directory is not writable
        $cacheDir = $this->cache->getCacheDir();
        if (self::SHOW_ALL_NOTICES || (!is_dir($cacheDir) || !is_writable($cacheDir))) {
            require_once "{$viewsNoticesPath}/cache-directory-permission-problem.php";
        }

        // Show notice Logger directory is not writable
        $logsDir = $this->logger->getLogDir();
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

        // Show notice mu-plugin directory is not executable
        $varsDirectory = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : trailingslashit(WP_CONTENT_DIR) . 'mu-plugins';
        $wpstgSettings = (object) $settings;
        if (
            self::SHOW_ALL_NOTICES || (!is_writable($varsDirectory) || !is_readable($varsDirectory))
            && isset($wpstgSettings->optimizer) && $wpstgSettings->optimizer
        ) {
            require "{$viewsNoticesPath}/mu-plugin-directory-permission-problem.php";
        }

        if (self::SHOW_ALL_NOTICES || empty($wpstgSettings->optimizer)) {
            require_once "{$viewsNoticesPath}disabled-optimizer-notice.php";
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
     * Render the notice dismiss action
     *
     * @param string $viewsNoticesPath
     * @param string $wpstgNotice
     * @param string $cssClassSelectorDismiss
     * @param string $cssClassSelectorNotice
     *
     * @todo Convert to Facade for testability?
     */
    public static function renderNoticeDismissAction($viewsNoticesPath, $wpstgNotice, $cssClassSelectorDismiss, $cssClassSelectorNotice)
    {
        require "{$viewsNoticesPath}_partial/notice_dismiss_action.php";
    }
}
