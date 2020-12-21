<?php

namespace WPStaging\Core;

// No Direct Access
if( !defined( "WPINC" ) ) {
    die;
}

use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\DI\Container;
use WPStaging\Backend\Administrator;
use WPStaging\Core\DTO\Settings;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\Security\Nonce;
use WPStaging\Frontend\Frontend;
use WPStaging\Core\Utils\Cache;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Permalinks\PermalinksPurge;
use WPStaging\Core\Cron\Cron;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Staging\FirstRun;

/**
 * Class WPStaging
 * @package WPStaging
 */
final class WPStaging {

    /**
     * Slug: Either wp-staging or wp-staging-pro
     * @var string
     */
    public $slug;

    /**
     * Absolute plugin path
     * @var string
     */
    private $pluginPath;

    /**
     * Singleton instance
     * @var WPStaging
     */
    private static $instance;

    /**
     * @var \WPStaging\Framework\SiteInfo
     */
    private $siteInfo;

    /*
     * @var string
     */
    private $backend_url;

    /**
     * @var string
     */
    private $frontend_url;

    /**
     * @var string
     */
    private $url;

    /**
     * @var AccessToken
     */
    private $accessToken;

    private $container;

    /**
     * WPStaging constructor.
     */
    private function __construct(Container $container) {
	    $this->container   = $container;

	    // Todo: Move this to a common service Provider for both Free and Pro. Do not register anything else here.
	    $this->container->bind(LoggerInterface::class, Logger::class);

        /*
         * @todo Before injecting these using DI, we have to register them in a Service Provider.
         *       Therefore, we don't inject them using DI here.
         */
        $this->accessToken = new AccessToken;
        $this->siteInfo    = new SiteInfo;

        $this->registerMain();
        $this->loadDependencies();
        $this->defineHooks();
        $this->initCron();
        $this->cloneSiteFirstRun();
        $this->maybeLoadPro();
        $this->handleCacheIssues();
    }

    /**
     * Initialize cron jobs
     */
    private function initCron() {
        // Register cron job and add new interval 'weekly'
        new Cron;
    }

    /**
     * Get root WP root path -
     * Changed ABSPATH trailingslash for windows compatibility

     * @return string
     */
    public static function getWPpath() {
        return str_replace( '/', DIRECTORY_SEPARATOR, ABSPATH );
    }

    /**
     * Method to be executed upon activation of the plugin
     */
    public function registerMain() {
        // Slug of the plugin
        $this->slug = plugin_basename(  dirname(__DIR__) );

        // absolute path to the main plugin dir
        $this->pluginPath = plugin_dir_path(__DIR__);

        // URL to main plugin folder
        $this->url = plugin_dir_url(__DIR__);

        // URL to backend public folder folder
        $this->backend_url = plugin_dir_url(__DIR__) . "Backend/public/";

        // URL to frontend public folder folder
        $this->frontend_url = plugin_dir_url(__DIR__) . "Frontend/public/";
    }

    /**
     * Define Hooks
     */
    public function defineHooks() {
        add_action('admin_enqueue_scripts', [$this, 'enqueueElements'], 100);
        add_action('admin_enqueue_scripts', [$this, 'removeWPCoreJs'], 5);
        add_action('wp_enqueue_scripts', [$this, 'enqueueElements'], 100);
    }

    /**
     * Remove heartbeat api and user login check
     * @param bool $hook
     */
    public function removeWPCoreJs( $hook ) {

        if( $this->isDisabledAssets($hook)) {
            return;
        }

        // Disable user login status check
        // Todo: Can we remove this now that we have AccessToken?
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
        return ( $pagenow === 'plugins.php' );
    }

    /**
     * Scripts and Styles
     * @param string $hook
     */
    public function enqueueElements( $hook ) {

        // Load this css file on frontend and backend on all pages if current site is a staging site
        if( wpstg_is_stagingsite() ) {
            wp_enqueue_style( "wpstg-admin-bar", $this->backend_url . "css/wpstg-admin-bar.css", [], self::getVersion() );
        }

        // Load js file on page plugins.php in free version only
        if( !defined('WPSTGPRO_VERSION') && $this->isPluginsPage() ) {
            wp_enqueue_script(
                    "wpstg-admin-script", $this->backend_url . "js/wpstg-admin-plugins.js", ["jquery"], self::getVersion(), false
            );
            wp_enqueue_style(
                    "wpstg-admin-feedback", $this->backend_url . "css/wpstg-admin-feedback.css", [], self::getVersion()
            );
        }

        if( $this->isDisabledAssets($hook)) {
            return;
        }


        // Load admin js files
        wp_enqueue_script(
                "wpstg-admin-script", $this->backend_url . "js/wpstg-admin.js", ["jquery"], self::getVersion(), false
        );

        // Sweet Alert
        wp_enqueue_script(
            'wpstg-admin-sweetalerts',
            $this->url . 'Backend/public/vendor/sweetalert2/sweetalert2.all.min.js',
            [],
            self::getVersion(),
            true
        );

        wp_enqueue_style(
            'wpstg-admin-sweetalerts',
            $this->url . 'Backend/public/vendor/sweetalert2/wordpress-admin.min.css',
            [],
            self::getVersion()
        );

        // Load admin js pro files
        if(defined('WPSTGPRO_VERSION')) {
            wp_enqueue_script(
                "wpstg-admin-pro-script", $this->url . "Backend/Pro/public/js/wpstg-admin-pro.js", ["jquery"], self::getVersion(), false
            );
        }

        // Load admin css files
        wp_enqueue_style(
                "wpstg-admin", $this->backend_url . "css/wpstg-admin.css", [], self::getVersion()
        );

        wp_localize_script( "wpstg-admin-script", "wpstg", [
            "delayReq"               => $this->getDelay(),
            "settings"               => ( object )[], // TODO add settings?
            "tblprefix"              => self::getTablePrefix(),
            "isMultisite"            => is_multisite(),
            AccessToken::REQUEST_KEY => (string)$this->accessToken->getToken() ?: (string)$this->accessToken->generateNewToken(),
            'nonce'                  => wp_create_nonce(Nonce::WPSTG_NONCE),
        ] );
    }

