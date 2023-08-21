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
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Staging\CloneOptions;
use WPStaging\Framework\Staging\FirstRun;
use WPStaging\Framework\Support\ThirdParty\FreemiusScript;
use WPStaging\Framework\Support\ThirdParty\Jetpack;
use WPStaging\Framework\Support\ThirdParty\WordFence;
use WPStaging\Framework\Traits\NoticesTrait;
use WPStaging\Framework\Staging\Sites;

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

    /** @var string */
    const PRO_NOTICES_ACTION = 'wpstg.notices.show_pro_notices';

    /** @var string */
    const BASIC_NOTICES_ACTION = 'wpstg.notices.show_basic_notices';

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

    /** @var ObjectCacheNotice */
    private $objectCacheNotice;

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
        $this->dirUtil         = WPStaging::make(Directory::class);
        $this->wordfence       = WPStaging::make(WordFence::class);
        $this->cloneOptions    = WPStaging::make(CloneOptions::class);
        $this->freemiusScript  = WPStaging::make(FreemiusScript::class);
        $this->excludedPlugins = WPStaging::make(ExcludedPlugins::class);
        $this->logger          = WPStaging::getInstance()->get("logger");
        $this->cache           = WPStaging::getInstance()->get("cache");
        $this->db              = WPStaging::getInstance()->get('wpdb');

        // Notices
        $this->disabledItemsNotice     = WPStaging::make(DisabledItemsNotice::class);
        $this->warningsNotice          = WPStaging::make(WarningsNotice::class);
        $this->outdatedWpStagingNotice = WPStaging::make(OutdatedWpStagingNotice::class);
        $this->objectCacheNotice       = WPStaging::make(ObjectCacheNotice::class);
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
     * Load admin notices
     * @throws Exception
     */
    public function renderNotices()
    {
        if (!current_user_can(WPStaging::make(Capabilities::class)->manageWPSTG())) {
            return;
        }

        $viewsNoticesPath = "{$this->getPluginPath()}/Backend/views/notices/";

        if (!$this->isPro()) {
            do_action(self::BASIC_NOTICES_ACTION);
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
            $excludedFiles              = get_option(Sites::STAGING_EXCLUDED_FILES_OPTION, []);
            // use require here instead of require_once otherwise unit tests will always fail,
            // as this notice is tested multiple times.
            require "{$viewsNoticesPath}disabled-items-notice.php";
        }

        $optionTable              = $this->db->prefix . 'options';
        $isPrimaryKeyMissing      = $this->isOptionTablePrimaryKeyMissing($this->db, $optionTable);
        $isPrimaryKeyIsOptionName = $this->isOptionTablePrimaryKeyIsOptionName($this->db, $optionTable);
        if (self::SHOW_ALL_NOTICES || (current_user_can("manage_options") && ( $isPrimaryKeyMissing || $isPrimaryKeyIsOptionName ) && $this->isWPStagingAdminPage())) {
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

        // Show notice Outdated version of WP Staging Hooks
        if (self::SHOW_ALL_NOTICES || ($this->objectCacheNotice->isEnabled())) {
            require_once "{$viewsNoticesPath}object-cache-skipped.php";
        }

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

        // Show notice if db prefix does not exist
        if (self::SHOW_ALL_NOTICES || empty($this->db->prefix)) {
            require_once "{$viewsNoticesPath}no-db-prefix-notice.php";
        }
    }

    /**
     * Check whether the wp_options table is missing primary key | auto increment
     * @param wpdb $db
     * @param string $optionTable
     *
     * @return bool
     */
    private function isOptionTablePrimaryKeyMissing($db, $optionTable)
    {
        $result = $db->dbh->query("SELECT option_id FROM {$optionTable} LIMIT 1");
        $fInfo  = $result->fetch_field();
        $result->free_result();

        // Check whether the flag have primary key and auto increment flag
        if (isset($fInfo->flags) && ($fInfo->flags & MYSQLI_PRI_KEY_FLAG) && $fInfo->flags & MYSQLI_AUTO_INCREMENT_FLAG) {
            return false;
        }

        if ($this->isOptionTablePrimaryKeyIsOptionName($db, $optionTable)) {
            return false;
        }

        return true;
    }

    /** @return bool */
    private function isOptionTablePrimaryKeyIsOptionName($db, $optionTable)
    {
        $result = $db->dbh->query("SELECT option_name FROM {$optionTable} LIMIT 1");
        $fInfo  = $result->fetch_field();
        $result->free_result();

        // Abort if flag has no primary key
        if (!($fInfo->flags & MYSQLI_PRI_KEY_FLAG)) {
            return false;
        }

        // Check if the field has a composite key
        $results = $db->get_results("SELECT `CONSTRAINT_NAME`,`COLUMN_NAME` FROM `information_schema`.`KEY_COLUMN_USAGE` WHERE `table_name`='{$optionTable}' AND `table_schema`=DATABASE()", ARRAY_A);
        if (empty($results) || !is_array($results)) {
            return true;
        }

        $found = 0;
        while ($row = array_shift($results)) {
            if ($row['CONSTRAINT_NAME'] === 'PRIMARY' && in_array($row['COLUMN_NAME'], ['option_name', 'option_id'])) {
                $found++;
            }

            if ($found > 1) {
                return false;
            }
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
