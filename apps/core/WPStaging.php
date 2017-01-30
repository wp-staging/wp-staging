<?php

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

/**
 * Class WPStaging
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
        $this->loadDependencies();
        $this->run();
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
        // Require necessary files
        $basePath = plugin_dir_path(__FILE__);
        require_once $basePath . "Loader.php";
        require_once $basePath . "Cache.php";
        require_once $basePath . "Logger.php";
        require_once $basePath . "backend" . DIRECTORY_SEPARATOR . "Administrator.php";

        // Set loader
        $this->set("loader", new WPStaging_Loader());

        // Set cache
        $this->set("cache", new WPStaging_Cache());

        // Set logger
        $this->set("logger", new WPStaging_Logger());

        // Set Administrator
        (new WPStaging_Administrator);
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