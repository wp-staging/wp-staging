<?php

namespace WPStaging\Core;

use Exception;
use RuntimeException;
use WPStaging\Backend\Administrator;
use WPStaging\Framework\Job\JobServiceProvider;
use WPStaging\Backup\BackupServiceProvider;
use WPStaging\Basic\BasicServiceProvider;
use WPStaging\Core\Cron\Cron;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\WpAdapter;
use WPStaging\Framework\AnalyticsServiceProvider;
use WPStaging\Framework\AssetServiceProvider;
use WPStaging\Framework\CommonServiceProvider;
use WPStaging\Framework\DI\Container;
use WPStaging\Framework\ErrorHandler;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\DirectoryListing;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\NoticeServiceProvider;
use WPStaging\Framework\Permalinks\PermalinksPurge;
use WPStaging\Framework\SettingsServiceProvider;
use WPStaging\Framework\SiteInfo;
use WPStaging\Staging\FirstRun;
use WPStaging\Framework\Url;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Frontend\Frontend;
use WPStaging\Frontend\FrontendServiceProvider;
use WPStaging\Pro\ProServiceProvider;
use WPStaging\Staging\StagingServiceProvider;

/**
 * Class WPStaging
 * @package WPStaging
 */
final class WPStaging
{
    /**
     * Internal Use Only: Hook to register Basic or Pro specific services.
     * @var string
     */
    const HOOK_BOOTSTRAP_SERVICES = 'wpstg.bootstrap.services';

    /**
     * Singleton instance
     * @var WPStaging
     */
    private static $instance;

    /**
     * @var bool
     */
    private static $useBaseContainerSingleton = false;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var bool Whether this Singleton instance has bootstrapped already.
     */
    private $isBootstrapped = false;

    /**
     * @var int|float The microtime where the Container was bootstrapped. Used to identify the time where the application started running.
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

        // Register Pro or Basic Provider, Always prefer registering Pro if both classes found unless if dev basic constant enabled. If both not present throw error
        if (class_exists('\WPStaging\Pro\ProServiceProvider') && !WPStaging::isDevBasic()) {
            $this->container->register(ProServiceProvider::class);
        } elseif (class_exists('\WPStaging\Basic\BasicServiceProvider')) {
            $this->container->register(BasicServiceProvider::class);
        } else {
            throw new RuntimeException('Basic and Pro Providers both not found! At least one of them should be present.');
        }

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

        $this->container->register(JobServiceProvider::class);
        $this->container->register(StagingServiceProvider::class);
        $this->container->register(BackupServiceProvider::class);

        // Internal Use Only: Register Basic or Pro specific services.
        Hooks::callInternalHook(self::HOOK_BOOTSTRAP_SERVICES);

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
        /** @var Database $db */
        $db = WPStaging::getInstance()->getVar("database");
        return $db->getPrefix();
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
     * @param bool $useBaseContainerSingleton
     * @return void
     */
    public static function setUseBaseContainerSingleton(bool $useBaseContainerSingleton)
    {
        static::$useBaseContainerSingleton = $useBaseContainerSingleton;
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
            static::$instance = new WPStaging(new Container(false, static::$useBaseContainerSingleton));
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
            $this->container      = new Container(false, static::$useBaseContainerSingleton);
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
     * Load Dependencies
     */
    private function loadDependencies()
    {
        if (!WPStaging::isWordPressLoaded()) {
            return;
        }

        // Load globally available functions
        require_once(__DIR__ . "/Utils/functions.php");

        $cache = WPStaging::make(Cache::class);
        $cache->setLifetime(-1); // Non-expireable file
        $cache->setPath(WPStaging::getContentDir());
        $this->set("cache", $cache);

        $this->set("logger", new Logger());

        $this->loadLanguages();

        // Set Administrator
        if (is_admin()) {
            new Administrator();
            return;
        }

        if (class_exists('\WPStaging\Pro\Frontend\Frontend')) {
            new \WPStaging\Pro\Frontend\Frontend();
        } else {
            new Frontend();
        }
    }

    private function loadLanguages()
    {
        (new Language())->load();
    }

    /**
     * Set a variable to DI with given name
     *
     * @param string $name
     * @param mixed $variable
     *
     * @return $this
     * @deprecated Use setVar instead.
     *
     */
    public function set($name, $variable)
    {
        return $this->setVar($name, $variable);
    }

    /**
     * Store a variable in DI container with given name
     *
     * @param string $name
     * @param mixed $variable
     * @return self
     */
    public function setVar(string $name, $variable)
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
     * @deprecated Use getVar instead if you want to retrieve value for a variable set using setVar or set.
     *
     */
    public function get($name)
    {
        return $this->container->_get($name);
    }

    /**
     * Get a variable from DI container with given name
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getVar(string $name, $default = null)
    {
        return $this->container->getVar($name, $default);
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
        if (WPStaging::isDevBasic()) {
            // @phpstan-ignore-next-line
            return WPSTG_DEV_BASIC; // This constant will only be returned if it's a string e.g. '1.0.0'
        }

        if (self::isPro()) {
            return WPSTGPRO_VERSION;
        }

        return WPSTG_VERSION;
    }

    /**
     * @return bool
     */
    public static function isWordPressLoaded()
    {
        return defined('ABSPATH') && function_exists('wp');
    }

    /**
     * @return bool
     * @deprecated Use isBasic instead.
     */
    public static function isPro()
    {
        return !self::isBasic();
    }

    /**
     * @param bool $silence
     */
    public static function silenceLogs($silence = true)
    {
        WPStaging::getInstance()->setVar('SILENCE_LOGS', $silence);
    }

    /**
     * @return bool
     */
    public static function areLogsSilenced()
    {
        try {
            return WPStaging::getInstance()->getVar('SILENCE_LOGS', false);
        } catch (Exception $ex) {
            return false;
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

    /**
     * Const is used during development for building and testing basic/free features
     * The constant expects a string value like a version number '1.0.0' to treat the plugin as a free version
     * Boolean false and the plugin will be treated as a premium version or regular free version depending on
     * the availability of the constants WPSTGPRO_VERSION or WPSTG_VERSION.
     *
     * @return bool
     */
    public static function isDevBasic()
    {
        return defined('WPSTG_DEV_BASIC') && is_string(WPSTG_DEV_BASIC);
    }

    /**
     * @return bool
     */
    public static function isBasic()
    {
        return WPStaging::getInstance()->getVar('WPSTG_BASIC', true) === true;
    }

    /**
     * @return bool
     */
    public static function isOnWordPressPlayground(): bool
    {
        return ( ABSPATH === '/wordpress/' && defined('WP_HOME') && strpos(WP_HOME, '/scope:') && ! empty($_SERVER['SERVER_SOFTWARE']) && $_SERVER['SERVER_SOFTWARE'] === 'PHP.wasm' );
    }
}
