<?php

namespace WPStaging\Core;

use WPStaging\Backend\Administrator;
use WPStaging\Backend\Pro\Licensing\Licensing;
use WPStaging\Backup\BackupServiceProvider;
use WPStaging\Core\Cron\Cron;
use WPStaging\Core\Utils\Cache;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Adapter\WpAdapter;
use WPStaging\Framework\AnalyticsServiceProvider;
use WPStaging\Framework\AssetServiceProvider;
use WPStaging\Framework\CommonServiceProvider;
use WPStaging\Framework\DI\Container;
use WPStaging\Framework\ErrorHandler;
use WPStaging\Framework\Filesystem\DirectoryListing;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\NoticeServiceProvider;
use WPStaging\Framework\Permalinks\PermalinksPurge;
use WPStaging\Framework\SettingsServiceProvider;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Staging\FirstRun;
use WPStaging\Framework\Url;
use WPStaging\Frontend\Frontend;
use WPStaging\Frontend\FrontendServiceProvider;
use WPStaging\Pro\ProServiceProvider;

/**
 * Class WPStaging
 * @package WPStaging
 */
final class WPStaging
{
    /**
     * Singleton instance
     * @var WPStaging
     */
    private static $instance;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var bool Whether this Singleton instance has bootstrapped already.
     */
    private $isBootstrapped = false;

    /**
     * @var int The microtime where the Container was bootstraped. Used to identify the time where the application started running.
     */
    public static $startTime;

    /** @var Filesystem */
    private $filesystem;

    /** @var ErrorHandler */
    private $errorHandler;

    /**
     * WPStaging constructor.
     */
    private function __construct(Container $container)
    {
        $this->container    = $container;
        $this->errorHandler = new ErrorHandler();
        $this->filesystem   = new Filesystem();
    }

    public function bootstrap()
    {
        $this->isBootstrapped = true;

        WPStaging::$startTime = microtime(true);

        $this->setupDebugLog();

        $this->container->register(CoreServiceProvider::class);

        $this->loadDependencies();

        // Boot the container after dependencies are loaded.
        $this->container->boot();

        $this->container->register(CommonServiceProvider::class);
        $this->container->register(AssetServiceProvider::class);

        /** @var WpAdapter */
        $wpAdapter = $this->container->get(WpAdapter::class);

        $currentUrlPath = $this->container->get(Url::class)->getCurrentRoute();

        // Register notices only on UI requests and admin pages except plugins.php
        // to keep the plugins page alive and allow deactivation of the plugin in case of failure in one of the notices.
        if (!$wpAdapter->doingAjax() && !$wpAdapter->isWpCliRequest() && is_admin() && strpos($currentUrlPath, 'plugins.php') === false) {
            $this->container->register(NoticeServiceProvider::class);
        }

        $this->initCron();

        $this->container->register(SettingsServiceProvider::class);

        $this->cloneSiteFirstRun();

        $this->container->register(AnalyticsServiceProvider::class);

        // @todo remove this pro condition in the last step PR of backup feature to enable backup on free version.
        if (self::isPro() && class_exists('\WPStaging\Backup\BackupServiceProvider')) {
            $this->container->register(BackupServiceProvider::class);
        }

        if (class_exists('\WPStaging\Pro\ProServiceProvider')) {
            $this->container->register(ProServiceProvider::class);
        }

        $this->container->register(FrontendServiceProvider::class);


        $this->handleCacheIssues();
        $this->preventDirectoryListing();
    }

    public function registerErrorHandler()
    {
        $this->errorHandler->registerShutdownHandler();
    }

    protected function setupDebugLog()
    {
        if (!defined('WPSTG_UPLOADS_DIR')) {
            $wpStagingUploadsDir = trailingslashit(wp_upload_dir()['basedir']) . WPSTG_PLUGIN_DOMAIN . '/';
            define('WPSTG_UPLOADS_DIR', $wpStagingUploadsDir);
        }

        if (defined('WPSTG_DEBUG_LOG_FILE')) {
            return;
        }

        $logsDirectory = WPSTG_UPLOADS_DIR . 'logs/';

        if (!file_exists($logsDirectory)) {
            $this->filesystem->mkdir($logsDirectory, true);
            $logsDirectoryExists = file_exists($logsDirectory) && is_writable($logsDirectory);
        } else {
            $logsDirectoryExists = is_writable($logsDirectory);
        }

        if ($logsDirectoryExists) {
            // eg: wpstg_debug_907b4a01db8d244da272601a0491cd5c.log
            $logFile = sanitize_file_name(sprintf('wpstg_debug_%s.log', strtolower(wp_hash(__FILE__))));

            define('WPSTG_DEBUG_LOG_FILE', $logsDirectory . $logFile);
        }
    }

    /**
     * Initialize cron jobs
     */
    private function initCron()
    {
        // Register cron job and add new interval 'weekly'
        new Cron();
    }

    /**
     * Get root WP root path -
     * Changed ABSPATH trailingslash for windows compatibility
     * @return string
     */
    public static function getWPpath()
    {
        return str_replace('/', DIRECTORY_SEPARATOR, ABSPATH);
    }

    /**
     * Get table prefix of the current site
     * Always use lowercase on Windows
     * @return string
     */
    public static function getTablePrefix()
    {
        $db = WPStaging::getInstance()->get("wpdb");
        if (self::isWindowsOs()) {
            return strtolower($db->prefix);
        }

        return $db->prefix;
    }

    /**
     * Get base prefix of the current wordpress
     * Always use lowercase on Windows
     * @return string
     */
    public static function getTableBasePrefix()
    {
        $db = WPStaging::getInstance()->get("wpdb");
        if (self::isWindowsOs()) {
            return strtolower($db->base_prefix);
        }

        return $db->base_prefix;
    }

