<?php

namespace WPStaging\Framework\Notices;

use Exception;
use wpdb;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\CloningProcess\ExcludedPlugins;
use WPStaging\Framework\Database\WpOptionsInfo;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Staging\CloneOptions;
use WPStaging\Staging\FirstRun;
use WPStaging\Framework\ThirdParty\FreemiusScript;
use WPStaging\Framework\ThirdParty\Jetpack;
use WPStaging\Framework\ThirdParty\WordFence;
use WPStaging\Framework\Traits\NoticesTrait;
use WPStaging\Staging\Sites;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\ServerVars;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\ThirdParty\Aios;

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

    /** @var string */
    const INJECT_ANALYTICS_CONSENT_ASSETS_ACTION = 'wpstg.assets.inject_analytics_consent_assets';

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

    /** @var string */
    private $viewsNoticesPath;

    /** @var false|mixed|null */
    private $settings;

    /** @var ServerVars */
    private $serverVars;

    /** @var bool */
    private $isWpComSite;

    /** @var WpOptionsInfo */
    private $wpOptionsInfo;

    /**
     * @param Assets $assets
     */
    public function __construct(Assets $assets)
    {
        $this->assets           = $assets;
        $this->viewsNoticesPath = WPSTG_VIEWS_DIR . "notices/";

        // To avoid dependency hell and smooth transition we will be using service locator for below dependencies
        $this->dirUtil         = WPStaging::make(Directory::class);
        $this->wordfence       = WPStaging::make(WordFence::class);
        $this->cloneOptions    = WPStaging::make(CloneOptions::class);
        $this->freemiusScript  = WPStaging::make(FreemiusScript::class);
        $this->excludedPlugins = WPStaging::make(ExcludedPlugins::class);
        $this->logger          = WPStaging::make("logger");
        $this->cache           = WPStaging::make("cache");
        $this->db              = WPStaging::make('wpdb');
        $this->wpOptionsInfo   = WPStaging::make(WpOptionsInfo::class);

        // Notices
        $this->disabledItemsNotice     = WPStaging::make(DisabledItemsNotice::class);
        $this->warningsNotice          = WPStaging::make(WarningsNotice::class);
        $this->outdatedWpStagingNotice = WPStaging::make(OutdatedWpStagingNotice::class);
        $this->objectCacheNotice       = WPStaging::make(ObjectCacheNotice::class);
        $this->siteInfo                = WPStaging::make(SiteInfo::class);
        $this->serverVars              = WPStaging::make(ServerVars::class);

        $this->isWpComSite = $this->siteInfo->isHostedOnWordPressCom();
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
     * @throws Exception
     */
    public function renderNotices()
    {
        if (!current_user_can(WPStaging::make(Capabilities::class)->manageWPSTG())) {
            return;
        }

        $this->settings = get_option('wpstg_settings', []);

        $this->renderNoticesBasicVersion();
        $this->renderNoticesProVersion();
        $this->renderNoticesOnAllWpAdminPages();
        $this->renderNoticesOnWpStagingAdminPages();
    }

    /**
     * @return void
     */
    private function renderNoticesOnAllWpAdminPages()
    {
        $this->noticeListItemsDisabledOnStagingSite();
        $this->noticeDbHasMissingOrUnexpectedPrimaryKeys();
        $this->noticeWordFenceHasBeenDisabled();
        $this->noticeSettingsAreCorrupted();
        $this->noticeStagingUploadsFolderIsSymlinked();
        $this->noticeTableTmpPrefixConflictNotice();
        $this->showAnalyticsModal();
    }

    /**
     * @return void
     */
    private function renderNoticesBasicVersion()
    {
        if (!$this->isPro()) {
            do_action(self::BASIC_NOTICES_ACTION);
        }
    }

    /**
     * @return void
     */
    private function renderNoticesProVersion()
    {
        if ($this->isPro()) {
            // This hook is for internal use only. Used in PRO version to display PRO version related notices.
            do_action(self::PRO_NOTICES_ACTION);
        }
    }

    /**
     * @return void
     */
    private function renderNoticesOnWpStagingAdminPages()
    {
        if (!current_user_can("update_plugins") || !$this->isWPStagingAdminPage()) {
            return;
        }

        $this->noticeUploadsDirIsOutsideAbspath();
        $this->noticeWpStagingVersionIsOutdated();
        $this->noticeObjectCachePluginNotRestored();
        $this->noticeCacheDirectoryNotWriteable();
        $this->noticeLoggerDirectoryNotWriteable();
        $this->noticeAbspathDirectoryNotWriteable();
        $this->noticeHomeAndSiteurlHaveDifferentScheme();
        $this->noticeWpStagingHooksPluginIsOutdated();
        $this->noticeMuPluginDirNotWriteable();
        $this->noticeOptimizerIsDisabled();
        $this->noticeShowDirectoryListingWarning($this->viewsNoticesPath);
        $this->noticeDbPrefixDoesNotExist();
        $this->noticeWPEnginePermalinkWarning();
        $this->noticeAiosSaltPostfixEnabled();
    }

    /**
     * @return void
     */
    private function noticeStagingUploadsFolderIsSymlinked()
    {
        $uploadsPath = wp_upload_dir()['basedir'];
        if (self::SHOW_ALL_NOTICES || (is_link($uploadsPath) && $this->siteInfo->isStagingSite())) {
            require_once $this->viewsNoticesPath . "staging-symlink-enabled-notice.php";
        }
    }

    /**
     * Show warning notice if current site prefix is equal to one of the WPSTG temporary prefixes wpstgtmp_ or wpstgbak_
     */
    private function noticeTableTmpPrefixConflictNotice()
    {
        $disallowedPrefixes = [DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP, DatabaseImporter::TMP_DATABASE_PREFIX];
        if (self::SHOW_ALL_NOTICES || in_array($this->db->prefix, $disallowedPrefixes, true)) {
            require $this->viewsNoticesPath . "table-tmp-prefix-conflict-notice.php";
        }
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
    private function noticeShowDirectoryListingWarning(string $viewsNoticesPath)
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

    /**
     * @return void
     */
    public function maybeShowElementorCloudNotice()
    {
        if (self::SHOW_ALL_NOTICES || ($this->isWPStagingClonePage() && $this->siteInfo->isHostedOnElementorCloud())) {
            require_once "{$this->viewsNoticesPath}elementor-cloud-notice.php";
        }
    }

    /**
     * @param $settings
     * @return bool
     */
    private function isSettingsCorrupt(): bool
    {
        if (!is_array($this->settings) && !is_object($this->settings)) {
            return true;
        }

        return false;
    }

    /**
     * @return void
     */
    private function noticeDbHasMissingOrUnexpectedPrimaryKeys()
    {
        if (Hooks::applyFilters('wpstg.notices.hideMissingPrimaryKeyNotice', false)) {
            return;
        }

        $optionTable              = $this->db->prefix . 'options';
        $isPrimaryKeyMissing      = $this->wpOptionsInfo->isOptionTablePrimaryKeyMissing($optionTable);
        $isPrimaryKeyIsOptionName = $this->wpOptionsInfo->isPrimaryKeyIsOptionName($optionTable);
        if (self::SHOW_ALL_NOTICES || (current_user_can("manage_options") && ($isPrimaryKeyMissing || $isPrimaryKeyIsOptionName) && $this->isWPStagingAdminPage())) {
            require $this->viewsNoticesPath . "wp-options-missing-pk.php";
        }
    }

    /**
     * @return void
     */
    private function noticeDbPrefixDoesNotExist()
    {
        if (self::SHOW_ALL_NOTICES || empty($this->db->prefix)) {
            require_once $this->viewsNoticesPath . "no-db-prefix-notice.php";
        }
    }

    /**
     * @return void
     */
    private function noticeWPEnginePermalinkWarning()
    {
        if (self::SHOW_ALL_NOTICES || class_exists('WPE_API')) {
            require_once $this->viewsNoticesPath . "wpe-permalink-issue-notice.php";
        }
    }

    /**
     * @param $wpstgSettings
     * @return void
     */
    private function noticeOptimizerIsDisabled()
    {
        $wpstgSettings = (object)$this->settings;
        if (self::SHOW_ALL_NOTICES || empty($wpstgSettings->optimizer)) {
            require_once $this->viewsNoticesPath . "disabled-optimizer-notice.php";
        }
    }

    /**
     * @return void
     */
    private function noticeMuPluginDirNotWriteable()
    {
        $varsDirectory = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : trailingslashit(WP_CONTENT_DIR) . 'mu-plugins';
        $wpstgSettings = (object)$this->settings;
        if (
            self::SHOW_ALL_NOTICES || (!is_writable($varsDirectory) || !is_readable($varsDirectory))
            && isset($wpstgSettings->optimizer) && $wpstgSettings->optimizer
        ) {
            require $this->viewsNoticesPath . "mu-plugin-directory-permission-problem.php";
        }
    }

    /**
     * @return void
     */
    private function noticeWpStagingHooksPluginIsOutdated()
    {
        if (self::SHOW_ALL_NOTICES || ($this->isUsingOutdatedWpstgHooksPlugin())) {
            require_once $this->viewsNoticesPath . "outdated-wp-staging-hooks.php";
        }
    }

    /**
     * @return void
     */
    private function noticeHomeAndSiteurlHaveDifferentScheme()
    {
        if (self::SHOW_ALL_NOTICES || ($this->isDifferentScheme())) {
            require_once $this->viewsNoticesPath . "wrong-scheme.php";
        }
    }

    /**
     * @return void
     */
    private function noticeAbspathDirectoryNotWriteable()
    {
        // Don't show this notice on WP Com Sites
        if (self::SHOW_ALL_NOTICES || ((!is_writable(ABSPATH)) && !$this->isWpComSite)) {
            require_once $this->viewsNoticesPath . "staging-directory-permission-problem.php";
        }
    }

    /**
     * @return void
     */
    private function noticeLoggerDirectoryNotWriteable()
    {
        $logsDir = $this->logger->getLogDir();
        if (self::SHOW_ALL_NOTICES || (!is_dir($logsDir) || !is_writable($logsDir))) {
            require_once $this->viewsNoticesPath . "logs-directory-permission-problem.php";
        }
    }

    /**
     * @return void
     */
    private function noticeCacheDirectoryNotWriteable()
    {
        $cacheDir = $this->cache->getPath();
        if (self::SHOW_ALL_NOTICES || (!is_dir($cacheDir) || !is_writable($cacheDir))) {
            require_once $this->viewsNoticesPath . "cache-directory-permission-problem.php";
        }
    }

    /**
     * @return void
     */
    private function noticeObjectCachePluginNotRestored()
    {
        if (self::SHOW_ALL_NOTICES || ($this->objectCacheNotice->isEnabled())) {
            require_once $this->viewsNoticesPath . "object-cache-skipped.php";
        }
    }

    /**
     * @return void
     */
    private function noticeWpStagingVersionIsOutdated()
    {
        /**
         * Display outdated WP Staging version notice (Free Only)
         */
        $this->outdatedWpStagingNotice->showNotice($this->viewsNoticesPath);
    }

    /**
     * @return void
     */
    private function noticeUploadsDirIsOutsideAbspath()
    {
        if (self::SHOW_ALL_NOTICES || (!$this->dirUtil->isPathInWpRoot($this->dirUtil->getUploadsDirectory()) && !$this->siteInfo->isFlywheel() && !$this->isWpComSite)) {
            require $this->viewsNoticesPath . "uploads-outside-wp-root.php";
        }
    }

    /**
     * @return void
     */
    private function noticeSettingsAreCorrupted()
    {
        if (self::SHOW_ALL_NOTICES || ($this->isSettingsCorrupt())) {
            require $this->viewsNoticesPath . "settings_option_corrupt.php";
        }
    }

    /**
     * @return void
     */
    private function noticeWordFenceHasBeenDisabled()
    {
        $this->wordfence->showNotice($this->viewsNoticesPath);
    }

    /**
     * @return void
     */
    private function noticeListItemsDisabledOnStagingSite()
    {
        // free version has no option to disable outgoing mails
        $outgoingMailsDisabled = false;

        if ($this->isPro()) {
            // Check mails disabled against both the old and new way of emails disabled option
            $outgoingMailsDisabled = $this->cloneOptions->get(FirstRun::MAILS_DISABLED_KEY) || (get_option(FirstRun::MAILS_DISABLED_KEY, false));
        }

        // Show notice about what disabled in the staging site. (Show only on staging site)
        if (self::SHOW_ALL_NOTICES || $this->disabledItemsNotice->isEnabled()) {
            $excludedPlugins = (array)$this->excludedPlugins->getExcludedPlugins();
            // Show freemius notice if freemius options were deleted during cloning.
            $freemiusOptionsCleared = $this->freemiusScript->isNoticeEnabled();
            // Show jetpack staging mode notice if the constant is set on staging site
            $isJetpackStagingModeActive = defined(Jetpack::STAGING_MODE_CONST) && constant(Jetpack::STAGING_MODE_CONST) === true;
            $excludedFiles              = get_option(Sites::STAGING_EXCLUDED_FILES_OPTION, []);
            $excludedGoDaddyFiles       = get_option(Sites::STAGING_EXCLUDED_GD_FILES_OPTION, []);
            // use require here instead of require_once otherwise unit tests will always fail,
            // as this notice is tested multiple times.
            require $this->viewsNoticesPath . "disabled-items-notice.php";
        }
    }

    /**
     * @return void
     */
    private function noticeAiosSaltPostfixEnabled()
    {
        $aios = WPStaging::make(Aios::class);

        // Execute it here to prevent this from being executed on each page request and to save db calls.
        $aios->optimizerWhitelistUpdater();

        if (self::SHOW_ALL_NOTICES || $aios->isSaltPostfixOptionEnabled()) {
            require $this->viewsNoticesPath . "aios-salt-postfix-enabled.php";
        }
    }
}
