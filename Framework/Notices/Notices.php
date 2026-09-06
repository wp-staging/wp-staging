<?php

namespace WPStaging\Framework\Notices;

use Exception;
use wpdb;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Backend\Optimizer\Optimizer;
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









class Notices
{
    use NoticesTrait;

 
    const ACTION_PRO_NOTICES = 'wpstg.notices.show_pro_notices';

 
    const ACTION_BASIC_NOTICES = 'wpstg.notices.show_basic_notices';

 
    const ACTION_INJECT_ANALYTICS_CONSENT_ASSETS = 'wpstg.assets.inject_analytics_consent_assets';

 
    const ACTION_ADMIN_NOTICES = 'wpstg.admin_notices';

 
    const ACTION_NETWORK_ADMIN_NOTICES = 'wpstg.network_admin_notices';

 
    const ACTION_ALL_ADMIN_NOTICES = 'wpstg.all_admin_notices';

 
    const FILTER_NOTICES_HIDE_DIRECTORY_LISTING_WARNINGS = 'wpstg.notices.hideDirectoryListingWarnings';

    const FILTER_NOTICES_HIDE_MISSING_PRIMARY_KEY_NOTICE = 'wpstg.notices.hideMissingPrimaryKeyNotice';

 
    private $assets;

 
    private $dirUtil;

 
    private $cache;

 
    private $logger;

 
    private $cloneOptions;

 
    private $excludedPlugins;

 
    private $freemiusScript;

 
    private $wordfence;

 
    private $disabledItemsNotice;

 
    private $warningsNotice;

 
    private $outdatedWpStagingNotice;

 
    private $objectCacheNotice;

 
    private $db;

 
    const SHOW_ALL_NOTICES = false;




    public static $directoryListingErrors = 'directoryListingErrors';

 
    private $siteInfo;

 
    private $viewsNoticesPath;

 
    private $settings;

 
    private $serverVars;

 
    private $isWpComSite;

 
    private $wpOptionsInfo;




    public function __construct(Assets $assets)
    {
        $this->assets           = $assets;
        $this->viewsNoticesPath = WPSTG_VIEWS_DIR . "notices/";

 
        $this->dirUtil         = WPStaging::make(Directory::class);
        $this->wordfence       = WPStaging::make(WordFence::class);
        $this->cloneOptions    = WPStaging::make(CloneOptions::class);
        $this->freemiusScript  = WPStaging::make(FreemiusScript::class);
        $this->excludedPlugins = WPStaging::make(ExcludedPlugins::class);
        $this->logger          = WPStaging::make("logger");
        $this->cache           = WPStaging::make("cache");
        $this->db              = WPStaging::make('wpdb');
        $this->wpOptionsInfo   = WPStaging::make(WpOptionsInfo::class);

 
        $this->disabledItemsNotice     = WPStaging::make(DisabledItemsNotice::class);
        $this->warningsNotice          = WPStaging::make(WarningsNotice::class);
        $this->outdatedWpStagingNotice = WPStaging::make(OutdatedWpStagingNotice::class);
        $this->objectCacheNotice       = WPStaging::make(ObjectCacheNotice::class);
        $this->siteInfo                = WPStaging::make(SiteInfo::class);
        $this->serverVars              = WPStaging::make(ServerVars::class);

        $this->isWpComSite = $this->siteInfo->isHostedOnWordPressCom();
    }






    protected function isPro(): bool
    {
        return WPStaging::isPro();
    }





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




    private function renderNoticesOnAllWpAdminPages()
    {
        $this->noticeListItemsDisabledOnStagingSite();
        $this->noticeDbHasMissingOrUnexpectedPrimaryKeys();
        $this->noticeWordFenceHasBeenDisabled();
        $this->noticeSettingsAreCorrupted();
        $this->noticeStagingUploadsFolderIsSymlinked();
        $this->noticeTableTmpPrefixConflictNotice();
        $this->noticeNextGenEngineStagingSites();
        $this->showAnalyticsModal();
    }







    private function noticeNextGenEngineStagingSites()
    {
        if (self::SHOW_ALL_NOTICES || WPStaging::make(NextGenEngineNotice::class)->isEnabled()) {
            require $this->viewsNoticesPath . "next-gen-engine-notice.php";
        }
    }




    private function renderNoticesBasicVersion()
    {
        if (!$this->isPro()) {
            do_action(self::ACTION_BASIC_NOTICES);
        }
    }




    private function renderNoticesProVersion()
    {
        if ($this->isPro()) {
 
            do_action(self::ACTION_PRO_NOTICES);
        }
    }




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




