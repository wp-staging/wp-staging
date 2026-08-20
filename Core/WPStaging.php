<?php

namespace WPStaging\Core;

use Exception;
use RuntimeException;
use WPStaging\Backend\Administrator;
use WPStaging\Backend\DashboardWidget\DashboardWidgetServiceProvider;
use WPStaging\Backup\BackupServiceProvider;
use WPStaging\Backup\Service\BackupsDirectoryResolver;
use WPStaging\Backup\Service\TmpBackupCleaner;
use WPStaging\Basic\BasicServiceProvider;
use WPStaging\Core\Cron\Cron;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Adapter\WpAdapter;
use WPStaging\Framework\AnalyticsServiceProvider;
use WPStaging\Framework\AssetServiceProvider;
use WPStaging\Framework\CommonServiceProvider;
use WPStaging\Framework\DI\Container;
use WPStaging\Framework\ErrorHandler;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\DirectoryListing;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Job\JobServiceProvider;
use WPStaging\Framework\Language\Language;
use WPStaging\Framework\NoticeServiceProvider;
use WPStaging\Framework\Permalinks\PermalinksPurge;
use WPStaging\Framework\SettingsServiceProvider;
use WPStaging\Staging\FirstRun;
use WPStaging\Framework\Url;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Frontend\Frontend;
use WPStaging\Pro\ProServiceProvider;
use WPStaging\Staging\StagingServiceProvider;





final class WPStaging
{




    const HOOK_BOOTSTRAP_SERVICES = 'wpstg.bootstrap.services';




    const SSE_DIR_NAME = 'sse';





    private static $instance;




    private static $useBaseContainerSingleton = false;




    private $container;




    private $isBootstrapped = false;




    public static $startTime;

 
    private $filesystem;

 
    private $errorHandler;




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

 
        if (class_exists('\WPStaging\Pro\ProServiceProvider') && !WPStaging::isDevBasic()) {
            $this->container->register(ProServiceProvider::class);
        } elseif (class_exists('\WPStaging\Basic\BasicServiceProvider')) {
            $this->container->register(BasicServiceProvider::class);
        } else {
            throw new RuntimeException('Basic and Pro Providers both not found! At least one of them should be present.');
        }

        $this->registerInitHook();
        $this->setupDebugLog();

        $this->container->register(CoreServiceProvider::class);

        $this->loadDependencies();

 
        $this->container->boot();

        $this->container->register(CommonServiceProvider::class);

 
        $wpAdapter = $this->container->get(WpAdapter::class);

 
        if (!$wpAdapter->doingAjax() && !$wpAdapter->isWpCliRequest() && !wp_doing_cron()) {
            $this->container->register(AssetServiceProvider::class);
        }

        $currentUrlPath = $this->container->get(Url::class)->getCurrentRoute();

 
 
        if (!$wpAdapter->doingAjax() && !$wpAdapter->isWpCliRequest() && is_admin() && strpos($currentUrlPath, 'plugins.php') === false) {
            $this->container->register(NoticeServiceProvider::class);
            if (isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'index.php') {
                $this->container->register(DashboardWidgetServiceProvider::class);
            }
        }

        $this->initCron();

        $this->container->register(SettingsServiceProvider::class);

        $this->cloneSiteFirstRun();

        $this->container->register(AnalyticsServiceProvider::class);

        $this->container->register(JobServiceProvider::class);
        $this->container->register(StagingServiceProvider::class);
        $this->container->register(BackupServiceProvider::class);

 
        Hooks::callInternalHook(self::HOOK_BOOTSTRAP_SERVICES);

