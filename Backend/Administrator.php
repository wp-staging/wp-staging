<?php

namespace WPStaging\Backend;

use WPStaging\Backend\Modules\Jobs\Job;
use WPStaging\Core\WPStaging;
use WPStaging\Core\DTO\Settings;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingReset;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingUpdate;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\Database\DbInfo;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Mails\Report\Report;
use WPStaging\Framework\Filesystem\Filters\ExcludeFilter;
use WPStaging\Framework\Filesystem\DebugLogReader;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\CloningProcess\Database\CompareExternalDatabase;
use WPStaging\Framework\Utils\Math;
use WPStaging\Framework\Utils\WpDefaultDirectories;
use WPStaging\Framework\Notices\DismissNotice;
use WPStaging\Framework\Staging\Sites;
use WPStaging\Backend\Modules\Jobs\Cancel;
use WPStaging\Backend\Modules\Jobs\CancelUpdate;
use WPStaging\Backend\Modules\Jobs\Cloning;
use WPStaging\Backend\Modules\Jobs\Updating;
use WPStaging\Backend\Modules\Jobs\Delete;
use WPStaging\Backend\Modules\Jobs\Scan;
use WPStaging\Backend\Modules\Jobs\Logs;
use WPStaging\Backend\Modules\Jobs\ProcessLock;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Backend\Modules\Views\Tabs\Tabs;
use WPStaging\Backend\Modules\Views\Forms\Settings as FormSettings;
use WPStaging\Backend\Activation;
use WPStaging\Backend\Pro\Modules\Jobs\Processing;
use WPStaging\Backend\Pro\Modules\Jobs\Backups\BackupUploadsDir;
use WPStaging\Backend\Pluginmeta\Pluginmeta;
use WPStaging\Framework\Database\SelectedTables;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Backend\Pro\Modules\Jobs\Scan as ScanProModule;
use WPStaging\Backend\Feedback\Feedback;
use WPStaging\Backup\Ajax\Restore\PrepareRestore;
use WPStaging\Framework\Database\WpDbInfo;
use WPStaging\Framework\Utils\PluginInfo;
use WPStaging\Framework\Security\Nonce;

/**
 * Class Administrator
 * @package WPStaging\Backend
 */
class Administrator
{
    /**
     * @var int Place WP Staging Menu below Plugins
     */
    const MENU_POSITION_ORDER = 65;

    /**
     * @var int Place WP Staging Menu below Plugins for multisite
     */
    const MENU_POSITION_ORDER_MULTISITE = 20;

    /**
     * Path to plugin's Backend Dir
     * @var string
     */
    private $path;

    /**
     * @var Assets
     */
    private $assets;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var SiteInfo
     */
    private $siteInfo;

    /** @var Sanitize */
    private $sanitize;

    /** @var Report */
    private $report;

    /** @var PluginInfo */
    private $pluginInfo;

    public function __construct()
    {
        $this->auth       = WPStaging::make(Auth::class);
        $this->assets     = WPStaging::make(Assets::class);
        $this->siteInfo   = WPStaging::make(SiteInfo::class);
        $this->report     = WPStaging::make(Report::class);
        $this->pluginInfo = WPStaging::make(PluginInfo::class);

        $this->defineHooks();

        // Path to backend
        $this->path = plugin_dir_path(__FILE__);

        $this->sanitize = WPStaging::make(Sanitize::class);

        // Load plugins meta data
        $this->loadMeta();
    }

    /**
     * Load plugin meta data
     */
    public function loadMeta()
    {
        new Pluginmeta();
    }

