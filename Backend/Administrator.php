<?php

namespace WPStaging\Backend;

use WPStaging\Core\WPStaging;
use WPStaging\Core\DTO\Settings;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\Database\DbInfo;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\Nonce;
use WPStaging\Framework\Mails\Report\Report;
use WPStaging\Framework\Filesystem\Filters\ExcludeFilter;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\CloningProcess\Database\CompareExternalDatabase;
use WPStaging\Framework\Utils\Math;
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
use WPStaging\Backend\Notices\Notices;
use WPStaging\Backend\Notices\DisabledItemsNotice;
use WPStaging\Backend\Modules\Views\Forms\Settings as FormSettings;
use WPStaging\Backend\Activation;
use WPStaging\Backend\Feedback;
use WPStaging\Backend\Pro\Notices\BackupsDifferentPrefixNotice;
use WPStaging\Backend\Pro\Modules\Jobs\Processing;
use WPStaging\Backend\Pro\Modules\Jobs\Backups\BackupUploadsDir;
use WPStaging\Backend\Pluginmeta\Pluginmeta;
use WPStaging\Framework\Staging\Sites;
use WPStaging\Framework\Support\ThirdParty\WordFence;

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

    /**
     * @var array
     * All options here will only be stored in the database as integers. Decimal points and separators will be removed
     */
    private static $integerOptions = [
        'queryLimit',
        'querySRLimit'
    ];

    public function __construct()
    {
        // TODO: Inject using DI
        $this->auth = new Auth(new Capabilities(), new AccessToken(), new Nonce());
        // TODO: Inject using DI
        $this->assets = new Assets(new AccessToken(), new Settings());

        // Todo: Inject using DI
        $this->siteInfo = new SiteInfo();

        $this->defineHooks();

        // Path to backend
        $this->path = plugin_dir_path(__FILE__);

        // Load plugins meta data
        $this->loadMeta();
    }

    /**
     * Load plugn meta data
     */
    public function loadMeta()
    {
        $run = new Pluginmeta();
    }

    /**
     * Define Hooks
     */
    private function defineHooks()
    {
        if (!defined('WPSTGPRO_VERSION')) {
            $Welcome = new Activation\Welcome();
        }

        add_action("admin_menu", [$this, "addMenu"], 10);
        add_action("admin_init", [$this, "setOptionFormElements"]);
        add_action("admin_init", [$this, "upgrade"]);
        add_action("admin_post_wpstg_download_sysinfo", [$this, "systemInfoDownload"]);
        add_action("admin_post_wpstg_export", [$this, "export"]);
        add_action("admin_post_wpstg_import_settings", [$this, "import"]);
        add_action("admin_notices", [$this, "messages"]);

        if (!defined('WPSTGPRO_VERSION')) {
            add_filter('admin_footer', [$this, 'loadFeedbackForm']);
        }

        // Ajax Requests
        add_action("wp_ajax_wpstg_overview", [$this, "ajaxOverview"]);
        add_action("wp_ajax_wpstg_scanning", [$this, "ajaxCloneScan"]);
        add_action("wp_ajax_wpstg_check_clone", [$this, "ajaxCheckCloneDirectoryName"]);
        add_action("wp_ajax_wpstg_restart", [$this, "ajaxRestart"]);
        add_action("wp_ajax_wpstg_update", [$this, "ajaxUpdateProcess"]);
        add_action("wp_ajax_wpstg_reset", [$this, "ajaxResetProcess"]);
        add_action("wp_ajax_wpstg_cloning", [$this, "ajaxStartClone"]);
        add_action("wp_ajax_wpstg_processing", [$this, "ajaxCloneDatabase"]);
        add_action("wp_ajax_wpstg_database_connect", [$this, "ajaxDatabaseConnect"]);
        add_action("wp_ajax_wpstg_database_verification", [$this, "ajaxDatabaseVerification"]);
        add_action("wp_ajax_wpstg_clone_prepare_directories", [$this, "ajaxPrepareDirectories"]);
        add_action("wp_ajax_wpstg_clone_files", [$this, "ajaxCopyFiles"]);
        add_action("wp_ajax_wpstg_clone_replace_data", [$this, "ajaxReplaceData"]);
        add_action("wp_ajax_wpstg_clone_finish", [$this, "ajaxFinish"]);
        add_action("wp_ajax_wpstg_confirm_delete_clone", [$this, "ajaxDeleteConfirmation"]);
        add_action("wp_ajax_wpstg_delete_clone", [$this, "ajaxDeleteClone"]);
        add_action("wp_ajax_wpstg_cancel_clone", [$this, "ajaxCancelClone"]);
        add_action("wp_ajax_wpstg_cancel_update", [$this, "ajaxCancelUpdate"]);
        add_action("wp_ajax_wpstg_hide_poll", [$this, "ajaxHidePoll"]);
        add_action("wp_ajax_wpstg_hide_rating", [$this, "ajaxHideRating"]);
        add_action("wp_ajax_wpstg_hide_later", [$this, "ajaxHideLaterRating"]);
        add_action("wp_ajax_wpstg_hide_beta", [$this, "ajaxHideBeta"]);
        add_action("wp_ajax_wpstg_logs", [$this, "ajaxLogs"]);
        add_action("wp_ajax_wpstg_check_disk_space", [$this, "ajaxCheckFreeSpace"]);
        add_action("wp_ajax_wpstg_send_report", [$this, "ajaxSendReport"]);
        add_action("wp_ajax_wpstg_send_feedback", [$this, "sendFeedback"]);
        add_action("wp_ajax_wpstg_enable_staging_cloning", [$this, "ajaxEnableStagingCloning"]);
        add_action("wp_ajax_wpstg_clone_excludes_settings", [$this, "ajaxCloneExcludesSettings"]);
        add_action("wp_ajax_wpstg_fetch_dir_childrens", [$this, "ajaxFetchDirChildrens"]);
        add_action("wp_ajax_wpstg_modal_error", [$this, "ajaxModalError"]);
        add_action("wp_ajax_wpstg_dismiss_notice", [$this, "ajaxDismissNotice"]);


        // Ajax hooks pro Version
        // TODO: move all below actions to pro service provider?
        add_action("wp_ajax_wpstg_edit_clone_data", [$this, "ajaxEditCloneData"]);
        add_action("wp_ajax_wpstg_save_clone_data", [$this, "ajaxSaveCloneData"]);
        add_action("wp_ajax_wpstg_scan", [$this, "ajaxPushScan"]);
        add_action("wp_ajax_wpstg_push_processing", [$this, "ajaxPushProcessing"]);
        add_action("wp_ajax_nopriv_wpstg_push_processing", [$this, "ajaxPushProcessing"]);

        // TODO: replace uploads backup during push once we have backups PR ready,
        // Then there will be no need to have any cron to delete those backups
        if (class_exists('WPStaging\Backend\Pro\Modules\Jobs\Backups\BackupUploadsDir')) {
            add_action(BackupUploadsDir::BACKUP_DELETE_CRON_HOOK_NAME, [$this, "removeOldUploadsBackup"]);
        }
    }

    /**
     * Load Feedback Form on plugins.php
     */
    public function loadFeedbackForm()
    {
        $form = new Feedback\Feedback();
        $load = $form->loadForm();
    }

    /**
     * Send Feedback form via mail
     */
    public function sendFeedback()
    {
        $form = new Feedback\Feedback();
        $send = $form->sendMail();
    }

    /**
     * Register options form elements
     */
    public function setOptionFormElements()
    {
        register_setting("wpstg_settings", "wpstg_settings", [$this, "sanitizeOptions"]);
    }

    /**
     * Upgrade routine
     * @action admin_init 10 0
     * @see \WPStaging\Backend\Administrator::defineHooks
     */
    public function upgrade()
    {
        if (defined('WPSTGPRO_VERSION') && class_exists('WPStaging\Backend\Pro\Upgrade\Upgrade')) {
            $upgrade = new Pro\Upgrade\Upgrade();
        } else {
            $upgrade = new Upgrade\Upgrade();
        }
        $upgrade->doUpgrade();
    }

    /**
     * Sanitize options data and delete the cache
     * @param array $data
     * @return array
     */
    public function sanitizeOptions($data = [])
    {
        $error = false;
        // is_array() is required otherwise new clone will fail.
        if ($this->siteInfo->isStaging() && is_array($data)) {
            $isStagingCloneable = isset($data['isStagingSiteCloneable']) ? $data['isStagingSiteCloneable'] : 'false';
            unset($data['isStagingSiteCloneable']);
            $error = !$this->toggleStagingSiteCloning($isStagingCloneable === 'true');
        }

        $sanitized = $this->sanitizeData($data);

        if ($error) {
            add_settings_error("wpstg-notices", '', __("Settings updated. But unable to toggle cloning feature!", "wp-staging"), "warning");
        } else {
            add_settings_error("wpstg-notices", '', __("Settings updated.", "wp-staging"), "updated");
        }

        return apply_filters("wpstg-settings", $sanitized, $data);
    }

    /**
     * @param array $data
     * @return array
     */
    private function sanitizeData($data = [])
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } //Removing comma separators and decimal points
            elseif (in_array($key, self::$integerOptions, true)) {
                $sanitized[$key] = preg_replace('/\D/', '', htmlspecialchars($value));
            } else {
                $sanitized[$key] = htmlspecialchars($value);
            }
        }

        return $sanitized;
    }

    /**
     * Add Admin Menu(s)
     */
    public function addMenu()
    {

        $logo = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCFET0NUWVBFIHN2ZyBQVUJMSUMgIi0vL1czQy8vRFREIFNWRyAxLjEvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkIj4KPHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgMTAwMCAxMDAwIiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAxMDAwIDEwMDAiIHhtbDpzcGFjZT0icHJlc2VydmUiIGZpbGw9Im5vbmUiPgo8Zz48Zz48cGF0aCBzdHlsZT0iZmlsbDojZmZmIiAgZD0iTTEzNy42LDU2MS4zSDEzLjhIMTB2MzA2LjNsOTAuNy04My40QzE4OS42LDkwOC43LDMzNS4zLDk5MCw1MDAsOTkwYzI0OS45LDAsNDU2LjEtMTg3LjEsNDg2LjItNDI4LjhIODYyLjRDODMzLjMsNzM1LjEsNjgyLjEsODY3LjUsNTAwLDg2Ny41Yy0xMjksMC0yNDIuNS02Ni41LTMwOC4xLTE2Ny4ybDE1MS4zLTEzOS4xSDEzNy42eiIvPjxwYXRoIHN0eWxlPSJmaWxsOiNmZmYiICBkPSJNNTAwLDEwQzI1MC4xLDEwLDQzLjksMTk3LjEsMTMuOCw0MzguOGgxMjMuOEMxNjYuNywyNjQuOSwzMTcuOSwxMzIuNSw1MDAsMTMyLjVjMTMyLjksMCwyNDkuMyw3MC41LDMxMy44LDE3Ni4yTDY4My44LDQzOC44aDEyMi41aDU2LjJoMTIzLjhoMy44VjEzMi41bC04Ny43LDg3LjdDODEzLjgsOTMuMSw2NjYuNiwxMCw1MDAsMTB6Ii8+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjxnPjwvZz48Zz48L2c+PGc+PC9nPjwvZz4KPC9zdmc+';

        $pos = self::MENU_POSITION_ORDER;
        if (is_multisite()) {
            $pos = self::MENU_POSITION_ORDER_MULTISITE;
        }

        $proSlug = defined('WPSTGPRO_VERSION') ? 'Pro' : '';

        // Main WP Staging Menu
        add_menu_page(
            "WP STAGING",
            __("WP Staging " . $proSlug, "wp-staging"),
            "manage_options",
            "wpstg_clone",
            [$this, "getClonePage"],
            $logo,
            $pos
        );

        // Page: Clone
        add_submenu_page(
            "wpstg_clone",
            __("WP Staging Jobs", "wp-staging"),
            __("Staging Sites", "wp-staging"),
            "manage_options",
            "wpstg_clone",
            [$this, "getClonePage"]
        );

        add_submenu_page(
            "wpstg_clone",
            __("WP Staging Jobs", "wp-staging"),
            __("Backup & Migration", "wp-staging"),
            "manage_options",
            "wpstg_backup",
            [$this, "getBackupPage"]
        );

        // Page: Settings
        add_submenu_page(
            "wpstg_clone",
            __("WP Staging Settings", "wp-staging"),
            __("Settings", "wp-staging"),
            "manage_options",
            "wpstg-settings",
            [$this, "getSettingsPage"]
        );

        // Page: Tools
        add_submenu_page(
            "wpstg_clone",
            __("WP Staging Tools", "wp-staging"),
            __("Tools", "wp-staging"),
            "manage_options",
            "wpstg-tools",
            [$this, "getToolsPage"]
        );

        if (!defined('WPSTGPRO_VERSION')) {
            // Page: Tools
            add_submenu_page(
                "wpstg_clone",
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
                "wpstg_clone",
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
        // Existing clones
        $availableClones = get_option(Sites::STAGING_SITES_OPTION, []);

        require_once "{$this->path}views/clone/index.php";
    }

    /**
     * Backup & Migration Page
     */
    public function getBackupPage()
    {
        // Existing clones
        $availableClones = get_option(Sites::STAGING_SITES_OPTION, []);

        $openBackupPage = true;

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
            "import_export" => __("Import/Export", "wp-staging"),
            "system_info" => __("System Info", "wp-staging")
        ]);

        WPStaging::getInstance()->set("tabs", $tabs);

        WPStaging::getInstance()->set("systemInfo", new SystemInfo());

        require_once "{$this->path}views/tools/index.php";
    }

    /**
     * System Information Download
     */
    public function systemInfoDownload()
    {
        if (!current_user_can("update_plugins")) {
            return;
        }

        nocache_headers();
        header("Content-Type: text/plain");
        header('Content-Disposition: attachment; filename="wpstg-system-info.txt"');
        echo wp_strip_all_tags(new SystemInfo());
    }

    /**
     * Import JSON settings file
     */
    public function import()
    {
        if (empty($_POST["wpstg_import_nonce"])) {
            return;
        }

        if (!wp_verify_nonce($_POST["wpstg_import_nonce"], "wpstg_import_nonce")) {
            return;
        }

        if (!current_user_can("update_plugins")) {
            return;
        }

        $fileExtension = explode('.', $_FILES["import_file"]["name"]);
        $fileExtension = end($fileExtension);
        if ($fileExtension !== "json") {
            wp_die("Please upload a valid .json file", "wp-staging");
        }


        $importFile = $_FILES["import_file"]["tmp_name"];

        if (empty($importFile)) {
            wp_die(__("Please upload a file to import", "wp-staging"));
        }

        update_option("wpstg_settings", json_decode(file_get_contents($importFile, true)));

        wp_safe_redirect(admin_url("admin.php?page=wpstg-tools&amp;wpstg-message=settings-imported"));

        return;
    }

    /**
     * Export settings to JSON file
     */
    public function export()
    {
        if (empty($_POST["wpstg_export_nonce"])) {
            return;
        }

        if (!wp_verify_nonce($_POST["wpstg_export_nonce"], "wpstg_export_nonce")) {
            return;
        }

        if (!current_user_can("manage_options")) {
            return;
        }

        $settings = get_option("wpstg_settings", []);

        ignore_user_abort(true);

        // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
        if (!in_array("set_time_limit", explode(',', ini_get("disable_functions")))) {
            set_time_limit(0);
        }

        $fileName = apply_filters("wpstg_settings_export_filename", "wpstg-settings-export-" . date("m-d-Y")) . ".json";

        nocache_headers();
        header("Content-Type: application/json; charset=utf-8");
        header("Content-Disposition: attachment; filename={$fileName}");
        header("Expires: 0");

        echo json_encode($settings);
    }

    /**
     * Render a view file
     * @param string $file
     * @param array $vars
     * @return string
     */
    public function render($file, $vars = [])
    {
        $fullPath = $this->path . "views" . DIRECTORY_SEPARATOR;
        $fullPath = str_replace(['/', "\\"], DIRECTORY_SEPARATOR, $fullPath . $file . ".php");

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
    private function isAuthenticated()
    {
        return $this->auth->isAuthenticatedRequest();
    }

    /**
     * Restart cloning process
     */
    public function ajaxRestart()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $process = new ProcessLock();
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

        // Existing clones
        $availableClones = (new Sites())->getSortedStagingSites();

        // Get license data
        $license = get_option('wpstg_license_status');

        // Get db
        $db = WPStaging::getInstance()->get('wpdb');

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
        $response = (new ProcessLock())->ajaxIsRunning(); 
        if ($response !== false)
        {
            echo $response;

            exit();
        }

        $db = WPStaging::getInstance()->get('wpdb');

        // Scan
        $scan = new Scan();
        $scan->setGifLoaderPath($this->assets->getAssetsUrl('img/spinner.gif'));
        $scan->setInfoIcon($this->assets->getAssetsUrl('svg/vendor/dashicons/info-outline.svg'));
        $scan->start();

        // Get Options
        $options = $scan->getOptions();
        $excludeUtils = new ExcludeFilter();
        require_once "{$this->path}views/clone/ajax/scan.php";

        wp_die();
    }

    /**
     * Fetch children of the given directory
     */
    public function ajaxFetchDirChildrens()
    {
        if (!$this->isAuthenticated()) {
            wp_send_json(['success' => false]);
            return;
        }

        $isChecked = isset($_POST['isChecked']) ? $_POST['isChecked'] === 'true' : false;
        $forceDefault = isset($_POST['forceDefault']) ? $_POST['forceDefault'] === 'true' : false;
        $path = isset($_POST['dirPath']) ? $_POST['dirPath'] : "";
        $path = ABSPATH . $path;
        $scan = new Scan($path);
        $scan->setGifLoaderPath($this->assets->getAssetsUrl('img/spinner.gif'));
        wp_send_json([
            "success" => true,
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

        $cloneDirectoryName = sanitize_key($_POST["directoryName"]);
        $cloneDirectoryNameLength = strlen($cloneDirectoryName);
        $existingClones = get_option(Sites::STAGING_SITES_OPTION, []);

        $cloneDestDir = trailingslashit(get_home_path()) . $cloneDirectoryName;

        // Check clone directory name length
        if ($cloneDirectoryNameLength < 1 || $cloneDirectoryNameLength > 16) {
            echo wp_send_json([
                "status" => "failed",
                "message" => "Choose a site name below 16 characters"
            ]);

            return;
        }
        
        // Check if destination clone dir exists and that it is not empty
        if (!wpstg_is_empty_dir($cloneDestDir)) {
            echo wp_send_json([
                "status" => "failed",
                "message" => "Warning: Use another site name! Clone destination directory " . $cloneDestDir . " already exists and is not empty. As default, WP STAGING uses the site name as subdirectory for the clone."
            ]);

            return;
        }

        foreach ($existingClones as $clone) {
            if ($clone['directoryName'] === $cloneDirectoryName) {
                echo wp_send_json([
                    "status" => "failed",
                    "message" => "Site name is already in use, please choose another name for the staging site."
                ]);

                return;
            }
        }

        echo wp_send_json(["status" => "success"]);
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
        $cloning->setMainJob(Updating::RESET_UPDATE);
        if (!$cloning->save()) {
            wp_die('can not save clone data');
        }

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
        $processLock = new ProcessLock();
        $processLock->isRunning();

        $cloning = WPStaging::make(Cloning::class);

        if (!$cloning->save()) {
            wp_die('can not save clone data');
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

        $delete = new Delete();

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

        wp_send_json((new Delete())->start());
    }

    /**
     * Delete clone
     */
    public function ajaxCancelClone()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        wp_send_json((new Cancel())->start());
    }

    /**
     * Cancel updating process / Do not delete clone!
     */
    public function ajaxCancelUpdate()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        wp_send_json((new CancelUpdate())->start());
    }

    /**
     * Admin Messages
     */
    public function messages()
    {
        (new Notices($this->path, $this->assets))->messages();
    }

    /**
     * Ajax Hide Poll
     * @todo check if this is being used, remove otherwise.
     */
    public function ajaxHidePoll()
    {
        if (update_option("wpstg_poll", "no") !== false) {
            wp_send_json(true);
        }

        wp_send_json(null);
    }

    /**
     * Ajax Hide Rating
     *
     * Runs when the user dismisses the notice to rate the plugin.
     */
    public function ajaxHideRating()
    {
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
        wp_send_json(update_option("wpstg_beta", "no"));
    }

    public function ajaxDismissNotice()
    {
        // Early bail if no notice option available
        if (!isset($_POST['wpstg_notice'])) {
            wp_send_json(null);
            return;
        }

        // Dismiss backups prefix notice
        if ($_POST['wpstg_notice'] === 'backups_diff_prefix' && (new BackupsDifferentPrefixNotice())->disable() !== false) {
            wp_send_json(true);
            return;
        }

        // Dismiss disabled item notice
        if ($_POST['wpstg_notice'] === 'disabled_items' && (new DisabledItemsNotice())->disable() !== false) {
            wp_send_json(true);
            return;
        }

        // Dismiss wordfence user.ini renamed notice
        if ($_POST['wpstg_notice'] === WordFence::NOTICE_NAME && (new WordFence())->disable() !== false) {
            wp_send_json(true);
            return;
        }

        wp_send_json(null);
    }

    /**
     * Clone logs
     */
    public function ajaxLogs()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        wp_send_json((new Logs())->start());
    }

    /**
     * Ajax Checks Free Disk Space
     */
    public function ajaxCheckFreeSpace()
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $excludedDirectories = isset($_POST["excludedDirectories"]) ? $_POST["excludedDirectories"] : '';
        $extraDirectories = isset($_POST["extraDirectories"]) ? $_POST["extraDirectories"] : '';

        $scan = new Scan();
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
            $clone = $existingClones[$_POST["clone"]];
            require_once "{$this->path}Pro/views/edit-clone-data.php";
        } else {
            echo __("Unknown error. Please reload the page and try again", "wp-staging");
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
                echo __("Site name is required!");
                wp_die();
            }

            $cloneId      = $_POST["clone"];
            $cloneName    = wpstg_urldecode($_POST["cloneName"]);
            $cloneDirectoryName = wpstg_urldecode($_POST["directoryName"]);
            $cloneDirectoryName = preg_replace("#\W+#", '-', strtolower($cloneDirectoryName));

            $existingClones[$cloneId]["cloneName"] = stripslashes($cloneName);
            $existingClones[$cloneId]["directoryName"] = stripslashes($cloneDirectoryName);
            $existingClones[$cloneId]["path"] = stripslashes($_POST["path"]);
            $existingClones[$cloneId]["url"] = stripslashes($_POST["url"]);
            $existingClones[$cloneId]["prefix"] = stripslashes($_POST["prefix"]);
            $existingClones[$cloneId]["databaseUser"] = stripslashes($_POST["externalDBUser"]);
            $existingClones[$cloneId]["databasePassword"] = stripslashes($_POST["externalDBPassword"]);
            $existingClones[$cloneId]["databaseDatabase"] = stripslashes($_POST["externalDBDatabase"]);
            $existingClones[$cloneId]["databaseServer"] = stripslashes($_POST["externalDBHost"]);
            $existingClones[$cloneId]["databasePrefix"] = stripslashes($_POST["externalDBPrefix"]);

            update_option(Sites::STAGING_SITES_OPTION, $existingClones);

            echo __("Success");
        } else {
            echo __("Unknown error. Please reload the page and try again", "wp-staging");
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
        $scan = new Pro\Modules\Jobs\Scan();

        $scan->start();

        // Get Options
        $options = $scan->getOptions();

        // Get Framework\Utils\Math
        $utilsMath = new Math();

        require_once "{$this->path}Pro/views/scan.php";

        wp_die();
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
        $email = null;
        if (isset($args['wpstg_email'])) {
            $email = trim($args['wpstg_email']);
        }

        // Set hosting provider
        $provider = null;
        if (isset($args['wpstg_provider'])) {
            $provider = trim($args['wpstg_provider']);
        }

        // Set message
        $message = null;
        if (isset($args['wpstg_message'])) {
            $message = trim($args['wpstg_message']);
        }

        // Set syslog
        $syslog = false;
        if (isset($args['wpstg_syslog'])) {
            $syslog = (bool)$args['wpstg_syslog'];
        }

        // Set terms
        $terms = false;
        if (isset($args['wpstg_terms'])) {
            $terms = (bool)$args['wpstg_terms'];
        }

        // Set forceSend
        $forceSend = isset($_POST['wpstg_force_send']) && $_POST['wpstg_force_send'] !== "false";

        $report = new Report();
        $errors = $report->send($email, $message, $terms, $syslog, $provider, $forceSend);

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

        $args = $_POST;
        $user = !empty($args['databaseUser']) ? $args['databaseUser'] : '';
        $password = !empty($args['databasePassword']) ? $args['databasePassword'] : '';
        $database = !empty($args['databaseDatabase']) ? $args['databaseDatabase'] : '';
        $server = !empty($args['databaseServer']) ? $args['databaseServer'] : 'localhost';
        $prefix = !empty($args['databasePrefix']) ? $args['databasePrefix'] : 'wp_';
        // make sure prefix doesn't contains any invalid character
        // same condition as in WordPress wpdb::set_prefix() method
        if (preg_match('|[^a-z0-9_]|i', $prefix)) {
            echo json_encode(['success' => 'false', 'errors' => __('Table prefix contains an invalid character.', 'wp-staging')]);
            exit;
        }

        // ensure tables with the given prefix exist, default false
        $ensurePrefixTableExist = !empty($args['databaseEnsurePrefixTableExist']) ? filter_var($args['databaseEnsurePrefixTableExist'], FILTER_VALIDATE_BOOLEAN) : false;

        $dbInfo = new DbInfo($server, $user, stripslashes($password), $database);
        $wpdb = $dbInfo->connect();
        $error = $dbInfo->getError();

        // Can not connect to mysql database
        if ($error !== null) {
            echo json_encode(['success' => 'false', 'errors' => $error]);
            exit;
        }

        // Check if any table with provided prefix already exist
        $existingTables = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prefix . '%'));
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
        $productionDb = WPStaging::getInstance()->get('wpdb');

        $queryToFindHost = "SHOW VARIABLES WHERE Variable_name = 'hostname';";
        $queryToFindPort = "SHOW VARIABLES WHERE Variable_name = 'port';";

        $stagingSiteAddress = gethostbyname($wpdb->get_var($wpdb->prepare($queryToFindHost), 1));
        $productionSiteAddress = gethostbyname($productionDb->get_var($productionDb->prepare($queryToFindHost), 1));
        if ($stagingSiteAddress === null || $productionSiteAddress === null) {
            echo json_encode(['success' => 'false', 'errors' => __('Unable to find database server hostname of the staging or the production site.', 'wp-staging')]);
            exit;
        }

        $isSameAddress = $productionSiteAddress === $stagingSiteAddress;
        $isSamePort = $wpdb->get_var($wpdb->prepare($queryToFindPort), 1) === $productionDb->get_var($productionDb->prepare($queryToFindPort), 1);

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

        $type = isset($_POST['type']) ? $_POST['type'] : null;
        if ($type === 'processLock') {
            $process = new ProcessLock();
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

        $response = (new ProcessLock())->ajaxIsRunning(); 
        if ($response !== false)
        {
            echo $response;

            exit();
        }

        $templateEngine = new TemplateEngine();

        // Scan
        $scan = new Scan();
        $scan->setGifLoaderPath($this->assets->getAssetsUrl('img/spinner.gif'));
        $scan->start();

        echo json_encode([
            'success' => true,
            "html" => $templateEngine->render("/Backend/views/clone/ajax/exclude-settings.php", [
                'scan' => $scan,
                'options' => $scan->getOptions(),
                'excludeUtils' => new ExcludeFilter(),
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

        $user = !empty($_POST['databaseUser']) ? $_POST['databaseUser'] : '';
        $password = !empty($_POST['databasePassword']) ? $_POST['databasePassword'] : '';
        $database = !empty($_POST['databaseDatabase']) ? $_POST['databaseDatabase'] : '';
        $server = !empty($_POST['databaseServer']) ? $_POST['databaseServer'] : 'localhost';

        $comparison = new CompareExternalDatabase($server, $user, stripslashes($password), $database);
        $results = $comparison->maybeGetComparison();

        echo json_encode($results);
        exit();
    }

    /**
     * Enable cloning on staging site if it is not enabled already
     */
    public function ajaxEnableStagingCloning()
    {
        if ($this->siteInfo->enableStagingSiteCloning()) {
            echo json_encode(['success' => 'true']);
            exit();
        }

        echo json_encode(['success' => 'false', 'message' => __('Unable to enable cloning in the staging site', 'wp-staging')]);
        exit();
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
     * Toggle staging site cloning
     *
     * @param bool $isCloneable
     * 
     * @return bool
     */
    protected function toggleStagingSiteCloning($isCloneable)
    {
        if ($isCloneable && $this->siteInfo->enableStagingSiteCloning()) {
            return true;
        }

        if (!$isCloneable && $this->siteInfo->disableStagingSiteCloning()) {
            return true;
        }

        return false;
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
}