    private function noticeStagingUploadsFolderIsSymlinked()
    {
        $uploadsPath = wp_upload_dir()['basedir'];
        if (self::SHOW_ALL_NOTICES || (is_link($uploadsPath) && $this->siteInfo->isStagingSite())) {
            require_once $this->viewsNoticesPath . "staging-symlink-enabled-notice.php";
        }
    }




    private function noticeTableTmpPrefixConflictNotice()
    {
        $disallowedPrefixes = [DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP, DatabaseImporter::TMP_DATABASE_PREFIX];
        if (self::SHOW_ALL_NOTICES || in_array($this->db->prefix, $disallowedPrefixes, true)) {
            require $this->viewsNoticesPath . "table-tmp-prefix-conflict-notice.php";
        }
    }













    private function noticeShowDirectoryListingWarning(string $viewsNoticesPath)
    {
        $directoryListingErrors = WPStaging::getInstance()->getContainer()->getFromArray(static::$directoryListingErrors);

 
        if (!self::SHOW_ALL_NOTICES && empty($directoryListingErrors)) {
            return;
        }

 
        if (Hooks::applyFilters(self::FILTER_NOTICES_HIDE_DIRECTORY_LISTING_WARNINGS, false)) {
            return;
        }

        require_once "{$viewsNoticesPath}directory-listing-could-not-be-prevented.php";
    }





    private function isDifferentScheme(): bool
    {
        $siteurlScheme = parse_url(get_option('siteurl'), PHP_URL_SCHEME);
        $homeScheme    = parse_url(get_option('home'), PHP_URL_SCHEME);

        return !($siteurlScheme === $homeScheme);
    }





    private function isUsingOutdatedWpstgHooksPlugin(): bool
    {
 
        $versionToCheck = '0.0.4';

 
        $wpstgHooksPath = 'wp-staging-hooks/wp-staging-hooks.php';

 
        if (file_exists(WP_PLUGIN_DIR . '/' . $wpstgHooksPath)) {
            $wpstgHooksData = get_plugin_data(WP_PLUGIN_DIR . '/' . $wpstgHooksPath);
 
            return version_compare($wpstgHooksData['Version'], $versionToCheck, '>=') ? false : true;
        }

 
        $wpstgHooksPath = 'wp-staging-hooks.php';

 
        if (file_exists(WP_PLUGIN_DIR . '/' . $wpstgHooksPath)) {
            $wpstgHooksData = get_plugin_data(WP_PLUGIN_DIR . '/' . $wpstgHooksPath);
 
            return version_compare($wpstgHooksData['Version'], $versionToCheck, '>=') ? false : true;
        }

        return false;
    }











    public static function renderNoticeDismissAction(string $viewsNoticesPath, $wpstgNotice, $cssClassSelectorDismiss, $cssClassSelectorNotice)
    {
        require "{$viewsNoticesPath}_partial/notice_dismiss_action.php";
    }




    public function maybeShowElementorCloudNotice()
    {
        if (self::SHOW_ALL_NOTICES || ($this->isWPStagingClonePage() && $this->siteInfo->isHostedOnElementorCloud())) {
            require_once "{$this->viewsNoticesPath}elementor-cloud-notice.php";
        }
    }





    private function isSettingsCorrupt(): bool
    {
        if (!is_array($this->settings) && !is_object($this->settings)) {
            return true;
        }

        return false;
    }




    private function noticeDbHasMissingOrUnexpectedPrimaryKeys()
    {
        if (Hooks::applyFilters(self::FILTER_NOTICES_HIDE_MISSING_PRIMARY_KEY_NOTICE, false)) {
            return;
        }

        $optionTable              = $this->db->prefix . 'options';
        $isPrimaryKeyMissing      = $this->wpOptionsInfo->isOptionTablePrimaryKeyMissing($optionTable);
        $isPrimaryKeyIsOptionName = $this->wpOptionsInfo->isPrimaryKeyIsOptionName($optionTable);
        if (self::SHOW_ALL_NOTICES || (current_user_can("manage_options") && ($isPrimaryKeyMissing || $isPrimaryKeyIsOptionName) && $this->isWPStagingAdminPage())) {
            require $this->viewsNoticesPath . "wp-options-missing-pk.php";
        }
    }




    private function noticeDbPrefixDoesNotExist()
    {
        if (self::SHOW_ALL_NOTICES || empty($this->db->prefix)) {
            require_once $this->viewsNoticesPath . "no-db-prefix-notice.php";
        }
    }




    private function noticeWPEnginePermalinkWarning()
    {
        if (self::SHOW_ALL_NOTICES || class_exists('WPE_API')) {
            require_once $this->viewsNoticesPath . "wpe-permalink-issue-notice.php";
        }
    }