    /**
     * Define Hooks
     */
    private function defineHooks()
    {
        if (!defined('WPSTGPRO_VERSION')) {
            new Activation\Welcome();
        }

        if ($this->pluginInfo->canShowAdminMenu()) {
            add_action("admin_menu", [$this, "addMenu"], 10);
        }

        add_action("admin_init", [$this, "upgrade"]);
        add_action("admin_post_wpstg_download_sysinfo", [$this, "downloadSystemInfoAndLogFiles"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        if (!defined('WPSTGPRO_VERSION') && $this->isPluginsPage()) {
            add_filter('admin_footer', [$this, 'loadFeedbackForm']);
        }

        // Ajax Requests
        add_action("wp_ajax_wpstg_overview", [$this, "ajaxOverview"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_scanning", [$this, "ajaxCloneScan"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_check_clone", [$this, "ajaxCheckCloneDirectoryName"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_restart", [$this, "ajaxRestart"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_update", [$this, "ajaxUpdateProcess"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_reset", [$this, "ajaxResetProcess"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_cloning", [$this, "ajaxStartClone"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_processing", [$this, "ajaxCloneDatabase"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_database_connect", [$this, "ajaxDatabaseConnect"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_database_verification", [$this, "ajaxDatabaseVerification"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_clone_prepare_directories", [$this, "ajaxPrepareDirectories"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_clone_files", [$this, "ajaxCopyFiles"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_clone_replace_data", [$this, "ajaxReplaceData"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_clone_finish", [$this, "ajaxFinish"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_confirm_delete_clone", [$this, "ajaxDeleteConfirmation"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_delete_clone", [$this, "ajaxDeleteClone"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_cancel_clone", [$this, "ajaxCancelClone"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_cancel_update", [$this, "ajaxCancelUpdate"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_hide_rating", [$this, "ajaxHideRating"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_hide_later", [$this, "ajaxHideLaterRating"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_hide_beta", [$this, "ajaxHideBeta"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_logs", [$this, "ajaxLogs"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_check_disk_space", [$this, "ajaxCheckFreeSpace"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_send_report", [$this, "ajaxSendReport"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_send_feedback", [$this, "sendFeedback"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_enable_staging_cloning", [$this, "ajaxEnableStagingCloning"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_clone_excludes_settings", [$this, "ajaxCloneExcludesSettings"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_fetch_dir_children", [$this, "ajaxFetchDirChildren"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_modal_error", [$this, "ajaxModalError"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_dismiss_notice", [$this, "ajaxDismissNotice"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_restore_settings", [$this, "ajaxRestoreSettings"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_send_debug_log_report", [$this->report, "ajaxSendDebugLog"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        // Ajax hooks pro Version
        // TODO: move all below actions to pro service provider?
        add_action("wp_ajax_wpstg_edit_clone_data", [$this, "ajaxEditCloneData"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_save_clone_data", [$this, "ajaxSaveCloneData"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_scan", [$this, "ajaxPushScan"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_push_tables", [$this, "ajaxPushTables"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_push_processing", [$this, "ajaxPushProcessing"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_nopriv_wpstg_push_processing", [$this, "ajaxPushProcessing"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        // TODO: replace uploads backup during push once we have backups PR ready,
        // Then there will be no need to have any cron to delete those backups
        if (class_exists('WPStaging\Backend\Pro\Modules\Jobs\Backups\BackupUploadsDir')) {
            add_action(BackupUploadsDir::BACKUP_DELETE_CRON_HOOK_NAME, [$this, "removeOldUploadsBackup"]); // phpcs:ignore WPStaging.Security.FirstArgNotAString -- Cron callback
        }
    }

    /**
     * Load Feedback Form on plugins.php
     */
    public function loadFeedbackForm()
    {
        $form = WPStaging::make(Feedback::class);
        $form->loadForm();
    }

    /**
     * Send Feedback data via mail
     */
    public function sendFeedback()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $form = WPStaging::make(Feedback::class);
        $form->sendMail();
    }

    /**
     * Upgrade routine
     * @action admin_init 10 0
     * @see \WPStaging\Backend\Administrator::defineHooks
     */
    public function upgrade()
    {
        if (defined('WPSTGPRO_VERSION') && class_exists('WPStaging\Backend\Pro\Upgrade\Upgrade')) {
            $upgrade = WPStaging::make('WPStaging\Backend\Pro\Upgrade\Upgrade');
        } else {
            $upgrade = WPStaging::make('WPStaging\Backend\Upgrade\Upgrade');
        }
        $upgrade->doUpgrade();
    }

    /**
     * Add Admin Menu(s)
     */
    public function addMenu()
    {
        global $wp_version;
        $logo = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCFET0NUWVBFIHN2ZyBQVUJMSUMgIi0vL1czQy8vRFREIFNWRyAxLjEvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkIj4KPHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgMTAwMCAxMDAwIiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAxMDAwIDEwMDAiIHhtbDpzcGFjZT0icHJlc2VydmUiIGZpbGw9Im5vbmUiPgo8Zz48Zz48cGF0aCBzdHlsZT0iZmlsbDojZmZmIiAgZD0iTTEzNy42LDU2MS4zSDEzLjhIMTB2MzA2LjNsOTAuNy04My40QzE4OS42LDkwOC43LDMzNS4zLDk5MCw1MDAsOTkwYzI0OS45LDAsNDU2LjEtMTg3LjEsNDg2LjItNDI4LjhIODYyLjRDODMzLjMsNzM1LjEsNjgyLjEsODY3LjUsNTAwLDg2Ny41Yy0xMjksMC0yNDIuNS02Ni41LTMwOC4xLTE2Ny4ybDE1MS4zLTEzOS4xSDEzNy42eiIvPjxwYXRoIHN0eWxlPSJmaWxsOiNmZmYiICBkPSJNNTAwLDEwQzI1MC4xLDEwLDQzLjksMTk3LjEsMTMuOCw0MzguOGgxMjMuOEMxNjYuNywyNjQuOSwzMTcuOSwxMzIuNSw1MDAsMTMyLjVjMTMyLjksMCwyNDkuMyw3MC41LDMxMy44LDE3Ni4yTDY4My44LDQzOC44aDEyMi41aDU2LjJoMTIzLjhoMy44VjEzMi41bC04Ny43LDg3LjdDODEzLjgsOTMuMSw2NjYuNiwxMCw1MDAsMTB6Ii8+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjwvZz4KPC9zdmc+';

        $pos = self::MENU_POSITION_ORDER;
        if (is_multisite()) {
            $pos = self::MENU_POSITION_ORDER_MULTISITE;
        }

        // Menu Position order needs to be unique for WordPress < 4.4
        // We are not using a unique position by default to keep WP Staging directly below plugin menu
        if (version_compare($wp_version, '4.4', '<')) {
            $pos++;
        }

        $proSlug = defined('WPSTGPRO_VERSION') ? 'Pro' : '';

        $defaultPageSlug       = "wpstg_clone";
        $defaultPageCallback   = "getClonePage";
        $defaultPageTitle      = esc_html__("Staging Sites", "wp-staging");
        $secondaryPageSlug     = "wpstg_backup";
        $secondaryPageCallback = "getBackupPage";
        $secondaryPageTitle    = esc_html__("Backup & Migration", "wp-staging");
        /** @var SiteInfo */
        $siteInfo = WPStaging::make(SiteInfo::class);
        if ($siteInfo->isHostedOnWordPressCom()) {
            $defaultPageSlug       = "wpstg_backup";
            $defaultPageCallback   = "getBackupPage";
            $defaultPageTitle      = esc_html__("Backup & Migration", "wp-staging");
            $secondaryPageSlug     = "wpstg_clone";
            $secondaryPageCallback = "getClonePage";
            $secondaryPageTitle    = esc_html__("Staging Sites", "wp-staging");
        }

        // Main WP Staging Menu
        add_menu_page(
            "WP STAGING",
            __("WP Staging " . $proSlug, "wp-staging"),
            "manage_options",
            $defaultPageSlug,
            [$this, $defaultPageCallback],
            $logo,
            $pos
        );

        // Clone page normally but backup page on WordPress.com
        add_submenu_page(
            $defaultPageSlug,
            __("WP Staging Jobs", "wp-staging"),
            $defaultPageTitle,
            "manage_options",
            $defaultPageSlug,
            [$this, $defaultPageCallback]
        );

        // Backup page normally but clone page on WordPress.com
        add_submenu_page(
            $defaultPageSlug,
            __("WP Staging Jobs", "wp-staging"),
            $secondaryPageTitle,
            "manage_options",
            $secondaryPageSlug,
            [$this, $secondaryPageCallback]
        );

        // Page: Settings
        add_submenu_page(
            $defaultPageSlug,
            __("WP Staging Settings", "wp-staging"),
            __("Settings", "wp-staging"),
            "manage_options",
            "wpstg-settings",
            [$this, "getSettingsPage"]
        );

        // Page: Tools
        add_submenu_page(
            $defaultPageSlug,
            __("WP Staging Tools", "wp-staging"),
            __("System Info", "wp-staging"),
            "manage_options",
            "wpstg-tools",
            [$this, "getToolsPage"]
        );

        if (!defined('WPSTGPRO_VERSION')) {
            // Page: Tools
            add_submenu_page(
                $defaultPageSlug,
                __("WP Staging Welcome", "wp-staging"),
                __("Get WP Staging Pro", "wp-staging"),
                "manage_options",
                "wpstg-welcome",
                [$this, "getWelcomePage"]
            );
        }

        if (defined('WPSTGPRO_VERSION')) {
            // Page: License
            add_submenu_page(
                $defaultPageSlug,
                __("WP Staging License", "wp-staging"),
                __("License", "wp-staging"),
                "manage_options",
                "wpstg-license",
                [$this, "getLicensePage"]
            );
        }
    }

    /**
     * Settings Page
     */
    public function getSettingsPage()
    {

        $license = get_option('wpstg_license_status');

        // Tabs
        $tabs = new Tabs(apply_filters('wpstg_main_settings_tabs', [
            "general" => __("General", "wp-staging")
        ]));

        WPStaging::getInstance()
            // Set tabs
            ->set("tabs", $tabs)
            // Forms
            ->set("forms", new FormSettings($tabs));

        require_once "{$this->path}views/settings/main-settings.php";
    }

    /**
     * Clone Page
     */
    public function getClonePage()
    {

        $license = get_option('wpstg_license_status');

        $availableClones = get_option(Sites::STAGING_SITES_OPTION, []);

        require_once "{$this->path}views/clone/index.php";
    }

    /**
     * Backup & Migration Page
     */
    public function getBackupPage()
    {
        $license = get_option('wpstg_license_status');

        // Existing clones
        $availableClones = get_option(Sites::STAGING_SITES_OPTION, []);

        $isBackupPage = true;

        require_once "{$this->path}views/clone/index.php";
    }

    /**
     * Welcome Page
     */
    public function getWelcomePage()
    {
        if (defined('WPSTGPRO_VERSION')) {
            return;
        }

        require_once "{$this->path}views/welcome/welcome.php";
    }

    /**
     * Tools Page
     */
    public function getToolsPage()
    {
        // Tabs
        $tabs = new Tabs([
            "system-info" => __("System Info", "wp-staging")
        ]);

        WPStaging::getInstance()->set("tabs", $tabs);

        WPStaging::getInstance()->set("systemInfo", new SystemInfo());

        // Get license data
        $license = get_option('wpstg_license_status');

        require_once "{$this->path}views/tools/index.php";
    }

    /**
     * Download System Information and latest log files.
     * @return void
     */
    public function downloadSystemInfoAndLogFiles()
    {
        if (!current_user_can("update_plugins")) {
            return;
        }

        nocache_headers();
        header("Content-Type: text/plain");
        header('Content-Disposition: attachment; filename="wpstg-bundled-logs.txt"');
        echo esc_html(wp_strip_all_tags(WPStaging::make(SystemInfo::class)->get("systemInfo")));
        echo esc_html("\n\n" . str_repeat("-", 25) . "\n\n");
        $wpstgLogs = WPStaging::make(DebugLogReader::class)->getLastLogEntries(100 * KB_IN_BYTES, true, false);
        echo esc_html(wp_strip_all_tags($wpstgLogs));
        echo esc_html(PHP_EOL . PHP_EOL . str_repeat("-", 25) . PHP_EOL . PHP_EOL);
        $wpCoreDebugLog =  WPStaging::make(DebugLogReader::class)->getLastLogEntries((256 * KB_IN_BYTES ), false, true);
        echo esc_html(wp_strip_all_tags($wpCoreDebugLog));

        $latestLogFiles = WPStaging::make(DebugLogReader::class)->getLatestLogFiles();
        if (count($latestLogFiles) === 0) {
            return;
        }

        foreach ($latestLogFiles as $logFile) {
            echo esc_html(PHP_EOL . PHP_EOL . str_repeat("-", 25) . PHP_EOL . PHP_EOL);
            $logs = file_get_contents($logFile);
            echo esc_html($logs);
        }
    }

    /**
     * Render a view file
     * @param string $file
     * @param array $vars
     * @return string
     */
    public function render($file, $vars = [])
    {
        $fullPath = $this->path . "views/" . $file . ".php";
        $fullPath = wp_normalize_path($fullPath);

        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            return "Can't render : {$fullPath} either file doesn't exist or can't read it";
        }

        $contents = @file_get_contents($fullPath);

        // Variables are set
        if (count($vars) > 0) {
            $vars = array_combine(
                array_map(function ($key) {
                    return "{{" . $key . "}}";
                }, array_keys($vars)),
                $vars
            );

            $contents = str_replace(array_keys($vars), array_values($vars), $contents);
        }

        return $contents;
    }

    /**
     * @return bool Whether the current request is considered to be authenticated.
     */
    private function isAuthenticated($nonce = Nonce::WPSTG_NONCE)
    {
        return $this->auth->isAuthenticatedRequest($nonce);
    }

    /**
     * Restart cloning process
     */
    public function ajaxRestart()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $process = WPStaging::make(ProcessLock::class);
        $process->restart();
    }

    /**
     * Ajax Overview
     */
    public function ajaxOverview()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        /** @var SiteInfo */
        $siteInfo = WPStaging::make(SiteInfo::class);
        if ($siteInfo->isHostedOnWordPressCom()) {
            require_once "{$this->path}views/clone/wordpress-com/index.php";
            wp_die(); // Using wp_die instead of return here to make sure no 0 is appended to the response
        }

        // Existing clones
        $sites           = WPStaging::make(Sites::class);
        $availableClones = $sites->getSortedStagingSites();

        // Get license data
        $license = get_option('wpstg_license_status');

        // Get db
        $db = WPStaging::make('wpdb');

        $iconPath = $this->assets->getAssetsUrl('svg/vendor/dashicons/cloud.svg');

        require_once "{$this->path}views/clone/ajax/single-overview.php";

        wp_die();
    }

    /**
     * Ajax Scan
     * @action wp_ajax_wpstg_scanning 10 0
     * @see Administrator::defineHooks()
     */
    public function ajaxCloneScan()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        // Check first if there is already a process running
        $processLock = WPStaging::make(ProcessLock::class);
        $response    = $processLock->ajaxIsRunning();
        if ($response !== false) {
            echo json_encode($response);

            exit();
        }

        $db = WPStaging::make('wpdb');

        // Scan
        $scan = WPStaging::make(Scan::class);
        $scan->setGifLoaderPath($this->assets->getAssetsUrl('img/spinner.gif'));
        $scan->setInfoIcon($this->assets->getAssetsUrl('svg/vendor/dashicons/info-outline.svg'));
        $scan->start();

        // Get Options
        $options              = $scan->getOptions();
        $excludeUtils         = WPStaging::make(ExcludeFilter::class);
        $wpDefaultDirectories = WPStaging::make(WpDefaultDirectories::class);
        require_once "{$this->path}views/clone/ajax/scan.php";

        wp_die();
    }

    /**
     * Fetch children of the given directory
     */
    public function ajaxFetchDirChildren()
    {
        if (!$this->isAuthenticated()) {
            wp_send_json(['success' => false]);
            return;
        }

        $isChecked    = isset($_POST['isChecked']) ? $this->sanitize->sanitizeBool($_POST['isChecked']) : false;
        $forceDefault = isset($_POST['forceDefault']) ? $this->sanitize->sanitizeBool($_POST['forceDefault']) : false;
        $path         = isset($_POST['dirPath']) ? $this->sanitize->sanitizePath($_POST['dirPath']) : "";
        $prefix       = isset($_POST['prefix']) ? $this->sanitize->sanitizePath($_POST['prefix']) : "";
        $basePath     = ABSPATH;
        if ($prefix === PathIdentifier::IDENTIFIER_WP_CONTENT) {
            $basePath = WP_CONTENT_DIR;
        }

        $path         = trailingslashit($basePath) . $path;
        $scan         = new Scan($path);
        $scan->setBasePath($basePath);
        $scan->setPathIdentifier($prefix);
        $scan->setGifLoaderPath($this->assets->getAssetsUrl('img/spinner.gif'));
        $scan->getDirectories($path);
        wp_send_json([
            "success"          => true,
            "directoryListing" => json_encode($scan->directoryListing($isChecked, $forceDefault)),
        ]);
    }

    /**
     * Ajax Check Clone Name
     */
    public function ajaxCheckCloneDirectoryName()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        /** @var Sites $sitesHelper */
        $sitesHelper        = WPStaging::make(Sites::class);
        $cloneDirectoryName = isset($_POST["directoryName"]) ? $sitesHelper->sanitizeDirectoryName($_POST["directoryName"]) : '';

        if (strlen($cloneDirectoryName) < 1) {
            return;
        }

        $result = $sitesHelper->isCloneExists($cloneDirectoryName);
        if ($result === false) {
            wp_send_json(["status" => "success"]);
            return;
        }

        wp_send_json([
            "status"  => "failed",
            "message" => $result
        ]);
    }

    /**
     * Ajax Start Updating Clone (Basically just layout and saving data)
     */
    public function ajaxUpdateProcess()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $cloning = WPStaging::make(Updating::class);

        if (!$cloning->save()) {
            wp_die('Can not save clone data');
        }

        $options = $cloning->getOptions();
        WPStaging::make(AnalyticsStagingUpdate::class)->enqueueStartEvent($options->jobIdentifier, $options);

        require_once "{$this->path}views/clone/ajax/update.php";

        wp_die();
    }

    /**
     * Ajax Start Resetting Clone
     */
    public function ajaxResetProcess()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $cloning = WPStaging::make(Updating::class);
        $cloning->setMainJob(Job::RESET);
        if (!$cloning->save()) {
            wp_die('can not save clone data');
        }

        $options = $cloning->getOptions();
        WPStaging::make(AnalyticsStagingReset::class)->enqueueStartEvent($options->jobIdentifier, $options);

        require_once "{$this->path}views/clone/ajax/update.php";
        wp_die();
    }

    /**
     * Ajax Start Clone (Basically just layout and saving data)
     */
    public function ajaxStartClone()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        // Check first if there is already a process running
        $processLock = WPStaging::make(ProcessLock::class);
        $processLock->isRunning();

        $cloning = WPStaging::make(Cloning::class);

        if (!$cloning->save()) {
            $message = $cloning->getErrorMessage();
            wp_send_json([
                'success' => false,
                'message' => $message !== '' ? $message : 'Can not save clone data'
            ]);

            wp_die();
        }

        require_once "{$this->path}views/clone/ajax/start.php";

        wp_die();
    }

    /**
     * Ajax Clone Database
     */
    public function ajaxCloneDatabase()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        wp_send_json(WPStaging::make(Cloning::class)->start());
    }

    /**
     * Ajax Prepare Directories (get listing of files)
     */
    public function ajaxPrepareDirectories()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        wp_send_json(WPStaging::make(Cloning::class)->start());
    }

    /**
     * Ajax Clone Files
     */
    public function ajaxCopyFiles()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        wp_send_json(WPStaging::make(Cloning::class)->start());
    }

    /**
     * Ajax Replace Data
     */
    public function ajaxReplaceData()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        wp_send_json(WPStaging::make(Cloning::class)->start());
    }

    /**
     * Ajax Finish
     */
    public function ajaxFinish()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        wp_send_json(WPStaging::make(Cloning::class)->start());
    }

    /**
     * Ajax Delete Confirmation
     */
    public function ajaxDeleteConfirmation()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $delete = WPStaging::make(Delete::class);

        $isDatabaseConnected = $delete->setData();

        $clone = $delete->getClone();

        $dbname = $delete->getDbName();

        require_once "{$this->path}views/clone/ajax/delete-confirmation.php";

        wp_die();
    }

    /**
     * Delete clone
     */
    public function ajaxDeleteClone()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $delete = WPStaging::make(Delete::class);

        wp_send_json($delete->start());
    }

    /**
     * Cancel clone
     */
    public function ajaxCancelClone()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $cancel = WPStaging::make(Cancel::class);
        wp_send_json($cancel->start());
    }

    /**
     * Cancel updating process / Do not delete clone!
     */
    public function ajaxCancelUpdate()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $cancelUpdate = WPStaging::make(CancelUpdate::class);
        wp_send_json($cancelUpdate->start());
    }

    /**
     * Ajax Hide Rating
     *
     * Runs when the user dismisses the notice to rate the plugin.
     */
    public function ajaxHideRating()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        if (update_option("wpstg_rating", "no") !== false) {
            wp_send_json(true);
        }

        wp_send_json(null);
    }

    /**
     * Ajax Hide Rating and show it again after one week
     *
     * Runs when the user chooses to rate the plugin later.
     */
    public function ajaxHideLaterRating()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $date = date('Y-m-d', strtotime(date('Y-m-d') . ' + 7 days'));
        if (update_option('wpstg_rating', $date) !== false) {
            wp_send_json(true);
        }

        wp_send_json(false);
    }

    /**
     * Ajax Hide Beta
     */
    public function ajaxHideBeta()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        wp_send_json(update_option("wpstg_beta", "no"));
    }

    /**
     * @return void
     */
    public function ajaxDismissNotice()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        // Early bail if no notice option available
        if (!isset($_POST['wpstg_notice'])) {
            wp_send_json(null);
            return;
        }

        /** @var DismissNotice */
        $dismissNotice = WPStaging::make(DismissNotice::class);
        $dismissNotice->dismiss($this->sanitize->sanitizeString($_POST['wpstg_notice']));
    }

    /**
     * Clone logs
     */
    public function ajaxLogs()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $logs = WPStaging::make(Logs::class);
        wp_send_json($logs->start());
    }