    /**
     * Load css and js files only on wp staging admin pages
     * @param $page string slug of the current page
     * @return bool
     */
    private function isDisabledAssets($page)
    {
        if (defined('WPSTGPRO_VERSION')) {
            $availablePages = [
                "toplevel_page_wpstg_clone",
                "wp-staging-pro_page_wpstg-settings",
                "wp-staging-pro_page_wpstg-tools",
                "wp-staging-pro_page_wpstg-license"
            ];
        } else {
            $availablePages = [
                "toplevel_page_wpstg_clone",
                "wp-staging_page_wpstg-settings",
                "wp-staging_page_wpstg-tools",
                "wp-staging_page_wpstg-welcome",
            ];
        }

        return !in_array($page, $availablePages) || !is_admin();
    }

    /**
     * Get table prefix of the current site
     * @return string
     */
    public static function getTablePrefix() {
        return WPStaging::getInstance()->get( "wpdb" )->prefix;
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
     * Get Instance
     *
     * @param Container|null $container
     *
     * @return WPStaging
     */
    public static function getInstance(Container $container = null) {
        if( static::$instance === null ) {
            if ($container === null) {
                $container = new Container;
                if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                    error_log('A Container instance should be set when requiring this class for the first time.');
                }
            }

            static::$instance = new static($container);
        }

        return static::$instance;
    }

    /**
     * Load Dependencies
     */
    private function loadDependencies() {
        // Load globally available functions
        require_once ($this->pluginPath . "Core/Utils/functions.php");

        $this->set( "cache", new Cache() );

        $this->set( "logger", new Logger() );

        $this->set( "settings", new Settings() );

        $this->loadLanguages();

        // Set Administrator
        if( is_admin() ) {
            new Administrator();
        } else {
            new Frontend();
        }
    }

	private function loadLanguages()
	{
		/** @noinspection NullPointerExceptionInspection */
		$languagesDirectory = WPSTG_PLUGIN_DIR . 'languages/';

		if (function_exists('get_user_locale')){
			$locale = get_user_locale();
		} else {
			$locale = get_locale();
		}

		// Traditional WP plugin locale filter
		$locale = apply_filters('plugin_locale', $locale, 'wp-staging');
		$moFile = sprintf('%1$s-%2$s.mo', 'wp-staging', $locale);

		// Setup paths to current locale file
		$moFileLocal = $languagesDirectory . $moFile;
		$moFileGlobal = sprintf('%s/wp-staging/%s', WP_LANG_DIR, $moFile);

		if (file_exists($moFileGlobal)) {
			load_textdomain('wp-staging', $moFileGlobal);
		}
		elseif (file_exists($moFileLocal)) {
			load_textdomain('wp-staging', $moFileLocal);
		}
		else {
			load_plugin_textdomain('wp-staging', false, $languagesDirectory);
		}
	}

    /**
     * Set a variable to DI with given name
     * @param string $name
     * @param mixed $variable
     *
     * @deprecated Refactor implementations of this method to use the Container instead.
     *
     * @return $this
     */
    public function set( $name, $variable ) {
	    $this->container->setVar( $name, $variable );

	    return $this;
    }

    /**
     * Get given name index from DI
     * @param string $name
     *
     * @deprecated Refactor implementations of this method to use the Container instead.
     *
     * @return mixed|null
     */
    public function get( $name ) {
	    return $this->container->_get( $name );
    }

    /**
     * Get given name index from DI
     * @param string $name
     *
     * @deprecated Refactor implementations of this method to use Dependency Injection instead.
     *
     * @return mixed|null
     */
    public function _make( $name ) {
	    return $this->container->make( $name );
    }

    /**
     * @return string
     */
    public static function getVersion() {

        if(defined('WPSTGPRO_VERSION'))
        {
            return WPSTGPRO_VERSION;
        }

        return WPSTG_VERSION;

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
     * Load Pro actions if they exist.
     */
    private function maybeLoadPro()
    {
        if (class_exists('\WPStaging\Pro\ProServiceProvider')) {
            $this->container->register(\WPStaging\Pro\ProServiceProvider::class);
        }
    }

    /**
     * Executes the first time a clone site runs.
     */
    private function cloneSiteFirstRun()
    {
        (new FirstRun())->init();
    }

    /**
     * Takes care of cache issues in certain situations
     */
    private function handleCacheIssues() {
        $permalinksPurge = new PermalinksPurge();
        add_action( 'wpstg_pushing_complete', [ $permalinksPurge, 'executeAfterPushing' ]);
        add_action( 'wp_loaded', [ $permalinksPurge, 'purgePermalinks' ], $permalinksPurge::PLUGINS_LOADED_PRIORITY);
    }

}
