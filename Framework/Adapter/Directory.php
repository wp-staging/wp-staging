<?php

namespace WPStaging\Framework\Adapter;

use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Backup\Job\Jobs\JobRestore;
use WPStaging\Backup\Service\Compressor;
use WPStaging\Framework\Utils\Urls;

class Directory
{
    /**
     * Staging site directory name, used when ABSPATH is not writeable
     * @var string
     */
    const STAGING_SITE_DIRECTORY = 'wp-staging-sites';

    /**
     * Used during PUSH, to avoid conflicts with existing plugins
     * @var string
     */
    const TMP_PLUGINS_DIRECTORY = 'wpstg-tmp-plugins';

    /**
     * Used during PUSH, to avoid conflicts with existing themes
     * @var string
     */
    const TMP_THEMES_DIRECTORY = 'wpstg-tmp-themes';

    /** @var string The directory that holds the uploads, usually wp-content/uploads */
    protected $uploadDir;

    /** @var string The directory that holds the WP STAGING cache directory, usually wp-content/uploads/wp-staging/cache */
    protected $cacheDirectory;

    /** @var string The directory that holds the WP STAGING backup tmp directory, usually wp-content/uploads/wp-staging/tmp/import */
    protected $tmpDirectory;

    /** @var string The directory that holds the WP STAGING logs directory, usually wp-content/uploads/wp-staging/logs */
    protected $logDirectory;

    /** @var string The directory that holds the WP STAGING backup directory, usually wp-content/uploads/wp-staging/backups */
    protected $backupDirectory;

    /** @var string The directory that holds the WP STAGING data directory, usually wp-content/uploads/wp-staging */
    protected $pluginUploadsDirectory;

    /** @var string The directory that holds the plugins, usually wp-content/plugins */
    protected $pluginsDir;

    /** @var string The directory that holds the mu-plugins, usually wp-content/mu-plugins */
    protected $muPluginsDir;

    /** @var array An array of directories that holds themes, usually ['wp-content/themes'] */
    protected $themesDirs;

    /** @var string The directory that holds the currently active theme, usually wp-content/themes */
    protected $activeThemeParentDir;

    /** @var array An array of default directories, such as ['wp-content/themes/', 'wp-content/plugins/', 'wp-content/mu-plugins/', 'wp-content/uploads/'] */
    protected $defaultWordPressFolders;

    /** @var string The directory that points to the wp-content folder, usually wp-content/ */
    protected $wpContentDirectory;

    /** @var string The directory that points to the languages folder, usually wp-content/languages/ */
    protected $langDir;

    /** @var string The directory that points to the ABSPATH folder */
    protected $absPath;

    /** @var string The directory that points to main site uploads folder, usually wp-content/uploads */
    protected $mainSiteUploadsDir;

    /** @var Filesystem */
    protected $filesystem;

    /** @var Strings */
    protected $strUtils;

    /**
     * @var string
     */
    private $stagingSiteUrl;

    /**
     * @var Urls
     */
    private $urls;

    /**
     * @param Filesystem $filesystem
     * @param Strings $strings
     * @param Urls $urls
     */
    public function __construct(Filesystem $filesystem, Strings $strings, Urls $urls)
    {
        $this->filesystem = $filesystem;
        $this->strUtils   = $strings;
        $this->urls       = $urls;
    }

    /**
     * Try to get a staging site main directory inside:
     * wp-content/wp-staging-sites or wp-content/uploads/wp-staging-sites
     *
     * @param bool $createDir if true create dir if it does not exist
     *
     * @return bool|string false if dir is not writable otherwise return the full path of staging sites dir.
     */
    public function getStagingSiteDirectoryInsideWpcontent(bool $createDir = true)
    {
        // Try wp-content/wp-staging-sites
        $baseDir              = WP_CONTENT_DIR;
        $this->stagingSiteUrl = trailingslashit(WP_CONTENT_URL) . self::STAGING_SITE_DIRECTORY;

        // wp-content/wp-staging-sites is not writeable. Try wp-content/uploads/wp-staging-sites
        if (!is_writable($baseDir)) {
            $baseDir              = $this->getUploadsDirectory();
            $this->stagingSiteUrl = trailingslashit($this->urls->getUploadsUrl()) . self::STAGING_SITE_DIRECTORY;
        }

        // wp-content/uploads/wp-staging-sites is not writeable as well
        if (!is_writable($baseDir)) {
            $this->stagingSiteUrl = '';
            return false;
        }

        $stagingSiteDir = trailingslashit($baseDir) . self::STAGING_SITE_DIRECTORY;
        if ($createDir && !is_dir($stagingSiteDir)) {
            wp_mkdir_p($stagingSiteDir);
        }

        return $stagingSiteDir;
    }