    private function noticeOptimizerIsDisabled()
    {
 
        if (get_option(Optimizer::OPTION_OPTIMIZER_DISABLED_AFTER_FATAL) === '1') {
            return;
        }

        $wpstgSettings = (object)$this->settings;
        if (self::SHOW_ALL_NOTICES || empty($wpstgSettings->optimizer)) {
            require_once $this->viewsNoticesPath . "disabled-optimizer-notice.php";
        }
    }




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




    private function noticeWpStagingHooksPluginIsOutdated()
    {
        if (self::SHOW_ALL_NOTICES || ($this->isUsingOutdatedWpstgHooksPlugin())) {
            require_once $this->viewsNoticesPath . "outdated-wp-staging-hooks.php";
        }
    }




    private function noticeHomeAndSiteurlHaveDifferentScheme()
    {
        if (self::SHOW_ALL_NOTICES || ($this->isDifferentScheme())) {
            require_once $this->viewsNoticesPath . "wrong-scheme.php";
        }
    }




    private function noticeAbspathDirectoryNotWriteable()
    {
 
        if (self::SHOW_ALL_NOTICES || ((!is_writable(ABSPATH)) && !$this->isWpComSite)) {
            require_once $this->viewsNoticesPath . "staging-directory-permission-problem.php";
        }
    }




    private function noticeLoggerDirectoryNotWriteable()
    {
        $logsDir = $this->logger->getLogDir();
        if (self::SHOW_ALL_NOTICES || (!is_dir($logsDir) || !is_writable($logsDir))) {
            require_once $this->viewsNoticesPath . "logs-directory-permission-problem.php";
        }
    }




    private function noticeCacheDirectoryNotWriteable()
    {
        $cacheDir = $this->cache->getPath();
        if (self::SHOW_ALL_NOTICES || (!is_dir($cacheDir) || !is_writable($cacheDir))) {
            require_once $this->viewsNoticesPath . "cache-directory-permission-problem.php";
        }
    }




    private function noticeObjectCachePluginNotRestored()
    {
        if (self::SHOW_ALL_NOTICES || ($this->objectCacheNotice->isEnabled())) {
            require_once $this->viewsNoticesPath . "object-cache-skipped.php";
        }
    }




    private function noticeWpStagingVersionIsOutdated()
    {



        $this->outdatedWpStagingNotice->showNotice($this->viewsNoticesPath);
    }




    private function noticeUploadsDirIsOutsideAbspath()
    {
        if (self::SHOW_ALL_NOTICES || (!$this->dirUtil->isPathInWpRoot($this->dirUtil->getUploadsDirectory()) && !$this->siteInfo->isFlywheel() && !$this->isWpComSite)) {
            require $this->viewsNoticesPath . "uploads-outside-wp-root.php";
        }
    }




    private function noticeSettingsAreCorrupted()
    {
        if (self::SHOW_ALL_NOTICES || ($this->isSettingsCorrupt())) {
            require $this->viewsNoticesPath . "settings_option_corrupt.php";
        }
    }




    private function noticeWordFenceHasBeenDisabled()
    {
        $this->wordfence->showNotice($this->viewsNoticesPath);
    }




    private function noticeListItemsDisabledOnStagingSite()
    {
 
        $outgoingMailsDisabled = false;

        if ($this->isPro()) {
 
            $outgoingMailsDisabled = $this->cloneOptions->get(FirstRun::MAILS_DISABLED_KEY) || (get_option(FirstRun::MAILS_DISABLED_KEY, false));
        }

 
        if (self::SHOW_ALL_NOTICES || $this->disabledItemsNotice->isEnabled()) {
            $excludedPlugins = (array)$this->excludedPlugins->getExcludedPlugins();
 
            $freemiusOptionsCleared = $this->freemiusScript->isNoticeEnabled();
 
            $isJetpackStagingModeActive = defined(Jetpack::STAGING_MODE_CONST) && constant(Jetpack::STAGING_MODE_CONST) === true;
            $excludedFiles              = get_option(Sites::STAGING_EXCLUDED_FILES_OPTION, []);
            $excludedHostingFiles       = get_option(Sites::STAGING_EXCLUDED_HOSTING_FILES_OPTION, []);
 
 
            require $this->viewsNoticesPath . "disabled-items-notice.php";
        }
    }




    private function noticeAiosSaltPostfixEnabled()
    {
        $aios = WPStaging::make(Aios::class);

 
        $aios->optimizerWhitelistUpdater();

        if (self::SHOW_ALL_NOTICES || $aios->isSaltPostfixOptionEnabled()) {
            require $this->viewsNoticesPath . "aios-salt-postfix-enabled.php";
        }
    }
}
