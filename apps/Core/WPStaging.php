<?php

namespace WPStaging;

// No Direct Access
if( !defined( "WPINC" ) ) {
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
use WPStaging\DI\InjectionAware;
use WPStaging\Cron\Cron;

/**
 * Class WPStaging
 * @package WPStaging
 */
final class WPStaging {

    /**
     * Plugin version
     */
    const VERSION = "2.4.3";

    /**
     * Plugin name
     */
    const NAME = "WP Staging";

    /**
     * Plugin slug
     */
    const SLUG = "wp-staging";

    /**
     * Compatible WP Version
     */
    const WP_COMPATIBLE = "5.0.0";

    public $wpPath;

    /**
     * Slug: Either wp-staging or wp-staging-pro
     * @var string 
     */
    public $slug;

    /**
     * Absolute plugin path
     * @var string
     */
    public $pluginPath;

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
    private function __construct() {

        $this->registerMain();
        $this->registerNamespaces();
        $this->loadLanguages();
        $this->loadDependencies();
        $this->defineHooks();
        // Licensing stuff be loaded in wpstg core to make cron hook available from frontpage
        $this->initLicensing();

        wpstg_setup_environment();
    }

    /**
     * Get root WP root path -
     * Changed ABSPATH trailingslash for windows compatibility

     * @return type
     */
    public static function getWPpath() {
        return str_replace( '/', DIRECTORY_SEPARATOR, ABSPATH );
    }

    /**
     * Method to be executed upon activation of the plugin
     */
    public function registerMain() {
        // Slug of the plugin
        $this->slug = plugin_basename( dirname( dirname( dirname( __FILE__ ) ) ) );

        // absolute path to the main plugin dir
        $this->pluginPath = plugin_dir_path( dirname( dirname( __FILE__ ) ) );

        // URL to apps folder
        $this->url = plugin_dir_url( dirname( __FILE__ ) );

        // URL to backend public folder folder
        $this->backend_url = plugin_dir_url( dirname( __FILE__ ) ) . "Backend/public/";

        // URL to frontend public folder folder
        $this->frontend_url = plugin_dir_url( dirname( __FILE__ ) ) . "Frontend/public/";
    }

    /**
     * Define Hooks
     */
    public function defineHooks() {
        $loader = $this->get( "loader" );
        $loader->addAction( "admin_enqueue_scripts", $this, "enqueueElements", 100 );
        $loader->addAction( "admin_enqueue_scripts", $this, "removeWPCoreJs", 5 );
        $loader->addAction( "wp_enqueue_scripts", $this, "enqueueElements", 100 );
        $this->addIntervals();
    }

    /**
     * Remove heartbeat api and user login check
     * @param type $hook
     * @return type
     */
    public function removeWPCoreJs( $hook ) {
        $availablePages = array(
            "toplevel_page_wpstg_clone",
            "wp-staging-pro_page_wpstg-settings",
            "wp-staging-pro_page_wpstg-tools",
            "wp-staging-pro_page_wpstg-license"
        );

        // Load these css and js files only on wp staging admin pages
        if( !in_array( $hook, $availablePages ) || !is_admin() ) {
            return;
        }

        // Disable user login status check
        remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );
        // Disable heartbeat check for cloning and pushing
        wp_deregister_script( 'heartbeat' );
    }

    /**
     * Add new cron time event "weekly"
     */
    public function addIntervals() {
        $interval = new Cron();
    }

    /**
     * Scripts and Styles
     * @param string $hook
     */
    public function enqueueElements( $hook ) {

        // Load this css file on frontend and backend on all pages if current site is a staging site
        if( $this->isStagingSite() ) {
            wp_enqueue_style( "wpstg-admin-bar", $this->backend_url . "css/wpstg-admin-bar.css", array(), $this->getVersion() );
        }

        $availablePages = array(
            "toplevel_page_wpstg_clone",
            "wp-staging_page_wpstg-settings",
            "wp-staging_page_wpstg-tools",
            "wp-staging_page_wpstg-license",
            "wp-staging_page_wpstg-welcome",
        );

        // Load these css and js files only on wp staging admin pages
        if( !in_array( $hook, $availablePages ) || !is_admin() ) {
            return;
        }


        // Load admin js files
        wp_enqueue_script(
                "wpstg-admin-script", $this->backend_url . "js/wpstg-admin.js", array("jquery"), $this->getVersion(), false
        );

        // Load admin css files
        wp_enqueue_style(
                "wpstg-admin", $this->backend_url . "css/wpstg-admin.css", array(), $this->getVersion()
        );

        wp_localize_script( "wpstg-admin-script", "wpstg", array(
            "nonce"       => wp_create_nonce( "wpstg_ajax_nonce" ),
            "noncetick"   => apply_filters( 'nonce_life', DAY_IN_SECONDS ),
            "delayReq"    => $this->getDelay(),
            "settings"    => ( object ) array(), // TODO add settings?
            "tblprefix"   => self::getTablePrefix(),
            "isMultisite" => is_multisite() ? true : false
        ) );
    }

    /**
     * Get table prefix of the current site
     * @return string
     */
    public static function getTablePrefix() {
        $wpDB = WPStaging::getInstance()->get( "wpdb" );
        return $wpDB->prefix;
    }

    /**
     * Caching and logging folder
     * 
     * @return string
     */
    public static function getContentDir() {
        $wp_upload_dir = wp_upload_dir();
        $path          = $wp_upload_dir['basedir'] . '/wp-staging';
        wp_mkdir_p( $path );
        return apply_filters( 'wpstg_get_upload_dir', $path . DIRECTORY_SEPARATOR );
    }

    /**
     * Register used namespaces
     */
    private function registerNamespaces() {
        $autoloader = new Autoloader();
        $this->set( "autoloader", $autoloader );

        // Autoloader
        $autoloader->registerNamespaces( array(
            "WPStaging"  => array(
                $this->pluginPath . 'apps' . DIRECTORY_SEPARATOR,
                $this->pluginPath . 'apps' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR,
                $this->pluginPath . 'apps' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Iterators' . DIRECTORY_SEPARATOR,
            ),
            "splitbrain" => array(
                $this->pluginPath . 'vendor' . DIRECTORY_SEPARATOR . 'splitbrain' . DIRECTORY_SEPARATOR
            )
        ) );

        // Register namespaces
        $autoloader->register();
    }

    /**
     * Get Instance
     * @return WPStaging
     */
    public static function getInstance() {
        if( null === static::$instance ) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Prevent cloning
     * @return void
     */
    private function __clone() {
        
    }

    /**
     * Prevent unserialization
     * @return void
     */
    private function __wakeup() {
        
    }

    /**
     * Load Dependencies
     */
    private function loadDependencies() {
        // Set loader
        $this->set( "loader", new Loader() );

        // Set cache
        $this->set( "cache", new Cache() );

        // Set logger
        $this->set( "logger", new Logger() );

        // Set settings
        $this->set( "settings", new Settings() );

        // Load globally available functions
        require_once $this->pluginPath . 'apps/Core/Utils/functions.php';

        // Set Administrator
        if( is_admin() ) {
            new Administrator( $this );
        } else {
            new Frontend( $this );
        }
    }

    /**
     * Execute Plugin
     */
    public function run() {
        $this->get( "loader" )->run();
    }

    /**
     * Set a variable to DI with given name
     * @param string $name
     * @param mixed $variable
     * @return $this
     */
    public function set( $name, $variable ) {
        // It is a function
        if( is_callable( $variable ) )
            $variable = $variable();

        // Add it to services
        $this->services[$name] = $variable;

        return $this;
    }

    /**
     * Get given name index from DI
     * @param string $name
     * @return mixed|null
     */
    public function get( $name ) {
        return (isset( $this->services[$name] )) ? $this->services[$name] : null;
    }

    /**
     * @return string
     */
    public function getVersion() {
        return self::VERSION;
    }

    /**
     * @return string
     */
    public function getName() {
        return self::NAME;
    }

    /**
     * @return string
     */
    public static function getSlug() {
        return plugin_basename( dirname( dirname( dirname( __FILE__ ) ) ) );
    }

    /**
     * Get path to main plugin file
     * @return string
     */
    public function getPath() {
        return dirname( dirname( __FILE__ ) );
    }

    /**
     * Get main plugin url
     * @return type
     */
    public function getUrl() {
        return plugin_dir_url( dirname( __FILE__ ) );
    }

    /**
     * @return array|mixed|object
     */
    public function getDelay() {
        $options = $this->get( "settings" );
        $setting = $options->getDelayRequests();

        switch ( $setting ) {
            case "0":
                $delay = 0;
                break;

            case "1":
                $delay = 1000;
                break;

            case "2":
                $delay = 2000;
                break;

            case "3":
                $delay = 3000;
                break;

            case "4":
                $delay = 4000;
                break;

            default:
                $delay = 0;
        }

        return $delay;
    }

    /**
     * Load language file
     */
    public function loadLanguages() {
        $languagesDirectory = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . self::SLUG . DIRECTORY_SEPARATOR . "languages" . DIRECTORY_SEPARATOR;

        // Set filter for plugins languages directory
        $languagesDirectory = apply_filters( "wpstg_languages_directory", $languagesDirectory );

        // Traditional WP plugin locale filter
        $locale = apply_filters( "plugin_locale", get_locale(), "wp-staging" );
        $moFile = sprintf( '%1$s-%2$s.mo', "wp-staging", $locale );

        // Setup paths to current locale file
        $moFileLocal  = $languagesDirectory . $moFile;
        $moFileGlobal = WP_LANG_DIR . DIRECTORY_SEPARATOR . "wp-staging" . DIRECTORY_SEPARATOR . $moFile;

        // Global file (/wp-content/languages/wpstg)
        if( file_exists( $moFileGlobal ) ) {
            load_textdomain( "wp-staging", $moFileGlobal );
        }
        // Local file (/wp-content/plugins/wp-staging/languages/)
        elseif( file_exists( $moFileLocal ) ) {
            load_textdomain( "wp-staging", $moFileLocal );
        }
        // Default file
        else {
            load_plugin_textdomain( "wp-staging", false, $languagesDirectory );
        }
    }

    /**
     * Check if it is a staging site
     * @return bool
     */
    private function isStagingSite() {
        return ("true" === get_option( "wpstg_is_staging_site" ));
    }

    /**
     * Initialize licensing functions
     * @return boolean
     */
    public function initLicensing() {
        // Add licensing stuff if class exists
        if( class_exists( 'WPStaging\Backend\Pro\Licensing\Licensing' ) ) {
            $licensing = new Backend\Pro\Licensing\Licensing();
        }
        return false;
    }

}