    /**
     * @return string
     */
    public function getStagingSiteUrl(): string
    {
        return $this->stagingSiteUrl;
    }

    /**
     * @noinspection PhpUnused
     * @return string
     */
    public function getCacheDirectory(): string
    {
        if (isset($this->cacheDirectory)) {
            return $this->cacheDirectory;
        }

        $cachePath = apply_filters('wpstg.directory.cacheDirectory', wp_normalize_path($this->getPluginUploadsDirectory() . 'cache'));

        $this->cacheDirectory = trailingslashit($cachePath);

        return $this->cacheDirectory;
    }

    /**
     * @return string
     */
    public function getTmpDirectory(): string
    {
        if (isset($this->tmpDirectory)) {
            return $this->tmpDirectory;
        }

        $this->tmpDirectory = trailingslashit(wp_normalize_path($this->getPluginUploadsDirectory() . JobRestore::TMP_DIRECTORY));

        wp_mkdir_p($this->tmpDirectory);

        return $this->tmpDirectory;
    }

    /**
     * @return string
     */
    public function getLogDirectory(): string
    {
        if (isset($this->logDirectory)) {
            return $this->logDirectory;
        }

        $this->logDirectory = trailingslashit(wp_normalize_path($this->getPluginUploadsDirectory() . 'logs'));

        return $this->logDirectory;
    }

    /**
     * @return string
     */
    public function getBackupDirectory(): string
    {
        if (isset($this->backupDirectory)) {
            return $this->backupDirectory;
        }

        $this->backupDirectory = trailingslashit(wp_normalize_path($this->getPluginUploadsDirectory() . Compressor::BACKUP_DIR_NAME));

        return $this->backupDirectory;
    }

    /**
     * @param bool $refresh
     * @return string
     */
    public function getPluginUploadsDirectory(bool $refresh = false): string
    {
        if (isset($this->pluginUploadsDirectory) && !$refresh) {
            return $this->pluginUploadsDirectory;
        }

        /** This is deprecated filter and its value should always be replaced by newer filter */
        $pluginUploadsDir = apply_filters('wpstg_get_upload_dir', wp_normalize_path($this->getUploadsDirectory($refresh) . WPSTG_PLUGIN_DOMAIN));
        $pluginUploadsDir = apply_filters('wpstg.directory.pluginUploadsDirectory', $pluginUploadsDir);

        $this->pluginUploadsDirectory = trailingslashit($pluginUploadsDir);

        return $this->pluginUploadsDirectory;
    }

    /**
     * Absolute Path to Upload URL of current single site / network site
     * @param bool $refresh
     * @return string
     */
    public function getUploadsDirectory(bool $refresh = false): string
    {
        if ($this->uploadDir && !$refresh) {
            return $this->uploadDir;
        }

        // Get absolute path to wordpress uploads directory e.g /var/www/wp-content/uploads/
        // Default is ABSPATH . 'wp-content/uploads', but it can be customized by the db option upload_path or the constant UPLOADS
        $uploadDir = wp_upload_dir(null, false)['basedir'];

        $this->uploadDir = trim(trailingslashit(wp_normalize_path($uploadDir)));

        return $this->uploadDir;
    }

    /**
     * If multisite, return the main site uploads directory
     * If single site, return the uploads directory
     * @return string
     */
    public function getMainSiteUploadsDirectory(): string
    {
        if (isset($this->mainSiteUploadsDir)) {
            return $this->mainSiteUploadsDir;
        }

        $uploadsDir = $this->getUploadsDirectory();
        if (!is_multisite() || is_main_site()) {
            $this->mainSiteUploadsDir = $uploadsDir;

            return $this->mainSiteUploadsDir;
        }

        switch_to_blog(1);
        $uploadDir = wp_upload_dir(null, false, true)['basedir'];
        $this->mainSiteUploadsDir = trim(trailingslashit(wp_normalize_path($uploadDir)));
        restore_current_blog();

        return $this->mainSiteUploadsDir;
    }

