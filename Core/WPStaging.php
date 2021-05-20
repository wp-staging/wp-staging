<?php

namespace WPStaging\Core;

use WPStaging\Backend\Administrator;
use WPStaging\Core\Cron\Cron;
use WPStaging\Core\Utils\Cache;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\AssetServiceProvider;
use WPStaging\Framework\DI\Container;
use WPStaging\Framework\Filesystem\DirectoryListing;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Permalinks\PermalinksPurge;
use WPStaging\Framework\Staging\FirstRun;
use WPStaging\Frontend\Frontend;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

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
    private $startTime;

    /**
     * WPStaging constructor.
     */
    private function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function bootstrap()
    {
        $this->isBootstrapped = true;

        $this->startTime = microtime(true);

        $this->container->register(CoreServiceProvider::class);

        $this->loadDependencies();

        // Boot the container after dependencies are loaded.
        $this->container->boot();

        $this->container->register(AssetServiceProvider::class);
        $this->initCron();
        $this->cloneSiteFirstRun();

        if (class_exists('\WPStaging\Pro\ProServiceProvider')) {
            $this->container->register(\WPStaging\Pro\ProServiceProvider::class);
        }

        $this->handleCacheIssues();
        $this->preventDirectoryListing();
    }

    public function getStartTime()
    {
        return $this->startTime;
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
     * @return string
     */
    public static function getTablePrefix()
    {
        return WPStaging::getInstance()->get("wpdb")->prefix;
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
            $this->container = new Container();
        }
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
     * @param mixed  $variable
     *
     * @deprecated Refactor implementations of this method to use the Container instead.
     *
     * @return $this
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
     * @deprecated Refactor implementations of this method to use the Container instead.
     *
     * @return mixed|null
     */
    public function get($name)
    {
        return $this->container->_get($name);
    }

    /**
     * Get given name index from DI
     *
     * @param string $name
     *
     * @deprecated Refactor implementations of this method to use Dependency Injection instead.
     *
     * @return mixed|null
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
        if (is_admin() && !wp_doing_ajax()) {
            /** @var DirectoryListing $directoryListing */
            $directoryListing = $this->getContainer()->make(DirectoryListing::class);
            $directoryListing->protectPluginUploadDirectory();
        }
    }
}