        $this->handleCacheIssues();
        $this->preventDirectoryListing();
    }

    public function registerErrorHandler()
    {
        $this->errorHandler->registerShutdownHandler();
    }




    public function registerInitHook()
    {




        add_action('init', function () {
 
            $run = get_transient('wpstg.run_daily');
            if ($run) {
                return;
            }

            set_transient('wpstg.run_daily', true, 24 * HOUR_IN_SECONDS);

            $now = time();

 
            $sseDir = trailingslashit(WP_CONTENT_DIR) . WPSTG_PLUGIN_DOMAIN . '/' . self::SSE_DIR_NAME;
            $this->cleanupDirectory($sseDir, HOUR_IN_SECONDS, $now, $scanChildren = false);

            $uploadDir = wp_upload_dir(null, false);
            if (!empty($uploadDir['basedir'])) {
                $backupsDir = (new BackupsDirectoryResolver())->resolveFromUploadsDirectory($uploadDir['basedir']);
                (new TmpBackupCleaner())->clean($backupsDir, DAY_IN_SECONDS, $now);
            }
        }, 1);
    }























    private function cleanupDirectory(string $directory, int $maxAge, int $now, bool $scanChildren = true)
    {
        if (!is_dir($directory)) {
            return;
        }

        $items    = scandir($directory);
        $hasFiles = false;
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = trailingslashit($directory) . $item;

            if (is_file($itemPath)) {
                $hasFiles = true;
                $fileAge  = $now - filemtime($itemPath);
                if ($fileAge >= $maxAge) {
                    @unlink($itemPath);
                }
            } elseif (is_dir($itemPath) && $scanChildren) {
 
                $this->cleanupDirectory($itemPath, $maxAge, $now);

 
                $subItems    = scandir($itemPath);
                $subHasFiles = false;
                foreach ($subItems as $subItem) {
                    if ($subItem !== '.' && $subItem !== '..') {
                        $subHasFiles = true;
                        break;
                    }
                }

 
                if (!$subHasFiles) {
                    @rmdir($itemPath);
                } else {
                    $hasFiles = true; 
                }
            }
        }

 
        if (!$hasFiles && $scanChildren) {
            @rmdir($directory);
        }
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
 
            $logFile = sanitize_file_name(sprintf('wpstg_debug_%s.log', strtolower(wp_hash(__FILE__))));

            define('WPSTG_DEBUG_LOG_FILE', $logsDirectory . $logFile);
        }
    }




    private function initCron()
    {
 
        new Cron();
    }






    public static function getWPpath()
    {
        return str_replace('/', DIRECTORY_SEPARATOR, ABSPATH);
    }






    public static function getTablePrefix()
    {
 
        $db = WPStaging::getInstance()->getVar("database");
        return $db->getPrefix();
    }






    public static function getTableBasePrefix()
    {
        $db = WPStaging::getInstance()->get("wpdb");
        if (self::isWindowsOs()) {
            return strtolower($db->base_prefix);
        }

        return $db->base_prefix;
    }





    public static function setUseBaseContainerSingleton(bool $useBaseContainerSingleton)
    {
        static::$useBaseContainerSingleton = $useBaseContainerSingleton;
    }






    public static function getContentDir()
    {
        $wp_upload_dir = wp_upload_dir();
        $path          = $wp_upload_dir['basedir'] . '/wp-staging';
        (new Filesystem())->mkdir($path);

        return Hooks::applyFilters(Directory::FILTER_GET_UPLOAD_DIR, $path . '/');
    }






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





    public function resetContainer()
    {
        if (php_sapi_name() == "cli") {
            $this->isBootstrapped = false;
            $this->container      = new Container(false, static::$useBaseContainerSingleton);
        }
    }






    public static function isWindowsOs()
    {
        return strncasecmp(PHP_OS, 'WIN', 3) === 0;
    }






    public static function isMacOs()
    {
        return strpos(strtolower(PHP_OS), 'darwin') !== false;
    }




    private function loadDependencies()
    {
        if (!WPStaging::isWordPressLoaded()) {
            return;
        }

 
        require_once(__DIR__ . "/Utils/functions.php");

        $cache = WPStaging::make(Cache::class);
        $cache->setLifetime(-1); 
        $cache->setPath(WPStaging::getContentDir());
        $this->set("cache", $cache);

        $this->set("logger", new Logger());

        $this->loadLanguages();

 
        if (is_admin()) {
            new Administrator();
            return;
        }

        if (class_exists('\WPStaging\Pro\Frontend\Frontend')) {
            $this->container->get(\WPStaging\Pro\Frontend\Frontend::class);
        } else {
            $this->container->get(Frontend::class);
        }
    }

    private function loadLanguages()
    {
        (new Language())->load();
    }











    public function set($name, $variable)
    {
        return $this->setVar($name, $variable);
    }








    public function setVar(string $name, $variable)
    {
        $this->container->setVar($name, $variable);

        return $this;
    }










    public function get($name)
    {
        return $this->container->_get($name);
    }








    public function getVar(string $name, $default = null)
    {
        return $this->container->getVar($name, $default);
    }












    public static function make($id)
    {
        static $container;

        if ($container === null) {
            $container = self::getInstance()->getContainer();
        }

        return $container->get($id);
    }










    public function _make($name)
    {
        return $this->container->make($name);
    }












    public function getContainer()
    {
        return $this->container;
    }




    public static function getVersion()
    {
        if (WPStaging::isDevBasic()) {
            // @phpstan-ignore-next-line
            return WPSTG_DEV_BASIC; 
        }

        if (self::isPro()) {
            return WPSTGPRO_VERSION;
        }

        return WPSTG_VERSION;
    }




    public static function isWordPressLoaded()
    {
        return defined('ABSPATH') && function_exists('wp');
    }





    public static function isPro()
    {
        return !self::isBasic();
    }




    public static function silenceLogs($silence = true)
    {
        WPStaging::getInstance()->setVar('SILENCE_LOGS', $silence);
    }




    public static function areLogsSilenced()
    {
        try {
            return WPStaging::getInstance()->getVar('SILENCE_LOGS', false);
        } catch (Exception $ex) {
            return false;
        }
    }




    private function cloneSiteFirstRun()
    {
        (new FirstRun())->init();
    }




    private function handleCacheIssues()
    {
        $permalinksPurge = new PermalinksPurge();
        add_action('wp_loaded', [$permalinksPurge, 'purgePermalinks'], $permalinksPurge::PLUGINS_LOADED_PRIORITY);
    }




    private function preventDirectoryListing()
    {
 
        if (is_admin() && !(new WpAdapter())->doingAjax()) {
 
            $directoryListing = $this->getContainer()->get(DirectoryListing::class);
            $directoryListing->protectPluginUploadDirectory();
        }
    }









    public static function isDevBasic()
    {
        return defined('WPSTG_DEV_BASIC') && is_string(WPSTG_DEV_BASIC);
    }




    public static function isBasic()
    {
        return WPStaging::getInstance()->getVar('WPSTG_BASIC', true) === true;
    }




    public static function isOnWordPressPlayground(): bool
    {
        return ( ABSPATH === '/wordpress/' && defined('WP_HOME') && strpos(WP_HOME, '/scope:') && ! empty($_SERVER['SERVER_SOFTWARE']) && $_SERVER['SERVER_SOFTWARE'] === 'PHP.wasm' );
    }
}
