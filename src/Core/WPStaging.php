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
use WPStaging\Service\Container\Container;
use WPStaging\Utils\Autoloader;
use WPStaging\Utils\Cache;
use WPStaging\Utils\Loader;
use WPStaging\Utils\Logger;
use WPStaging\Service\PluginFactory;

/**
 * Class WPStaging
 * @package WPStaging
 */
final class WPStaging {

    /*
     * Plugin name
     */
    const NAME = "WP Staging";

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
        $this->loadDependencies();
        $this->defineHooks();
        $this->initCron();
        // Load license class in wpstg core to allow executing cron jobs by regular frontpage visitors
        $this->initLicensing();
        $this->initVersion();
        $this->initActions();
    }

    /**
     * Initialize cron jobs
     */
    private function initCron() {
        // Register cron job and add new interval 'weekly'
        $cron = new \WPStaging\Cron\Cron;
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
        $this->slug = plugin_basename(  dirname( dirname( __FILE__ ) ) );

        // absolute path to the main plugin dir
        $this->pluginPath = plugin_dir_path( dirname( __FILE__ ) );

        // URL to main plugin folder
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
    }

    /**
     * Remove heartbeat api and user login check
     * @param type $hook
     * @return type
     */
    public function removeWPCoreJs( $hook ) {

        if( $this->isDisabledAssets($hook)) {
            return;
        }

        // Disable user login status check
        remove_action( 'admin_enqueue_scripts', 'wp_auth_check_load' );

        // Disable heartbeat check for cloning and pushing
        wp_deregister_script( 'heartbeat' );
    }

    /**
     * Check if current page is plugins.php
     * @global array $pagenow
     * @return bool
     */
    private function isPluginsPage() {
        global $pagenow;
        return ( 'plugins.php' === $pagenow );
    }

    /**
     * Scripts and Styles
     * @param string $hook
     */
    public function enqueueElements( $hook ) {

        // Load this css file on frontend and backend on all pages if current site is a staging site
        if( wpstg_is_stagingsite() ) {
            wp_enqueue_style( "wpstg-admin-bar", $this->backend_url . "css/wpstg-admin-bar.css", array(), $this->getVersion() );
        }

        // Load js file on page plugins.php in free version only
        if( !defined('WPSTGPRO_VERSION') && $this->isPluginsPage() ) {
            wp_enqueue_script(
                    "wpstg-admin-script", $this->backend_url . "js/wpstg-admin-plugins.js", array("jquery"), $this->getVersion(), false
            );
            wp_enqueue_style(
                    "wpstg-admin-feedback", $this->backend_url . "css/wpstg-admin-feedback.css", array(), $this->getVersion()
            );
        }

        if( $this->isDisabledAssets($hook)) {
            return;
        }


        // Load admin js files
        wp_enqueue_script(
                "wpstg-admin-script", $this->backend_url . "js/wpstg-admin.js", array("jquery"), $this->getVersion(), false
        );

        // Load admin js pro files
        if(defined('WPSTGPRO_VERSION')) {
            wp_enqueue_script(
                "wpstg-admin-pro-script", $this->url . "Backend/Pro/public/js/wpstg-admin-pro.js", array("jquery"), $this->getVersion(), false
            );

            // Sweet Alert
            wp_enqueue_script(
                'wpstg-admin-pro-sweetalerts',
                $this->url . 'Backend/Pro/public/vendor/sweetalert2/sweetalert2.all.min.js',
                [],
                $this->getVersion(),
                true
            );

            wp_enqueue_style(
                'wpstg-admin-pro-sweetalerts',
                $this->url . 'Backend/Pro/public/vendor/sweetalert2/wordpress-admin.min.css',
                [],
                $this->getVersion()
            );
        }

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
     * Load css and js files only on wp staging admin pages
     * @param $page string slug of the current page
     * @return bool
     */
    private function isDisabledAssets($page)
    {
        if (defined('WPSTGPRO_VERSION')) {
            $availablePages = array(
                "toplevel_page_wpstg_clone",
                "wp-staging-pro_page_wpstg-settings",
                "wp-staging-pro_page_wpstg-tools",
                "wp-staging-pro_page_wpstg-license"
            );
        } else {
            $availablePages = array(
                "toplevel_page_wpstg_clone",
                "wp-staging_page_wpstg-settings",
                "wp-staging_page_wpstg-tools",
                "wp-staging_page_wpstg-welcome",
            );
        }

        if( !in_array( $page, $availablePages ) || !is_admin() ) {
            return true;
        }
        return false;
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
        return apply_filters( 'wpstg_get_upload_dir', $path . '/' );
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
                $this->pluginPath,
                $this->pluginPath . 'Core' . DIRECTORY_SEPARATOR,
                $this->pluginPath . 'Core' . DIRECTORY_SEPARATOR . 'Iterators' . DIRECTORY_SEPARATOR,
            ),
            // @todo remove as it is not used any longer
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
        // Load globally available functions
        require_once $this->pluginPath . 'Core/Utils/functions.php';

        $this->set( "loader", new Loader() );

        $this->set( "cache", new Cache() );

        $this->set( "logger", new Logger() );

        $this->set( "settings", new Settings() );

        /** @noinspection PhpUnhandledExceptionInspection */
        $plugin = PluginFactory::make(Plugin::class);
        $plugin->init();
        $this->set(Plugin::class, $plugin);

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
     * @return Container
     */
    public static function getContainer()
    {
        /** @noinspection NullPointerExceptionInspection */
        return self::$instance->get(Plugin::class)->getContainer();
    }

    /**
     * @return string
     */
    public static function getVersion() {

        if(defined('WPSTGPRO_VERSION'))
        {
            return WPSTGPRO_VERSION;
        }
        if(defined('WPSTG_VERSION'))
        {
            return WPSTG_VERSION;
        }
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
    public static function getSlug()
    {
        return plugin_basename(dirname(dirname(__FILE__)));
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

    /**
     * Initialize Version Check
     * @return boolean
     */
    public function initVersion() {
        // Add licensing stuff if class exists
        if( class_exists( 'WPStaging\Backend\Pro\Licensing\Version' ) ) {
            $licensing = new Backend\Pro\Licensing\Version();
        }
        return false;
    }

    /**
     * Initialize several actions which can be hooked in by custom functions
     */
    private function initActions() {
        // Load one-time if current site is staging site and if it is loaded initially
        if( wpstg_is_stagingsite() ) {
            $execute = get_option( 'wpstg_execute' );
            if( false !== $execute ) {
                do_action( 'wpstg_clone_action_staging' );
                delete_option('wpstg_execute');
            }
        }
    }

}
