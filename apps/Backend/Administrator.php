<?php
namespace WPStaging\Backend;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

use WPStaging\Backend\Modules\Jobs\Data;
use WPStaging\Backend\Modules\Jobs\Database;
use WPStaging\Backend\Modules\Jobs\Files;
use WPStaging\Backend\Modules\Jobs\Scan;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Backend\Modules\Views\Tabs\Tabs;
use WPStaging\DI\InjectionAware;
use \WPStaging\Backend\Modules\Views\Forms\Settings as FormSettings;

/**
 * Class Administrator
 * @package WPStaging\Backend
 */
class Administrator extends InjectionAware
{

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $url;

    /**
     * Initialize class
     */
    public function initialize()
    {
        $this->loadDependencies();
        $this->defineHooks();

        // Path to backend
        $this->path = plugin_dir_path(__FILE__);
        $this->url  = plugin_dir_url(__FILE__) . "public/";
    }

    /**
     * Load Dependencies
     */
    private function loadDependencies()
    {
        // Set loader
        $this->di
            // Set loader
            ->set("data", new Data())
            // Set cache
            ->set("database", new Database())
            // Set logger
            ->set("files", new Files());
    }

    /**
     * Define Hooks
     */
    private function defineHooks()
    {
        // Get loader
        $loader = $this->di->get("loader");

        $loader->addAction("admin_enqueue_scripts", $this, "enqueueElements", 100);
        $loader->addAction("admin_menu", $this, "addMenu", 10);
        $loader->addAction("admin_init", $this, "setOptionFormElements");
        $loader->addAction("wp_ajax_wpstg_scanning", $this, "ajaxScan");
        $loader->addAction("wpstg_download_sysinfo", $this, "systemInfoDownload");
    }

    /**
     * Register options form elements
     */
    public function setOptionFormElements()
    {
        register_setting("wpstg_settings", "wpstg_settings", [$this, "sanitizeOptions"]);
    }

    /**
     * Sanitize options data and delete the cache
     * @param array $data
     * @return array
     */
    public function sanitizeOptions($data = array())
    {
        $sanitized = array();

        foreach ($data as $key => $value)
        {
            $sanitized[$key] = htmlspecialchars($value);
        }

        // TODO sanitization!
        add_settings_error("wpstg-notices", '', __("Settings updated.", "wpstg"), "updated");

        // Return sanitized data
        //return $sanitized;
        return apply_filters("wpstg-settings", $sanitized, $data);
    }

    /**
     * Add Admin Menu(s)
     */
    public function addMenu()
    {
        // Main WP Staging Menu
        add_menu_page(
            "WP-Staging",
            __("WP Staging", "wpstg"),
            "manage_options",
            "wpstg_clone",
            [$this, "getClonePage"],
            "dashicons-hammer"
        );

        // Page: Clone
        add_submenu_page(
            "wpstg_clone",
            __("WP Staging Jobs", "wpstg"),
            __("Start", "wpstg"),
            "manage_options",
            "wpstg_clone",
            [$this, "getClonePage"]
        );

        // Page: Settings
        add_submenu_page(
            "wpstg_clone",
            __("WP Staging Settings", "wpstg"),
            __("Settings", "wpstg"),
            "manage_options",
            "wpstg-settings",
            [$this, "getSettingsPage"]
        );

        // Page: Tools
        add_submenu_page(
            "wpstg_clone",
            __("WP Staging Tools", "wpstg"),
            __("Tools", "wpstg"),
            "manage_options",
            "wpstg-tools",
            [$this, "getToolsPage"]
        );
    }

    /**
     * Settings Page
     */
    public function getSettingsPage()
    {
        // Tabs
        $tabs = new Tabs(array(
            "general" => __("General", "wpstg")
        ));


        $this->di
            // Set tabs
            ->set("tabs", $tabs)
            // Forms
            ->set("forms", new FormSettings($tabs));

        require_once "{$this->path}views/settings/index.php";
    }

    /**
     * Clone Page
     */
    public function getClonePage()
    {
        require_once "{$this->path}views/clone/index.php";
    }

    /**
     * Tools Page
     */
    public function getToolsPage()
    {
        // Tabs
        $tabs = new Tabs(array(
            "import_export" => __("Import/Export", "wpstg"),
            "system_info"   => __("System Info", "wpstg")
        ));

        $this->di->set("tabs", $tabs);

        $this->di->set("systemInfo", new SystemInfo($this->di));

        require_once "{$this->path}views/tools/index.php";
    }

