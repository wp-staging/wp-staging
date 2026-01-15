<?php

namespace WPStaging\Framework\Adapter;

use Exception;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Backup\Job\Jobs\JobRestore;
use WPStaging\Backup\Service\Archiver;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\Permissions;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Framework\Utils\SlashMode;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;

class Directory implements DirectoryInterface
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

    /** @var string */
    const FILTER_CACHE_DIRECTORY = 'wpstg.directory.cacheDirectory';

    /** @var string */
    const FILTER_PLUGIN_UPLOADS_DIRECTORY = 'wpstg.directory.pluginUploadsDirectory';

    /** @var string */
    const FILTER_PLUGIN_WP_CONTENT_DIRECTORY = 'wpstg.directory.pluginWpContentDirectory';

    /** @var string */
    const FILTER_CLONE_EXCLUDED_FOLDERS = 'wpstg_clone_excl_folders';

    /** @var string */
    const FILTER_CLONE_MU_EXCLUDED_FOLDERS = 'wpstg_clone_mu_excl_folders';

    /** @var string */
    const FILTER_GET_UPLOAD_DIR = 'wpstg_get_upload_dir';

    /** @var string|null The directory that holds the uploads, usually wp-content/uploads */
    protected $uploadDir;

    /** @var string|null The directory that holds the WP STAGING cache directory, usually wp-content/uploads/wp-staging/cache */
    protected $cacheDirectory;

    /** @var string|null The directory that holds the WP STAGING backup tmp directory, usually wp-content/wp-staging/tmp/restore */
    protected $tmpDirectory;

    /** @var string|null The directory that holds the WP STAGING logs directory, usually wp-content/uploads/wp-staging/logs */
    protected $logDirectory;

    /** @var string|null The directory that holds the WP STAGING backup directory, usually wp-content/uploads/wp-staging/backups */
    protected $backupDirectory;

    /** @var string|null The directory that holds the WP STAGING data directory inside uploads folder, usually wp-content/uploads/wp-staging */
    protected $pluginUploadsDirectory;

    /** @var string|null The directory that holds the WP STAGING data directory directly inside wp-content, usually wp-content/wp-staging */
    protected $pluginWpContentDirectory;

    /** @var string|null The directory that holds the plugins, usually wp-content/plugins */
    protected $pluginsDir;

    /** @var string|null The directory that holds the mu-plugins, usually wp-content/mu-plugins */
    protected $muPluginsDir;

    /** @var array|null An array of directories that holds themes, usually ['wp-content/themes'] */
    protected $themesDirs;

    /** @var string|null The directory that holds the currently active theme, usually wp-content/themes */
    protected $activeThemeParentDir;

    /** @var array|null An array of default directories, such as ['wp-content/themes/', 'wp-content/plugins/', 'wp-content/mu-plugins/', 'wp-content/uploads/'] */
    protected $defaultWordPressFolders;

    /** @var string|null The directory that points to the wp-content folder, usually wp-content/ */
    protected $wpContentDirectory;

    /** @var string|null The directory that points to the wp-includes folder, usually wp-includes/ */
    protected $wpIncludesDirectory;

    /** @var string|null The directory that points to the wp-admin folder, usually wp-admin/ */
    protected $wpAdminDirectory;

    /** @var string|null The directory that points to the languages folder, usually wp-content/languages/ */
    protected $langDir;

    /** @var string|null The directory that points to the ABSPATH folder */
    protected $absPath;

    /** @var string|null The directory that points to main site uploads folder, usually wp-content/uploads */
    protected $mainSiteUploadsDir;

    /** @var Filesystem */
    protected $filesystem;

    /** @var Strings */
    protected $strUtils;

    /** @var string|null The directory that points to the temp directory for downloads */
    protected $downloadsDirectory;

    /** @var string The directory that stores the sse events cache used for background logging */
    protected $sseCacheDirectory = '';

    /**
     * @var string
     */
    private $stagingSiteUrl;

    /**
     * @var Urls
     */
    private $urls;

    /**
     * @var array
     */
    private $errors = [];

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
     * @return void
     */
    public function clearErrors()
    {
        $this->errors = [];
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
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

        $cachePath = Hooks::applyFilters(self::FILTER_CACHE_DIRECTORY, wp_normalize_path($this->getPluginUploadsDirectory() . 'cache'));

        $this->cacheDirectory = trailingslashit($cachePath);

        return $this->cacheDirectory;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getTmpDirectory(): string
    {
        if (isset($this->tmpDirectory)) {
            return $this->tmpDirectory;
        }

        $this->tmpDirectory = trailingslashit(wp_normalize_path($this->getPluginWpContentDirectory() . JobRestore::TMP_DIRECTORY));

        try {
            // Ensure parent tmp directory has correct permissions
            // The path is wp-content/wp-staging/tmp/restore/, so we need to fix wp-content/wp-staging/tmp/
            $parentTmpDir = trailingslashit($this->getPluginWpContentDirectory() . 'tmp');
            $this->ensureDirectoryPermissions($parentTmpDir);

            // Create directory if it doesn't exist
            if (!file_exists($this->tmpDirectory)) {
                wp_mkdir_p($this->tmpDirectory);
            }

            // Ensure the full tmp directory has correct permissions
            $this->ensureDirectoryPermissions($this->tmpDirectory);

            // Final validation
            if (!is_readable($this->tmpDirectory)) {
                throw new Exception(sprintf('Temporary directory is not readable: %s', $this->tmpDirectory));
            }

            if (!is_writable($this->tmpDirectory)) {
                throw new Exception(sprintf('Temporary directory is not writable: %s', $this->tmpDirectory));
            }
        } catch (Exception $e) {
            throw new Exception(sprintf('Failed to create or access temporary directory: %s - %s', $this->tmpDirectory, $e->getMessage()));
        }

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
    public function getSseCacheDirectory(): string
    {
        if (!empty($this->sseCacheDirectory)) {
            return $this->sseCacheDirectory;
        }

        $this->sseCacheDirectory = trailingslashit(wp_normalize_path($this->getPluginWpContentDirectory() . 'sse'));

        return $this->sseCacheDirectory;
    }

    /**
     * @return string
     */
    public function getBackupDirectory(): string
    {
        if (isset($this->backupDirectory)) {
            return $this->backupDirectory;
        }

        $this->backupDirectory = trailingslashit(wp_normalize_path($this->getPluginUploadsDirectory() . Archiver::BACKUP_DIR_NAME));

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
        $pluginUploadsDir = Hooks::applyFilters(self::FILTER_GET_UPLOAD_DIR, wp_normalize_path($this->getUploadsDirectory($refresh) . WPSTG_PLUGIN_DOMAIN));
        $pluginUploadsDir = Hooks::applyFilters(self::FILTER_PLUGIN_UPLOADS_DIRECTORY, $pluginUploadsDir);

        $this->pluginUploadsDirectory = trailingslashit($pluginUploadsDir);

        return $this->pluginUploadsDirectory;
    }

    /**
     * @return string
     */
    public function getPluginWpContentDirectory(): string
    {
        if (isset($this->pluginWpContentDirectory)) {
            return $this->pluginWpContentDirectory;
        }

        $pluginWpContentDir = $this->getWpContentDirectory() . WPSTG_PLUGIN_DOMAIN;
        $pluginWpContentDir = Hooks::applyFilters(self::FILTER_PLUGIN_WP_CONTENT_DIRECTORY, $pluginWpContentDir);

        $this->pluginWpContentDirectory = trailingslashit($pluginWpContentDir);

        return $this->pluginWpContentDirectory;
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

    public function getRelativeUploadsDirectory(bool $refresh = false): string
    {
        if (!$this->uploadDir || $refresh) {
            $this->uploadDir = $this->getUploadsDirectory($refresh);
        }

        return str_replace($this->getAbsPath(), '', $this->uploadDir);
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
            }, search_theme_directories(true) ?: []);

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
     * @return string
     */
    public function getWpIncludesDirectory(): string
    {
        if (!isset($this->wpIncludesDirectory)) {
            $this->wpIncludesDirectory = trailingslashit($this->getAbsPath()) . 'wp-includes/';
        }

        return $this->wpIncludesDirectory;
    }

    /**
     * @return string
     */
    public function getWpAdminDirectory(): string
    {
        if (!isset($this->wpAdminDirectory)) {
            $this->wpAdminDirectory = trailingslashit($this->getAbsPath()) . 'wp-admin/';
        }

        return $this->wpAdminDirectory;
    }

    /**
     * @return array
     */
    public function getWpDefaultRootDirectories(): array
    {
        return [
            $this->getWpAdminDirectory(),
            $this->getWpContentDirectory(),
            $this->getWpIncludesDirectory(),
        ];
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
     * @return string
     */
    public function getDownloadsDirectory(): string
    {
        if (isset($this->downloadsDirectory)) {
            return $this->downloadsDirectory;
        }

        $this->downloadsDirectory = trailingslashit(wp_normalize_path($this->getPluginUploadsDirectory() . 'tmp/downloads'));
        wp_mkdir_p($this->downloadsDirectory);

        return $this->downloadsDirectory;
    }

    /**
    * Return true if the default backup paths has been changed by a filter and is outside abspath
    * @return bool
    */
    public function isBackupPathOutsideAbspath(): bool
    {
        $defaultBackupDirAbsPath = $this->getPluginUploadsDirectory() . Archiver::BACKUP_DIR_NAME;
        $absPath                 = $this->getAbsPath();

        return $absPath !== substr($defaultBackupDirAbsPath, 0, strlen($absPath));
    }


    /**
     * Get excluded directories and map it to array
     *
     * @param string $directoriesRequest
     * @param int $slashMode
     *
     * @return array
     */
    public function getExcludedDirectories(string $directoriesRequest, int $slashMode = SlashMode::NO_SLASH): array
    {
        if ((empty($directoriesRequest))) {
            return [];
        }

        $excludedDirectories = explode(ScanConst::DIRECTORIES_SEPARATOR, wpstg_urldecode($directoriesRequest));
        $excludedDirectories = array_map(function ($directory) use ($slashMode) {
            return $this->slashit($directory, $slashMode);
        }, $excludedDirectories);

        return $excludedDirectories;
    }

    /**
     * Get size of path
     * @param string $path
     * @return int
     * @throws Exception
     */
    public function getSize(string $path): int
    {
        $path = realpath($path);

        if ($path === false) {
            return 0;
        }

        if (is_file($path)) {
            return filesize($path);
        }

        if (!is_dir($path)) {
            return 0;
        }

        $totalBytes = 0;
        try {
            // Iterator
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            // Loop & add file size
            foreach ($iterator as $file) {
                try {
                    $totalBytes += $file->getSize();
                } catch (Exception $e) { // Some invalid symbolic links can cause issues in *nix systems
                    $this->errors[] = "{$file} is a symbolic link or for some reason its size is invalid";
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $totalBytes;
    }

    /**
     * Different slash mode for path
     * @param string $path
     * @param int $mode Optional. Slash Mode. Default SlashMode::NO_SLASH.
     *                      Use SlashMode::NO_SLASH, if you don't want trailing and leading slash.
     *                      Use SlashMode::TRAILING_SLASH, if you want trailing forward slash.
     *                      Use SlashMode::LEADING_SLASH, if you want leading forward slash.
     *                      Use SlashMode::BOTH_SLASHES, if you want both trailing and leading forward slash.
     * @return string
     */
    private function slashit(string $path, int $mode = SlashMode::NO_SLASH): string
    {
        $path = trim(trim($path, '\\'), '/');
        if ($mode === SlashMode::BOTH_SLASHES) {
            return '/' . $path . '/';
        }

        if ($mode === SlashMode::TRAILING_SLASH) {
            return $path . '/';
        }

        if ($mode === SlashMode::LEADING_SLASH) {
            return '/' . $path;
        }

        return $path;
    }

    /**
     * Ensures a directory exists and has correct read/write permissions
     *
     * @param string $directory The directory path to check and fix
     * @return void
     * @throws Exception If permissions cannot be set
     */
    private function ensureDirectoryPermissions(string $directory)
    {
        // Create directory if it doesn't exist
        if (!file_exists($directory)) {
            wp_mkdir_p($directory);
        }

        // Check and fix permissions if directory is not readable or writable
        // Return if permissions are already correct
        if (is_readable($directory) && is_writable($directory)) {
            return;
        }

        // Use WordPress directory permission constant, or fall back to 0755
        $dirPermissions = defined('FS_CHMOD_DIR') ? FS_CHMOD_DIR : Permissions::DEFAULT_DIR_PERMISSION;

        // Attempt to fix permissions
        if (!@chmod($directory, $dirPermissions)) {
            throw new Exception(
                sprintf(
                    'Failed to set permissions (%s) on directory: %s',
                    decoct($dirPermissions),
                    $directory
                )
            );
        }
    }
}