    /**
     * Caching and logging folder
     *
     * @return string
     */
    public static function getContentDir()
    {
        $wp_upload_dir = wp_upload_dir();
        $path          = $wp_upload_dir['basedir'] . '/wp-staging';
        (new Filesystem())->mkdir($path);

        return apply_filters('wpstg_get_upload_dir', $path . '/');
    }

    /**
     * Get Instance
     *
     * @return WPStaging
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new WPStaging(new Container());
            static::getInstance();
        }

        if (!static::$instance->isBootstrapped) {
            static::$instance->bootstrap();
        }

        return static::$instance;
    }

    /**
     * Resets the Dependency Injection Container
     * Only to be used in automated tests.
     */
    public function resetContainer()
    {
        if (php_sapi_name() == "cli") {
            $this->isBootstrapped = false;
            $this->container      = new Container();
        }
    }

    /**
     * Is the current PHP OS Windows?
     *
     * @return bool
     */
    public static function isWindowsOs()
    {
        return strncasecmp(PHP_OS, 'WIN', 3) === 0;
    }

    /**
     * Check License is Valid or not
     *
     * @return bool
     */
    public static function isValidLicense()
    {
        return self::isPro() && ((new SiteInfo())->isStagingSite() || (new Licensing())->isValidOrExpiredLicenseKey());
    }

    /**
     * Load Dependencies
     */
    private function loadDependencies()
    {
        // Load globally available functions
        require_once(__DIR__ . "/Utils/functions.php");

        $this->set("cache", new Cache());

        $this->set("logger", new Logger());

        $this->loadLanguages();

        // Set Administrator
        if (is_admin()) {
            new Administrator();
        } else {
            new Frontend();
        }
    }

    private function loadLanguages()
    {
        /** @noinspection NullPointerExceptionInspection */
        $languagesDirectory = WPSTG_PLUGIN_DIR . 'languages/';

        if (function_exists('get_user_locale')) {
            $locale = get_user_locale();
        } else {
            $locale = get_locale();
        }

        // Traditional WP plugin locale filter
        $locale = apply_filters('plugin_locale', $locale, 'wp-staging');
        $moFile = sprintf('%1$s-%2$s.mo', 'wp-staging', $locale);

        // Setup paths to current locale file
        $moFileLocal  = $languagesDirectory . $moFile;
        $moFileGlobal = sprintf('%s/wp-staging/%s', WP_LANG_DIR, $moFile);

        if (file_exists($moFileGlobal)) {
            load_textdomain('wp-staging', $moFileGlobal);
        } elseif (file_exists($moFileLocal)) {
            load_textdomain('wp-staging', $moFileLocal);
        } else {
            load_plugin_textdomain('wp-staging', false, $languagesDirectory);
        }
    }

    /**
     * Set a variable to DI with given name
     *
     * @param string $name
     * @param mixed $variable
     *
     * @return $this
     * @deprecated Refactor implementations of this method to use the Container instead.
     *
     */
    public function set($name, $variable)
    {
        $this->container->setVar($name, $variable);

        return $this;
    }

    /**
     * Get given name index from DI
     *
     * @param string $name
     *
     * @return mixed|null
     * @deprecated Refactor implementations of this method to use the Container instead.
     *
     */
    public function get($name)
    {
        return $this->container->_get($name);
    }

    /**
     * Get given name index from DI
     *
     * USE THIS WISELY. Most of the time the dependencies should be injected through the __construct!
     * Google for "service locator vs dependency injection"
     *
     * @param string $id The bound service identifier, or a class name to build and return.
     *
     * @return mixed The built object bound to the id, the requested
     *               class instance.
     */
    public static function make($id)
    {
        static $container;

        if ($container === null) {
            $container = self::getInstance()->getContainer();
        }

        return $container->get($id);
    }

    /**
     * Get given name index from DI
     *
     * @param string $name
     *
     * @return mixed|null
     * @deprecated Refactor implementations of this method to use Dependency Injection instead.
     *
     */
    public function _make($name)
    {
        return $this->container->make($name);
    }

    /**
     * Returns the Container. Use this wisely!
     *
     * Acceptable example:
     * - Using the Container as a cache that lives during the request to avoid doing multiple operations.
     *
     * Avoid example:
     * - Using the Container to build instances of classes outside the __construct
     *
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return string
     */
    public static function getVersion()
    {
        if (self::isPro()) {
            return WPSTGPRO_VERSION;
        }

        return WPSTG_VERSION;
    }

    /**
     * @return bool
     *
     * @todo find a better place to make it mockable or add filter for mocking
     */
    public static function isPro()
    {
        return defined('WPSTGPRO_VERSION');
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
    private function handleCacheIssues()
    {
        $permalinksPurge = new PermalinksPurge();
        add_action('wpstg_pushing_complete', [$permalinksPurge, 'executeAfterPushing']);
        add_action('wp_loaded', [$permalinksPurge, 'purgePermalinks'], $permalinksPurge::PLUGINS_LOADED_PRIORITY);
    }

    /**
     * @todo Move this to a base service provider shared between Free and Pro
     */
    private function preventDirectoryListing()
    {
        // TODO: inject WpAdapter using DI
        if (is_admin() && !(new WpAdapter())->doingAjax()) {
            /** @var DirectoryListing $directoryListing */
            $directoryListing = $this->getContainer()->get(DirectoryListing::class);
            $directoryListing->protectPluginUploadDirectory();
        }
    }
}