    // TODO connect??
    /**
     * System Information Download
     */
    public function systemInfoDownload()
    {
        if (!current_user_can("update_plugins"))
        {
            return;
        }

        nocache_headers();

        header("Content-Type: text/plain");
        header("Content-Disposition: attachment; filename='wpstg-system-info.txt'");

        echo wp_strip_all_tags(new SystemInfo($this->di));
    }

    // TODO connect??
    /**
     * Import JSON settings file
     */
    public function import()
    {
        if (empty($_POST["wpstg_import_nonce"]))
        {
            return;
        }

        if (!wp_verify_nonce($_POST["wpstg_import_nonce"], "wpstg_import_nonce"))
        {
            return;
        }

        if (!current_user_can("update_plugins"))
        {
            return;
        }

        $fileExtension = explode('.', $_FILES["import_file"]["name"]);
        $fileExtension = end($fileExtension);
        if ("json" !== $fileExtension)
        {
            wp_die("Please upload a valid .json file", "wpstg");
        }


        $importFile = $_FILES["import_file"]["tmp_name"];

        if (empty($importFile))
        {
            wp_die(__("Please upload a file to import", "wpstg"));
        }

        update_option("wpstg_settings", json_decode(file_get_contents($importFile, true)));

        wp_safe_redirect(admin_url("admin.php?page=wpstg-tools&amp;wpstg-message=settings-imported"));

        return;
    }

    // TODO connect??
    /**
     * Export settings to JSON file
     */
    public function export()
    {
        if (empty($_POST["wpstg_export_nonce"]))
        {
            return;
        }

        if (!wp_verify_nonce($_POST["wpstg_export_nonce"], "wpstg_export_nonce"))
        {
            return;
        }

        if (!current_user_can("manage_options"))
        {
            return;
        }

        $settings = get_option("wpstg_settings", array());

        ignore_user_abort(true);

        if (!in_array("set_time_limit", explode(',', ini_get("disable_functions"))) && !ini_get("safe_mode"))
        {
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
     * Scripts and Styles
     * @param string $hooke
     */
    public function enqueueElements($hook)
    {
        //$suffix = isset($wpstg_options['debug_mode']) ? '.min' : '';
        $suffix = '';

        wp_enqueue_script(
            "wpstg-admin-script",
            $this->url . "js/wpstg-admin" . $suffix . ".js",
            array("jquery"),
            $this->di->getVersion(),
            false
        );

        wp_enqueue_style(
            'wpstg-admin',
            $this->url . "css/wpstg-admin" . $suffix . ".css",
            $this->di->getVersion()
        );

        wp_localize_script("wpstg-admin-script", "wpstg", array(
            "nonce"                                 => wp_create_nonce("wpstg_ajax_nonce"),
            "mu_plugin_confirmation"                => __(
                "If confirmed we will install an additional WordPress 'Must Use' plugin. "
                . "This plugin will allow us to control which plugins are loaded during "
                . "WP Staging specific operations. Do you wish to continue?",
                "wpstg"
            ),
            "plugin_compatibility_settings_problem" => __(
                "A problem occurred when trying to change the plugin compatibility setting.",
                "wpstg"
            ),
            "saved"                                 => __("Saved", "The settings were saved successfully", "wpstg"),
            "status"                                => __("Status", "Current request status", "wpstg"),
            "response"                              => __("Response", "The message the server responded with", "wpstg"),
            "blacklist_problem"                     => __(
                "A problem occurred when trying to add plugins to backlist.",
                "wpstg"
            ),
            "cpu_load"                              => $this->di->getCPULoadSetting(),
        ));
    }

    /**
     * Render a view file
     * @param string $file
     * @param array $vars
     * @return string
     */
    public function render($file, $vars = array())
    {
        $fullPath = $this->path . "views" . DIRECTORY_SEPARATOR;
        $fullPath = str_replace(array('/', "\\"), DIRECTORY_SEPARATOR, $fullPath . $file . ".php");

        if (!file_exists($fullPath) || !is_readable($fullPath))
        {
            return "Can't render : {$fullPath} either file doesn't exist or can't read it";
        }

        $contents = @file_get_contents($fullPath);

        // Variables are set
        if (count($vars) > 0)
        {
            $vars = array_combine(
                array_map(function ($key)
                {
                    return "{{" . $key . "}}";
                },
                    array_keys($vars)
                ),
                $vars
            );

            $contents = str_replace(array_keys($vars), array_values($vars), $contents);
        }

        return $contents;
    }

    public function ajaxScan()
    {
        check_ajax_referer("wpstg_ajax_nonce", "nonce");

        // Get Options
        $options = (new Scan)
            ->start()
            ->getOptions();

        wp_die();
    }
}