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
use WPStaging\DI\InjectionAware;
use WPStaging\WPStaging;

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

    public function getClonePage()
    {
        require_once "{$this->path}views/clone/index.php";
    }

    public function getToolsPage()
    {
        require_once "{$this->path}views/tools/index.php";
    }

    public function enqueueElements()
    {

    }
}