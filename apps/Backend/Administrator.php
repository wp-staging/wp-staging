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
use WPStaging\Backend\Modules\Views\Tabs\Settings;
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
        // Tabs
        $this->di->set("admin-tabs", new Settings());

        // Forms
        $this->di->set("general-forms", new FormSettings());

        // Set loader
        $this->di->set("data", new Data());

        // Set cache
        $this->di->set("database", new Database());

        // Set logger
        $this->di->set("files", new Files());
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
            "wpstg_clone_page"
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
        require_once "{$this->path}views/tools/index.php";
    }

    /**
     * Scripts and Styles
     */
    public function enqueueElements()
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
}