    /**
     * Ajax Checks Free Disk Space
     */
    public function ajaxCheckFreeSpace()
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $excludedDirectories = isset($_POST["excludedDirectories"]) ? $this->sanitize->sanitizeString($_POST["excludedDirectories"]) : '';
        $extraDirectories    = isset($_POST["extraDirectories"]) ? $this->sanitize->sanitizeString($_POST["extraDirectories"]) : '';

        $scan = WPStaging::make(Scan::class);
        return $scan->hasFreeDiskSpace($excludedDirectories, $extraDirectories);
    }

    /**
     * Allows the user to edit the clone's data
     */
    public function ajaxEditCloneData()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $existingClones = get_option(Sites::STAGING_SITES_OPTION, []);
        if (isset($_POST["clone"]) && array_key_exists($_POST["clone"], $existingClones)) {
            $clone = $existingClones[$this->sanitize->sanitizeString($_POST["clone"])];
            require_once "{$this->path}Pro/views/edit-clone-data.php";
        } else {
            echo esc_html__("Unknown error. Please reload the page and try again", "wp-staging");
        }

        wp_die();
    }

    /**
     * Allow the user to Save Clone Data
     */
    public function ajaxSaveCloneData()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $existingClones = get_option(Sites::STAGING_SITES_OPTION, []);
        if (isset($_POST["clone"]) && array_key_exists($_POST["clone"], $existingClones)) {
            if (empty($_POST['directoryName'])) {
                echo esc_html__("Site name is required!", "wp-staging");
                wp_die();
            }

            $cloneId            = $this->sanitize->sanitizeString($_POST["clone"]);
            $cloneName          = isset($_POST["cloneName"]) ? $this->sanitize->sanitizeString($_POST["cloneName"]) : '';
            $cloneDirectoryName = $this->sanitize->sanitizeString($_POST["directoryName"]);
            $cloneDirectoryName = preg_replace("#\W+#", '-', strtolower($cloneDirectoryName));

            $updateClones = [
                "cloneName"        => $this->sanitize->sanitizeString($cloneName),
                "directoryName"    => $this->sanitize->sanitizeString($cloneDirectoryName),
                "path"             => isset($_POST["path"]) ? $this->sanitize->sanitizeString($_POST["path"]) : '',
                "url"              => isset($_POST["url"]) ? $this->sanitize->sanitizeString($_POST["url"]) : '',
                "prefix"           => isset($_POST["prefix"]) ? $this->sanitize->sanitizeString($_POST["prefix"]) : '',
                "databaseUser"     => isset($_POST["externalDBUser"]) ? $this->sanitize->sanitizeString($_POST["externalDBUser"]) : '',
                "databasePassword" => isset($_POST["externalDBPassword"]) ? $this->sanitize->sanitizePassword($_POST["externalDBPassword"]) : '',
                "databaseDatabase" => isset($_POST["externalDBDatabase"]) ? $this->sanitize->sanitizeString($_POST["externalDBDatabase"]) : '',
                "databaseServer"   => isset($_POST["externalDBHost"]) ? $this->sanitize->sanitizeString($_POST["externalDBHost"]) : 'localhost',
                "databasePrefix"   => isset($_POST["externalDBPrefix"]) ? $this->sanitize->sanitizeString($_POST["externalDBPrefix"]) : 'wp_',
                "databaseSsl"      => isset($_POST["externalDBSsl"]) && 'true' === $this->sanitize->sanitizeString($_POST["externalDBSsl"]) ? true : false,
                "datetime"         => ! empty($existingClones["datetime"]) ? $existingClones["datetime"] : time(),
                "ownerId"          => ! empty($existingClones['ownerId']) ? $existingClones['ownerId'] : get_current_user_id()
            ];

            $existingClones[$cloneId] = array_merge($existingClones[$cloneId], $updateClones);

            if (update_option(Sites::STAGING_SITES_OPTION, $existingClones)) {
                // Update datetime if data was updated
                $existingClones[$cloneId]["datetime"] = time();
                update_option(Sites::STAGING_SITES_OPTION, $existingClones);
            }

            echo esc_html__("Success", "wp-staging");
        } else {
            echo esc_html__("Unknown error. Please reload the page and try again", "wp-staging");
        }

        wp_die();
    }

    /**
     * Ajax Start Push Changes Process
     * Start with the module Scan
     * @action wp_ajax_wpstg_scans 10 0
     * @see Administrator::defineHooks()
     */
    public function ajaxPushScan()
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        if (!class_exists('WPStaging\Backend\Pro\Modules\Jobs\Scan')) {
            return false;
        }

        // Scan
        $scan = WPStaging::make(ScanProModule::class);

        $scan->start();

        // Get Options
        $options = $scan->getOptions();

        // Get Framework\Utils\Math
        $utilsMath = WPStaging::make(Math::class);

        require_once "{$this->path}Pro/views/scan.php";

        wp_die();
    }

    /**
     * Fetch all tables for push process
     */
    public function ajaxPushTables()
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        if (!class_exists('WPStaging\Backend\Pro\Modules\Jobs\Scan')) {
            return false;
        }

        // Scan
        $scan = WPStaging::make(ScanProModule::class);
        $scan->loadStagingDBTables($onlyLoadStagingPrefixTables = false);
        $scan->start();
        $options = $scan->getOptions();

        $includedTables              = isset($_POST['includedTables']) ? $this->sanitize->sanitizeString($_POST['includedTables']) : '';
        $excludedTables              = isset($_POST['excludedTables']) ? $this->sanitize->sanitizeString($_POST['excludedTables']) : '';
        $selectedTablesWithoutPrefix = isset($_POST['selectedTablesWithoutPrefix']) ? $this->sanitize->sanitizeString($_POST['selectedTablesWithoutPrefix']) : '';
        $selectedTables              = new SelectedTables($includedTables, $excludedTables, $selectedTablesWithoutPrefix);
        $selectedTables->setDatabaseInfo($options->databaseServer, $options->databaseUser, $options->databasePassword, $options->databaseDatabase, empty($options->databasePrefix) ? $options->prefix : $options->databasePrefix, $options->databaseSsl);
        $tables = $selectedTables->getSelectedTables($options->networkClone);

        $templateEngine = WPStaging::make(TemplateEngine::class);

        echo json_encode([
            'success' => true,
            "content" => $templateEngine->render("/Backend/Pro/views/selections/tables.php", [
                'isNetworkClone' => $scan->isNetworkClone(),
                'options'        => $options,
                'showAll'        => true,
                'selected'       => $tables
            ])
        ]);

        exit();
    }

    /**
     * Ajax Start Pushing. Needs WP Staging Pro
     */
    public function ajaxPushProcessing()
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        if (!class_exists('WPStaging\Backend\Pro\Modules\Jobs\Processing')) {
            return false;
        }

        // Start the process
        wp_send_json(WPStaging::make(Processing::class)->start());

        return false;
    }

    /**
     * License Page
     */
    public function getLicensePage()
    {
        // Get license data
        $license = get_option('wpstg_license_status');

        require_once "{$this->path}Pro/views/licensing.php";
    }

    /**
     * Send mail via ajax
     * @param array $args
     */
    public function ajaxSendReport($args = [])
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        // Set params
        if (empty($args)) {
            $args = stripslashes_deep($_POST);
        }

        // Set e-mail
        $emailRecipient = '';
        if (isset($args['wpstg_email'])) {
            $emailRecipient = trim($this->sanitize->sanitizeString($args['wpstg_email']));
        }

        // Set hosting provider
        $providerName = '';
        if (!empty($args['wpstg_provider'])) {
            $providerName = trim($this->sanitize->sanitizeString($args['wpstg_provider']));
        }

        // Set message
        $messageBody = '';
        if (!empty($args['wpstg_message'])) {
            $messageBody = trim($this->sanitize->sanitizeString($args['wpstg_message']));
        }

        // Set syslog
        $sendLogFiles = false;
        if (isset($args['wpstg_syslog'])) {
            $sendLogFiles = $this->sanitize->sanitizeBool($args['wpstg_syslog']);
        }

        // Set terms
        $termsAccepted = false;
        if (isset($args['wpstg_terms'])) {
            $termsAccepted = $this->sanitize->sanitizeBool($args['wpstg_terms']);
        }

        // Set forceSend
        $forceSend = isset($_POST['wpstg_force_send']) && $this->sanitize->sanitizeBool($_POST['wpstg_force_send']);

        $report = WPStaging::make(Report::class);
        $errors = $report->send($emailRecipient, $messageBody, $termsAccepted, $sendLogFiles, $providerName, $forceSend);

        echo json_encode(['errors' => $errors]);
        exit;
    }

    /**
     * Connect to external database for testing correct credentials
     */
    public function ajaxDatabaseConnect()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        global $wpdb;

        $args     = $_POST;
        $user     = !empty($args['databaseUser']) ? $this->sanitize->sanitizeString($args['databaseUser']) : '';
        $password = !empty($args['databasePassword']) ? $this->sanitize->sanitizePassword($args['databasePassword']) : '';
        $database = !empty($args['databaseDatabase']) ? $this->sanitize->sanitizeString($args['databaseDatabase']) : '';
        $server   = !empty($args['databaseServer']) ? $this->sanitize->sanitizeString($args['databaseServer']) : 'localhost';
        $prefix   = !empty($args['databasePrefix']) ? $this->sanitize->sanitizeString($args['databasePrefix']) : $wpdb->prefix;
        $useSsl   = !empty($args['databaseSsl']) && 'true' === $this->sanitize->sanitizeString($args['databaseSsl']) ? true : false;

        // make sure prefix doesn't contains any invalid character
        // same condition as in WordPress wpdb::set_prefix() method
        if (preg_match('|[^a-z0-9_]|i', $prefix)) {
            echo json_encode(['success' => 'false', 'errors' => __('Table prefix contains an invalid character.', 'wp-staging')]);
            exit;
        }

        $tmpPrefixes = [
            PrepareRestore::TMP_DATABASE_PREFIX,
            PrepareRestore::TMP_DATABASE_PREFIX_TO_DROP,
        ];

        if (in_array($prefix, $tmpPrefixes)) {
            echo json_encode(['success' => 'false', 'errors' => 'Prefix wpstgtmp_ and wpstgbak_ are preserved by WP Staging and cannot be used for CLONING purpose! Please use another prefix.']);
            exit;
        }

        // ensure tables with the given prefix exist, default false
        $ensurePrefixTableExist = !empty($args['databaseEnsurePrefixTableExist']) ? $this->sanitize->sanitizeBool($args['databaseEnsurePrefixTableExist']) : false;

        $dbInfo = new DbInfo($server, $user, stripslashes($password), $database, $useSsl);
        $wpdb   = $dbInfo->connect();

        // Can not connect to mysql database
        $error = $dbInfo->getError();
        if ($error !== null) {
            echo json_encode(['success' => 'false', 'errors' => $error]);
            exit;
        }

        // Check if any table with provided prefix already exist
        $existingTables = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($prefix) . '%'));
        // used in new clone
        if ($existingTables !== null && !$ensurePrefixTableExist) {
            echo json_encode(['success' => 'false', 'errors' => __('Tables with prefix ' . $prefix . ' already exist in database. Select another prefix.', 'wp-staging')]);
            exit;
        }

        // no need to check further for new clone
        if ($existingTables === null && !$ensurePrefixTableExist) {
            echo json_encode(['success' => 'true']);
            exit;
        }

        // used in edit and update of clone
        if ($existingTables === null && $ensurePrefixTableExist) {
            echo json_encode(['success' => 'true', 'errors' => __('Tables with prefix "' . $prefix . '" not exist in database. Make sure it exists.', 'wp-staging')]);
            exit;
        }

        // get production db
        $productionDb     = WPStaging::make('wpdb');
        $productionDbInfo = new WpDbInfo($productionDb);
        $stagingDbInfo    = new WpDbInfo($wpdb);

        $stagingSiteAddress    = $stagingDbInfo->getServerIp();
        $productionSiteAddress = $productionDbInfo->getServerIp();
        if ($stagingSiteAddress === null || $productionSiteAddress === null) {
            echo json_encode(['success' => 'false', 'errors' => __('Unable to find database server hostname of the staging or the production site.', 'wp-staging')]);
            exit;
        }

        $isSameAddress = $productionSiteAddress === $stagingSiteAddress;
        $isSamePort    = $productionDbInfo->getServerPort() === $stagingDbInfo->getServerPort();

        $isSameServer = ($isSameAddress && $isSamePort) || $server === DB_HOST;

        if ($database === DB_NAME && $prefix === $productionDb->prefix && $isSameServer) {
            echo json_encode(['success' => 'false', 'errors' => __('Cannot use production site database. Use another database.', 'wp-staging')]);
            exit;
        }

        echo json_encode(['success' => 'true']);
        exit;
    }

    /**
     * Action to perform when error modal confirm button is clicked
     *
     * @todo use constants instead of hardcoded strings for error types
     */
    public function ajaxModalError()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $type = isset($_POST['type']) ? $this->sanitize->sanitizeString($_POST['type']) : null;
        if ($type === 'processLock') {
            $process = WPStaging::make(ProcessLock::class);
            $process->restart();

            exit();
        }
    }

    /**
     * Render tables and files selection for RESET function
     */
    public function ajaxCloneExcludesSettings()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $processLock = WPStaging::make(ProcessLock::class);
        $response    = $processLock->ajaxIsRunning();
        if ($response !== false) {
            echo json_encode($response);

            exit();
        }

        $templateEngine = WPStaging::make(TemplateEngine::class);

        // Scan
        $scan = WPStaging::make(Scan::class);
        $scan->setGifLoaderPath($this->assets->getAssetsUrl('img/spinner.gif'));
        $scan->start();

        echo json_encode([
            'success' => true,
            "html"    => $templateEngine->render("/Backend/views/clone/ajax/exclude-settings.php", [
                'scan'         => $scan,
                'options'      => $scan->getOptions(),
                'excludeUtils' => WPStaging::make(ExcludeFilter::class),
            ])
        ]);

        exit();
    }

    /**
     * Compare database and table properties of separate db with local db
     */
    public function ajaxDatabaseVerification()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        if (!$this->isPro()) {
            return;
        }

        $user     = !empty($_POST['databaseUser']) ? $this->sanitize->sanitizeString($_POST['databaseUser']) : '';
        $password = !empty($_POST['databasePassword']) ? $this->sanitize->sanitizePassword($_POST['databasePassword']) : '';
        $database = !empty($_POST['databaseDatabase']) ? $this->sanitize->sanitizeString($_POST['databaseDatabase']) : '';
        $server   = !empty($_POST['databaseServer']) ? $this->sanitize->sanitizeString($_POST['databaseServer']) : 'localhost';
        $useSsl   = !empty($_POST['databaseSsl']) && 'true' === $this->sanitize->sanitizeString($_POST['databaseSsl']) ? true : false;

        $comparison = new CompareExternalDatabase($server, $user, stripslashes($password), $database, $useSsl);
        $results    = $comparison->maybeGetComparison();

        echo json_encode($results);
        exit();
    }

    /**
     * Enable cloning on staging site if it is not enabled already
     */
    public function ajaxEnableStagingCloning()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        if ($this->siteInfo->enableStagingSiteCloning()) {
            echo json_encode(['success' => 'true']);
            exit();
        }

        echo json_encode(['success' => 'false', 'message' => __('Unable to enable cloning in the staging site', 'wp-staging')]);
        exit();
    }

    /**
     * Restore Settings, can be used when settings are corrupted 
     */
    public function ajaxRestoreSettings()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        // Delete old settings
        delete_option('wpstg_settings');
        $settings = WPStaging::make(Settings::class);
        $settings->setDefault();
    }

    /**
     * Remove uploads backup
     */
    public function removeOldUploadsBackup()
    {
        $backup = new BackupUploadsDir(null);
        $backup->removeUploadsBackup();
    }

    /**
     * Check if Plugin is Pro version
     * @return bool
     */
    protected function isPro()
    {
        if (!defined("WPSTGPRO_VERSION")) {
            return false;
        }

        return true;
    }

    /**
     * Check if current page is plugins.php
     * @global array $pagenow
     * @return bool
     */
    private function isPluginsPage()
    {
        global $pagenow;
        return ($pagenow === 'plugins.php');
    }
}
