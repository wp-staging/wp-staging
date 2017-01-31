<?php
namespace WPStaging;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

// Ensure to include autoloader class
require_once __DIR__ . DIRECTORY_SEPARATOR . "Autoloader.php";

use WPStaging\Backend\Administrator;

/**
 * Class WPStaging
 * @package WPStaging
 */
final class WPStaging
{
    /**
     * Plugin version
     */
    const VERSION   = "2.0.0";

    /**
     * Plugin name
     */
    const NAME      = "WP Staging";

    /**
     * Plugin slug
     */
    const SLUG      = "wp-staging";

    /**
     * Services
     * @var array
     */
    private $services;

    /**
     * Singleton instance
     * @var WPStaging
     */
    private static $instance;

    /**
     * WPStaging constructor.
     */
    private function __construct()
    {
        $this->registerNamespaces();
        $this->loadDependencies();
    }

    /**
     * Register used namespaces
     */
    private function registerNamespaces()
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . "Autoloader.php";

        $autoloader = new Autoloader();
        $this->set("autoloader", $autoloader);

        // Base directory
        $dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . self::SLUG . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR;

        // Autoloader
        $autoloader->registerNamespaces(array(
            "WPStaging" => array(
                $dir,
                $dir . "Core" . DIRECTORY_SEPARATOR
            )
        ));

        // Register namespaces
        $autoloader->register();
    }

    /**
     * Get Instance
     * @return WPStaging
     */
    public static function getInstance()
    {
        if (null === static::$instance)
        {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Prevent cloning
     * @return void
     */
    private function __clone()
    {}

    /**
     * Prevent unserialization
     * @return void
     */
    private function __wakeup()
    {}

    /**
     * Load Dependencies
     */
    private function loadDependencies()
    {
        // Set loader
        $this->set("loader", new Loader());

        // Set cache
        $this->set("cache", new Cache());

        // Set logger
        $this->set("logger", new Logger());

        // Set Administrator
        new Administrator($this);
    }

    /**
     * Execute Plugin
     */
    public function run()
    {
        $this->get("loader")->run();
    }

    /**
     * Set a variable to DI with given name
     * @param string $name
     * @param mixed $variable
     */
    public function set($name, $variable)
    {
        // It is a function
        if (is_callable($variable)) $variable = $variable();

        // Add it to services
        $this->services[$name] = $variable;
    }

    /**
     * Get given name index from DI
     * @param string $name
     * @return mixed|null
     */
    public function get($name)
    {
        return (isset($this->services[$name])) ? $this->services[$name] : null;
    }

    // TODO load languages
    public function loadLanguages()
    {

    }
}