<?php

namespace WPStaging\Framework\Notices;

use Exception;
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
use WPStaging\Framework\SiteInfo;
use WPStaging\Backup\Ajax\Restore\PrepareRestore;

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

    /** For testing all notices */
    const SHOW_ALL_NOTICES = false;

    /**
     * @var string The key that holds directory listing errors in the container.
     */
    public static $directoryListingErrors = 'directoryListingErrors';

    /** @var SiteInfo */
    private $siteInfo;

    /** @var string  */
    private $viewsNoticesPath;

    public function __construct(Assets $assets)
    {
        $this->assets = $assets;
        $this->viewsNoticesPath = trailingslashit($this->getPluginPath()) . "Backend/views/notices/";

        // To avoid dependency hell and smooth transition we will be using service locator for below dependencies
        $this->dirUtil         = WPStaging::make(Directory::class);
        $this->wordfence       = WPStaging::make(WordFence::class);
        $this->cloneOptions    = WPStaging::make(CloneOptions::class);
        $this->freemiusScript  = WPStaging::make(FreemiusScript::class);
        $this->excludedPlugins = WPStaging::make(ExcludedPlugins::class);
        $this->logger          = WPStaging::make("logger");
        $this->cache           = WPStaging::make("cache");
        $this->db              = WPStaging::make('wpdb');

        // Notices
        $this->disabledItemsNotice     = WPStaging::make(DisabledItemsNotice::class);
        $this->warningsNotice          = WPStaging::make(WarningsNotice::class);
        $this->outdatedWpStagingNotice = WPStaging::make(OutdatedWpStagingNotice::class);
        $this->objectCacheNotice       = WPStaging::make(ObjectCacheNotice::class);
        $this->siteInfo                = WPStaging::make(SiteInfo::class);
    }

    /**
     * Check whether the plugin is pro version
     *
     * @return  bool
     */
    protected function isPro(): bool
    {
        return WPStaging::isPro();
    }

    /**
     * Load admin notices
     * @todo refactor this method into smaller methods
     * @throws Exception
     */
    public function renderNotices()
    {
        if (!current_user_can(WPStaging::make(Capabilities::class)->manageWPSTG())) {
            return;
        }

        // Added this as workaround to not break multiple notices after the recent refactor
        // @todo refactor this class to avoid this workaround
        $viewsNoticesPath = $this->viewsNoticesPath;

        if (!$this->isPro()) {
            do_action(self::BASIC_NOTICES_ACTION);
        }

        // Never show disable mail message if it's free version
        $outgoingMailsDisabled = false;

        // Show all notices of the pro version
        if ($this->isPro()) {
            // Check mails disabled against both the old and new way of emails disabled option
            $outgoingMailsDisabled = $this->cloneOptions->get(FirstRun::MAILS_DISABLED_KEY) || (get_option(FirstRun::MAILS_DISABLED_KEY, false));
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
            require $this->viewsNoticesPath . "disabled-items-notice.php";
        }

        $optionTable              = $this->db->prefix . 'options';
        $isPrimaryKeyMissing      = $this->isOptionTablePrimaryKeyMissing($this->db, $optionTable);
        $isPrimaryKeyIsOptionName = $this->isOptionTablePrimaryKeyIsOptionName($this->db, $optionTable);
        if (self::SHOW_ALL_NOTICES || (current_user_can("manage_options") && ( $isPrimaryKeyMissing || $isPrimaryKeyIsOptionName ) && $this->isWPStagingAdminPage())) {
            require $this->viewsNoticesPath . "wp-options-missing-pk.php";
        }

        // Show notice if WordFence Firewall is disabled
        $this->wordfence->showNotice($this->viewsNoticesPath);

        $settings = get_option('wpstg_settings', []);
        if (self::SHOW_ALL_NOTICES || (!is_array($settings) && !is_object($settings))) {
            require $this->viewsNoticesPath . "settings_option_corrupt.php";
        }

        $this->showWarningIfStagingUploadsFolderIsSymlinked();

        $this->showTableTmpPrefixConflictNotice();

        /**
         * Display all notices below this line in WP STAGING admin pages only and only to administrators!
         */
        if (!current_user_can("update_plugins") || !$this->isWPStagingAdminPage()) {
            return;
        }

        // Show notice if uploads dir is outside ABSPATH
        if (self::SHOW_ALL_NOTICES || !$this->dirUtil->isPathInWpRoot($this->dirUtil->getUploadsDirectory())) {
            require $this->viewsNoticesPath . "uploads-outside-wp-root.php";
        }

        /**
         * Display outdated WP Staging version notice (Free Only)
         */
        $this->outdatedWpStagingNotice->showNotice($this->viewsNoticesPath);

        // Show notice Outdated version of WP Staging Hooks
        if (self::SHOW_ALL_NOTICES || ($this->objectCacheNotice->isEnabled())) {
            require_once $this->viewsNoticesPath . "object-cache-skipped.php";
        }

        // Show notice Cache directory is not writable
        $cacheDir = $this->cache->getCacheDir();
        if (self::SHOW_ALL_NOTICES || (!is_dir($cacheDir) || !is_writable($cacheDir))) {
            require_once $this->viewsNoticesPath . "cache-directory-permission-problem.php";
        }

        // Show notice Logger directory is not writable
        $logsDir = $this->logger->getLogDir();
        if (self::SHOW_ALL_NOTICES || (!is_dir($logsDir) || !is_writable($logsDir))) {
            require_once $this->viewsNoticesPath . "logs-directory-permission-problem.php";
        }

        // Show notice Staging directory is not writable
        if (self::SHOW_ALL_NOTICES || (!is_writable(ABSPATH))) {
            require_once $this->viewsNoticesPath . "staging-directory-permission-problem.php";
        }

        // Show notice Different scheme in home and siteurl
        if (self::SHOW_ALL_NOTICES || ($this->isDifferentScheme())) {
            require_once $this->viewsNoticesPath . "wrong-scheme.php";
        }

        // Show notice Outdated version of WP Staging Hooks
        if (self::SHOW_ALL_NOTICES || ($this->isUsingOutdatedWpstgHooksPlugin())) {
            require_once $this->viewsNoticesPath . "outdated-wp-staging-hooks.php";
        }

        // Show notice mu-plugin directory is not executable
        $varsDirectory = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : trailingslashit(WP_CONTENT_DIR) . 'mu-plugins';
        $wpstgSettings = (object) $settings;
        if (
            self::SHOW_ALL_NOTICES || (!is_writable($varsDirectory) || !is_readable($varsDirectory))
            && isset($wpstgSettings->optimizer) && $wpstgSettings->optimizer
        ) {
            require $this->viewsNoticesPath . "mu-plugin-directory-permission-problem.php";
        }

        if (self::SHOW_ALL_NOTICES || empty($wpstgSettings->optimizer)) {
            require_once $this->viewsNoticesPath . "disabled-optimizer-notice.php";
        }

        // Show notice Failed to prevent directory listing
        $this->showDirectoryListingWarningNotice($this->viewsNoticesPath);

        // Show notice if db prefix does not exist
        if (self::SHOW_ALL_NOTICES || empty($this->db->prefix)) {
            require_once $this->viewsNoticesPath . "no-db-prefix-notice.php";
        }
    }

    /**
     * @return void
     */
    private function showWarningIfStagingUploadsFolderIsSymlinked()
    {
        $uploadsPath = wp_upload_dir()['basedir'];
        if (self::SHOW_ALL_NOTICES || (is_link($uploadsPath) && $this->siteInfo->isStagingSite())) {
            require_once $this->viewsNoticesPath . "staging-symlink-enabled-notice.php";
        }
    }

    /**
     * Show warning notice if current site prefix is equal to one of the WPSTG temporary prefixes wpstgtmp_ or wpstgbak_
     */
    private function showTableTmpPrefixConflictNotice()
    {
        $disallowedPrefixes = [PrepareRestore::TMP_DATABASE_PREFIX_TO_DROP, PrepareRestore::TMP_DATABASE_PREFIX];
        if (self::SHOW_ALL_NOTICES || in_array($this->db->prefix, $disallowedPrefixes, true)) {
            require $this->viewsNoticesPath . "table-tmp-prefix-conflict-notice.php";
        }
    }

    /**
     * Check whether the wp_options table is missing primary key | auto increment
     * @param wpdb $db
     * @param string $optionTable
     *
     * @return bool
     */
    private function isOptionTablePrimaryKeyMissing(wpdb $db, string $optionTable): bool
    {
        $result = $db->dbh->query("SELECT option_id FROM {$optionTable} LIMIT 1");
        if (!is_object($result)) {
            return false;
        }

        $fInfo = $result->fetch_field();
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
    private function isOptionTablePrimaryKeyIsOptionName($db, $optionTable): bool
    {
        $result = $db->dbh->query("SELECT option_name FROM {$optionTable} LIMIT 1");
        if (!is_object($result)) {
            return false;
        }

        $fInfo = $result->fetch_field();
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
    private function showDirectoryListingWarningNotice(string $viewsNoticesPath)
    {
        $directoryListingErrors = WPStaging::getInstance()->getContainer()->getFromArray(static::$directoryListingErrors);

        // Early bail: No errors to show
        if (!self::SHOW_ALL_NOTICES && empty($directoryListingErrors)) {
            return;
        }

        // Early bail: These warnings were disabled by the user.
        if (apply_filters('wpstg.notices.hideDirectoryListingWarnings', false)) {
            return;
        }

        require_once "{$viewsNoticesPath}directory-listing-could-not-be-prevented.php";
    }

    /**
     * Check if the url scheme of siteurl and home is identical
     * @return bool
     */
    private function isDifferentScheme(): bool
    {
        $siteurlScheme = parse_url(get_option('siteurl'), PHP_URL_SCHEME);
        $homeScheme    = parse_url(get_option('home'), PHP_URL_SCHEME);

        return !($siteurlScheme === $homeScheme);
    }

    /**
     * Check if the user is using an outdated version of WP Staging Hooks plugin
     * @return bool
     */
    private function isUsingOutdatedWpstgHooksPlugin(): bool
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
    public static function renderNoticeDismissAction(string $viewsNoticesPath, $wpstgNotice, $cssClassSelectorDismiss, $cssClassSelectorNotice)
    {
        require "{$viewsNoticesPath}_partial/notice_dismiss_action.php";
    }
}
