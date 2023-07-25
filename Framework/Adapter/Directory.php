<?php

namespace WPStaging\Framework\Adapter;

use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Backup\Job\Jobs\JobRestore;
use WPStaging\Backup\Service\Compressor;

class Directory
{
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

    public function __construct(Filesystem $filesystem, Strings $strings)
    {
        $this->filesystem = $filesystem;
        $this->strUtils   = $strings;
    }

    /**
     * @noinspection PhpUnused
     * @return string
     */
    public function getCacheDirectory()
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
    public function getTmpDirectory()
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
    public function getLogDirectory()
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
    public function getBackupDirectory()
    {
        if (isset($this->backupDirectory)) {
            return $this->backupDirectory;
        }

        $this->backupDirectory = trailingslashit(wp_normalize_path($this->getPluginUploadsDirectory() . Compressor::BACKUP_DIR_NAME));

        return $this->backupDirectory;
    }

    /**
     * @return string
     */
    public function getPluginUploadsDirectory()
    {
        if (isset($this->pluginUploadsDirectory)) {
            return $this->pluginUploadsDirectory;
        }

        /** This is deprecated filter and its value should always be replaced by newer filter */
        $pluginUploadsDir = apply_filters('wpstg_get_upload_dir', wp_normalize_path($this->getUploadsDirectory() . WPSTG_PLUGIN_DOMAIN));
        $pluginUploadsDir = apply_filters('wpstg.directory.pluginUploadsDirectory', $pluginUploadsDir);

        $this->pluginUploadsDirectory = trailingslashit($pluginUploadsDir);

        return $this->pluginUploadsDirectory;
    }

    /**
     * Absolute Path
     * @return string
     */
    public function getUploadsDirectory()
    {
        if ($this->uploadDir) {
            return $this->uploadDir;
        }

        // Get absolute path to wordpress uploads directory e.g /var/www/wp-content/uploads/
        // Default is ABSPATH . 'wp-content/uploads', but it can be customized by the db option upload_path or the constant UPLOADS
        $uploadDir = wp_upload_dir(null, false, false)['basedir'];

        $this->uploadDir = trim(trailingslashit(wp_normalize_path($uploadDir)));

        return $this->uploadDir;
    }

    /**
     * If multisite, return the main site uploads directory
     * If single site, return the uploads directory
     * @return string
     */
    public function getMainSiteUploadsDirectory()
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
    public function getDefaultWordPressFolders()
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
    public function getPluginsDirectory()
    {
        if (!isset($this->pluginsDir)) {
            $this->pluginsDir = $this->filesystem->normalizePath(WP_PLUGIN_DIR, true);
        }

        return $this->pluginsDir;
    }

    /**
     * @return string
     */
    public function getMuPluginsDirectory()
    {
        if (!isset($this->muPluginsDir)) {
            $this->muPluginsDir = $this->filesystem->normalizePath(WPMU_PLUGIN_DIR, true);
        }

        return $this->muPluginsDir;
    }

    /**
     * @throws \RuntimeException
     *
     * @return array
     */
    public function getAllThemesDirectories()
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
    public function getActiveThemeParentDirectory()
    {
        if (!isset($this->activeThemeParentDir)) {
            $this->activeThemeParentDir = $this->filesystem->normalizePath(get_theme_root(get_template()), true);
        }

        return $this->activeThemeParentDir;
    }

    /**
     * @return string
     */
    public function getLangsDirectory()
    {
        if (!isset($this->langDir)) {
            $this->langDir = $this->filesystem->normalizePath(WP_LANG_DIR, true);
        }

        return $this->langDir;
    }

    /**
     * @return string
     */
    public function getAbsPath()
    {
        if (!isset($this->absPath)) {
            $this->absPath = $this->filesystem->normalizePath(ABSPATH, true);
        }

        return $this->absPath;
    }

    /**
     * @return string
     */
    public function getWpContentDirectory()
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
    public function isPathInWpRoot($path)
    {
        $path = $this->filesystem->normalizePath($path);
        $path = $this->getAbsPath() . str_replace($this->getAbsPath(), '', $path);
        return file_exists($path);
    }

    /**
     * @return Filesystem
     */
    public function getFileSystem()
    {
        return $this->filesystem;
    }

    /**
    * Return true if the default backup paths has been changed by a filter and is outside abspath
    * @return bool
    */
    public function isBackupPathOutsideAbspath()
    {
        $defaultBackupDirAbsPath = $this->getPluginUploadsDirectory() . Compressor::BACKUP_DIR_NAME;
        $absPath                 = $this->getAbsPath();

        return $absPath !== substr($defaultBackupDirAbsPath, 0, strlen($absPath));
    }
}
