<?php

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

/**
 * Class WPStaging_Administrator
 */
class WPStaging_Administrator
{
    private $path;

    /**
     * WPStaging_Administrator constructor.
     */
    public function __construct()
    {
        $this->loadDependencies();
        $this->defineHooks();

        // Path to backend
        $this->path = plugin_dir_path(__FILE__);
    }

    /**
     * Load Dependencies
     */
    private function loadDependencies()
    {
        // Require necessary files
        $basePath = plugin_dir_path(__FILE__) . "modules" . DIRECTORY_SEPARATOR;
        require_once $basePath . "Data.php";
        require_once $basePath . "Database.php";
        require_once $basePath . "Files.php";

        $WPStaging = WPStaging::getInstance();

        // Set loader
        $WPStaging->set("data", new WPStaging_Data());

        // Set cache
        $WPStaging->set("database", new WPStaging_Database());

        // Set logger
        $WPStaging->set("files", new WPStaging_Files());
    }

    /**
     * Define Hooks
     */
    private function defineHooks()
    {
        // Get loader
        $loader = WPStaging::getInstance()->get("loader");

        $loader->addAction("admin_enqueue_scripts", $this, "enqueueElements");
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
            [$this, "getSettingsPage"],
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
            "wpstg_options_page"
        );

        // Page: Tools
        add_submenu_page(
            "wpstg_clone",
            __("WP Staging Tools", "wpstg"),
            __("Tools", "wpstg"),
            "manage_options",
            "wpstg-tools",
            "wpstg_tools_page"
        );
    }

    /**
     * Settings Page
     */
    public function getSettingsPage()
    {
        require_once "{$this->path}views/settings/index.php";
    }

    public function enqueueElements()
    {

    }
}