    /**
     * @return array
     */
    public function getDefaultWordPressFolders(): array
    {
        if (!isset($this->defaultWordPressFolders)) {
            $this->defaultWordPressFolders = array_merge(
                [
                    $this->getPluginsDirectory(),
                    $this->getMuPluginsDirectory(),
                    $this->getUploadsDirectory(),
                ],
                $this->getAllThemesDirectories()
            );

            if (!in_array($this->getMainSiteUploadsDirectory(), $this->defaultWordPressFolders)) {
                $this->defaultWordPressFolders[] = $this->getMainSiteUploadsDirectory();
            }

            // For edge cases when actual uploads is within wp-content/uploads/some-uploads-dir i.e. subsites clones
            $baseUploadsFolder = trailingslashit($this->getWpContentDirectory() . 'uploads');
            if (!in_array($baseUploadsFolder, $this->defaultWordPressFolders)) {
                $this->defaultWordPressFolders[] = $baseUploadsFolder;
            }
        }

        return $this->defaultWordPressFolders;
    }

    /**
     * @return string
     */
    public function getPluginsDirectory(): string
    {
        if (!isset($this->pluginsDir)) {
            $this->pluginsDir = $this->filesystem->normalizePath(WP_PLUGIN_DIR, true);
        }

        return $this->pluginsDir;
    }

    /**
     * @return string
     */
    public function getMuPluginsDirectory(): string
    {
        if (!isset($this->muPluginsDir)) {
            $this->muPluginsDir = $this->filesystem->normalizePath(WPMU_PLUGIN_DIR, true);
        }

        return $this->muPluginsDir;
    }

    /**
     * @return string
     */
    public function getPluginsTmpDirectory(): string
    {
        return $this->getWpContentDirectory() . trailingslashit(self::TMP_PLUGINS_DIRECTORY);
    }

    /**
     * @return string
     */
    public function getThemesTmpDirectory(): string
    {
        return $this->getWpContentDirectory() . trailingslashit(self::TMP_THEMES_DIRECTORY);
    }

    /**
     * @throws \RuntimeException
     *
     * @return array
     */
    public function getAllThemesDirectories(): array
    {
        if (!isset($this->themesDirs)) {
            $this->themesDirs = array_map(function ($directory) {
                return $this->filesystem->normalizePath($directory['theme_root'], true);
            }, search_theme_directories(true));

            if (!is_array($this->themesDirs)) {
                throw new \RuntimeException('Could not get the themes directories.');
            }

            /*
             * [
             *  'foo' => '/var/www/single/wp-content/themes',
             *  'bar' => '/var/www/single/wp-content/themes',
             *  'baz' => '/var/themes'
             * ]
             *
             * Becomes:
             *
             * [
             *  '/var/www/single/wp-content/themes',
             *  '/var/themes',
             * ]
             */
            $this->themesDirs = array_unique($this->themesDirs);
            $this->themesDirs = array_values($this->themesDirs);
        }

        return $this->themesDirs;
    }

    /**
     * @return string
     */
    public function getActiveThemeParentDirectory(): string
    {
        if (!isset($this->activeThemeParentDir)) {
            $this->activeThemeParentDir = $this->filesystem->normalizePath(get_theme_root(get_template()), true);
        }

        return $this->activeThemeParentDir;
    }

    /**
     * @return string
     */
    public function getLangsDirectory(): string
    {
        if (!isset($this->langDir)) {
            $this->langDir = $this->filesystem->normalizePath(WP_LANG_DIR, true);
        }

        return $this->langDir;
    }

    /**
     * @return string
     */
    public function getAbsPath(): string
    {
        if (!isset($this->absPath)) {
            $this->absPath = $this->filesystem->normalizePath(ABSPATH, true);
        }

        return $this->absPath;
    }

    /**
     * @return string
     */
    public function getWpContentDirectory(): string
    {
        if (!isset($this->wpContentDirectory)) {
            $this->wpContentDirectory = $this->filesystem->normalizePath(WP_CONTENT_DIR, true);
        }

        return $this->wpContentDirectory;
    }

    /**
     * Check whether the given path exists in WordPress Root,
     * Method will return true if exists in WordPress Root or is relative to WordPress root.
     *
     * @param string $path
     * @return bool
     */
    public function isPathInWpRoot(string $path): bool
    {
        $path = $this->filesystem->normalizePath($path);
        $path = $this->getAbsPath() . str_replace($this->getAbsPath(), '', $path);
        return file_exists($path);
    }

    /**
     * @return Filesystem
     */
    public function getFileSystem(): Filesystem
    {
        return $this->filesystem;
    }

    /**
    * Return true if the default backup paths has been changed by a filter and is outside abspath
    * @return bool
    */
    public function isBackupPathOutsideAbspath(): bool
    {
        $defaultBackupDirAbsPath = $this->getPluginUploadsDirectory() . Compressor::BACKUP_DIR_NAME;
        $absPath                 = $this->getAbsPath();

        return $absPath !== substr($defaultBackupDirAbsPath, 0, strlen($absPath));
    }
}
