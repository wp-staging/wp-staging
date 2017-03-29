<?php
namespace WPStaging;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

// Ensure to include autoloader class
require_once __DIR__ . DIRECTORY_SEPARATOR . "Utils" . DIRECTORY_SEPARATOR . "Autoloader.php";

use WPStaging\Backend\Administrator;
use WPStaging\DTO\Settings;
use WPStaging\Frontend\Frontend;
use WPStaging\Utils\Autoloader;
use WPStaging\Utils\Cache;
use WPStaging\Utils\Loader;
use WPStaging\Utils\Logger;

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
     * Compatible WP Version
     */
    const WP_COMPATIBLE = "4.7.3";
    

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
        $file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . self::SLUG . DIRECTORY_SEPARATOR . self::SLUG . ".php";

        // Activation Hook
        register_activation_hook($file, array($this, "onActivation"));

        $this->registerNamespaces();
        $this->loadLanguages();
        $this->loadDependencies();
    }
    

    /**
     * Method to be executed upon activation of the plugin
     */
    public function onActivation()
    {

    }
    /**
     * Caching and logging folder
     * 
     * @return string
     */
    public static function getContentDir(){
	$wp_upload_dir = wp_upload_dir();
        $path = $wp_upload_dir['basedir'] . '/wp-staging';
	wp_mkdir_p( $path );
	return apply_filters( 'wpstg_get_upload_dir', $path . DIRECTORY_SEPARATOR );        
    }

    /**
     * Register used namespaces
     */
    private function registerNamespaces()
    {
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

        // Set settings
        $this->set("settings", new Settings());

        // Set Administrator
        if (is_admin())
        {
            new Administrator($this);
        }
        else
        {
            new Frontend($this);
        }
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
     * @return $this
     */
    public function set($name, $variable)
    {
        // It is a function
        if (is_callable($variable)) $variable = $variable();

        // Add it to services
        $this->services[$name] = $variable;

        return $this;
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

    /**
     * @return string
     */
    public function getVersion()
    {
        return self::VERSION;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return self::SLUG;
    }

    /**
     * @return array|mixed|object
     */
    public function getCPULoadSetting()
    {
        $options = $this->get("options");
        $setting = (isset($options->cpuLoad)) ? $options->cpuLoad : "default";

        switch ($setting)
        {
            case "high":
                $cpuLoad = 0;
                break;

            case "medium":
                $cpuLoad = 1000;
                break;

            case "low":
                $cpuLoad = 3000;
                break;

            case "default":
            default:
                $cpuLoad = 1000;
        }

        return $cpuLoad;
    }

    /**
     * Load language file
     */
    public function loadLanguages()
    {
        $languagesDirectory = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . self::SLUG . DIRECTORY_SEPARATOR;
        $languagesDirectory.= "vars" . DIRECTORY_SEPARATOR . "languages" . DIRECTORY_SEPARATOR;

        // Set filter for plugins languages directory
        $languagesDirectory = apply_filters("wpstg_languages_directory", $languagesDirectory);

        // Traditional WP plugin locale filter
        $locale             = apply_filters("plugin_locale", get_locale(), "wpstg");
        $moFile             = sprintf('%1$s-%2$s.mo', "wpstg", $locale);

        // Setup paths to current locale file
        $moFileLocal        = $languagesDirectory . $moFile;
        $moFileGlobal       = WP_LANG_DIR . DIRECTORY_SEPARATOR . "wpstg" . DIRECTORY_SEPARATOR . $moFile;

        // Global file (/wp-content/languages/WPSTG)
        if (file_exists($moFileGlobal))
        {
            load_textdomain("wpstg", $moFileGlobal);
        }
        // Local file (/wp-content/plugins/wp-staging/languages/)
        elseif (file_exists($moFileLocal))
        {
            load_textdomain("wpstg", $moFileGlobal);
        }
        // Default file
        else
        {
            load_plugin_textdomain("wpstg", false, $languagesDirectory);
        }
    }
}