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




    const STAGING_SITE_DIRECTORY = 'wp-staging-sites';





    const TMP_PLUGINS_DIRECTORY = 'wpstg-tmp-plugins';





    const TMP_THEMES_DIRECTORY = 'wpstg-tmp-themes';

 
    const FILTER_CACHE_DIRECTORY = 'wpstg.directory.cacheDirectory';

 
    const FILTER_PLUGIN_UPLOADS_DIRECTORY = 'wpstg.directory.pluginUploadsDirectory';

 
    const FILTER_PLUGIN_WP_CONTENT_DIRECTORY = 'wpstg.directory.pluginWpContentDirectory';

 
    const FILTER_CLONE_EXCLUDED_FOLDERS = 'wpstg_clone_excl_folders';

 
    const FILTER_CLONE_MU_EXCLUDED_FOLDERS = 'wpstg_clone_mu_excl_folders';

 
    const FILTER_GET_UPLOAD_DIR = 'wpstg_get_upload_dir';

 
    const DEFAULT_MAX_FILE_SIZE_MB = 8;

 
    protected $uploadDir;

 
    protected $cacheDirectory;

 
    protected $tmpDirectory;

 
    protected $logDirectory;

 
    protected $backupDirectory;

 
    protected $pluginUploadsDirectory;

 
    protected $pluginWpContentDirectory;

 
    protected $pluginsDir;

 
    protected $muPluginsDir;

 
    protected $themesDirs;

 
    protected $activeThemeParentDir;

 
    protected $defaultWordPressFolders;

 
    protected $wpContentDirectory;

 
    protected $wpIncludesDirectory;

 
    protected $wpAdminDirectory;

 
    protected $langDir;

 
    protected $absPath;

 
    protected $mainSiteUploadsDir;

 
    protected $filesystem;

 
    protected $strUtils;

 
    protected $downloadsDirectory;

 
    protected $sseCacheDirectory = '';




    private $stagingSiteUrl;




    private $urls;




    private $errors = [];






    public function __construct(Filesystem $filesystem, Strings $strings, Urls $urls)
    {
        $this->filesystem = $filesystem;
        $this->strUtils   = $strings;
        $this->urls       = $urls;
    }




    public function clearErrors()
    {
        $this->errors = [];
    }




    public function getErrors(): array
    {
        return $this->errors;
    }









    public function getStagingSiteDirectoryInsideWpcontent(bool $createDir = true)
    {
 
        $baseDir              = WP_CONTENT_DIR;
        $this->stagingSiteUrl = trailingslashit(WP_CONTENT_URL) . self::STAGING_SITE_DIRECTORY;

 
        if (!is_writable($baseDir)) {
            $baseDir              = $this->getUploadsDirectory();
            $this->stagingSiteUrl = trailingslashit($this->urls->getUploadsUrl()) . self::STAGING_SITE_DIRECTORY;
        }

 
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




    public function getStagingSiteUrl(): string
    {
        return $this->stagingSiteUrl;
    }





    public function getCacheDirectory(): string
    {
        if (isset($this->cacheDirectory)) {
            return $this->cacheDirectory;
        }

        $cachePath = Hooks::applyFilters(self::FILTER_CACHE_DIRECTORY, wp_normalize_path($this->getPluginUploadsDirectory() . 'cache'));

        $this->cacheDirectory = trailingslashit($cachePath);

        return $this->cacheDirectory;
    }





    public function getTmpDirectory(): string
    {
        if (isset($this->tmpDirectory)) {
            return $this->tmpDirectory;
        }

        $this->tmpDirectory = trailingslashit(wp_normalize_path($this->getPluginWpContentDirectory() . JobRestore::TMP_DIRECTORY));

        try {
 
 
            $parentTmpDir = trailingslashit($this->getPluginWpContentDirectory() . 'tmp');
            $this->ensureDirectoryPermissions($parentTmpDir);

 
            if (!file_exists($this->tmpDirectory)) {
                wp_mkdir_p($this->tmpDirectory);
            }

 
            $this->ensureDirectoryPermissions($this->tmpDirectory);

 
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




    public function getLogDirectory(): string
    {
        if (isset($this->logDirectory)) {
            return $this->logDirectory;
        }

        $this->logDirectory = trailingslashit(wp_normalize_path($this->getPluginUploadsDirectory() . 'logs'));

        return $this->logDirectory;
    }




    public function getSseCacheDirectory(): string
    {
        if (!empty($this->sseCacheDirectory)) {
            return $this->sseCacheDirectory;
        }

        $this->sseCacheDirectory = trailingslashit(wp_normalize_path($this->getPluginWpContentDirectory() . 'sse'));

        return $this->sseCacheDirectory;
    }




    public function getBackupDirectory(): string
    {
        if (isset($this->backupDirectory)) {
            return $this->backupDirectory;
        }

        $this->backupDirectory = trailingslashit(wp_normalize_path($this->getPluginUploadsDirectory() . Archiver::BACKUP_DIR_NAME));

        return $this->backupDirectory;
    }





    public function getPluginUploadsDirectory(bool $refresh = false): string
    {
        if (isset($this->pluginUploadsDirectory) && !$refresh) {
            return $this->pluginUploadsDirectory;
        }

 
        $pluginUploadsDir = Hooks::applyFilters(self::FILTER_GET_UPLOAD_DIR, wp_normalize_path($this->getUploadsDirectory($refresh) . WPSTG_PLUGIN_DOMAIN));
        $pluginUploadsDir = Hooks::applyFilters(self::FILTER_PLUGIN_UPLOADS_DIRECTORY, $pluginUploadsDir);

        $this->pluginUploadsDirectory = trailingslashit($pluginUploadsDir);

        return $this->pluginUploadsDirectory;
    }







    public function getDefaultExcludedDirectories(bool $applyFilter = true): array
    {
        $backupUploadsDirPostfix = '.wpstg_backup';

        $dirs = array_merge($this->getWpStagingDataDirectories(), [
            trailingslashit(WP_CONTENT_DIR) . 'cache',
            untrailingslashit($this->getUploadsDirectory()) . $backupUploadsDirPostfix,
            trailingslashit(WP_CONTENT_DIR) . 'uploads' . $backupUploadsDirPostfix,
            trailingslashit(WP_CONTENT_DIR) . 'ai1wm-backups',
            $this->getUploadsDirectory() . 'wio_backup',
            $this->getStagingSiteDirectoryInsideWpcontent(false),
        ]);

        if (!$applyFilter) {
            return $dirs;
        }

        $dirs = (array) apply_filters(self::FILTER_CLONE_EXCLUDED_FOLDERS, $dirs);

        return $this->ensureWpStagingDataDirectoriesExcluded($dirs);
    }






    public function getWpStagingDataDirectories(): array
    {
        return [
            $this->getPluginWpContentDirectory(),
            $this->getPluginUploadsDirectory(),
        ];
    }





    public function ensureWpStagingDataDirectoriesExcluded(array $directories): array
    {
        return array_values(array_unique(array_merge($directories, $this->getWpStagingDataDirectories())));
    }








    public function getExcludedFileExtensions(array $additionalExtensions = []): array
    {
        return array_merge($additionalExtensions, ['log', 'tmp', 'wpstg']);
    }




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






    public function getUploadsDirectory(bool $refresh = false): string
    {
        if ($this->uploadDir && !$refresh) {
            return $this->uploadDir;
        }

 
 
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

 
            $baseUploadsFolder = trailingslashit($this->getWpContentDirectory() . 'uploads');
            if (!in_array($baseUploadsFolder, $this->defaultWordPressFolders)) {
                $this->defaultWordPressFolders[] = $baseUploadsFolder;
            }
        }

        return $this->defaultWordPressFolders;
    }




    public function getPluginsDirectory(): string
    {
        if (!isset($this->pluginsDir)) {
            $this->pluginsDir = $this->filesystem->normalizePath(WP_PLUGIN_DIR, true);
        }

        return $this->pluginsDir;
    }




    public function getMuPluginsDirectory(): string
    {
        if (!isset($this->muPluginsDir)) {
            $this->muPluginsDir = $this->filesystem->normalizePath(WPMU_PLUGIN_DIR, true);
        }

        return $this->muPluginsDir;
    }






    public function getRelativePluginsDirectory(): string
    {
        return str_replace($this->getAbsPath(), '', $this->getPluginsDirectory());
    }






    public function getRelativeMuPluginsDirectory(): string
    {
        return str_replace($this->getAbsPath(), '', $this->getMuPluginsDirectory());
    }




    public function getPluginsTmpDirectory(): string
    {
        return $this->getWpContentDirectory() . trailingslashit(self::TMP_PLUGINS_DIRECTORY);
    }




    public function getThemesTmpDirectory(): string
    {
        return $this->getWpContentDirectory() . trailingslashit(self::TMP_THEMES_DIRECTORY);
    }






    public function getAllThemesDirectories(): array
    {
        if (!isset($this->themesDirs)) {
            $this->themesDirs = array_map(function ($directory) {
                return $this->filesystem->normalizePath($directory['theme_root'], true);
            }, search_theme_directories(true) ?: []);

            if (!is_array($this->themesDirs)) {
                throw new \RuntimeException('Could not get the themes directories.');
            }















            $this->themesDirs = array_unique($this->themesDirs);
            $this->themesDirs = array_values($this->themesDirs);
        }

        return $this->themesDirs;
    }




    public function getActiveThemeParentDirectory(): string
    {
        if (!isset($this->activeThemeParentDir)) {
            $this->activeThemeParentDir = $this->filesystem->normalizePath(get_theme_root(get_template()), true);
        }

        return $this->activeThemeParentDir;
    }




    public function getLangsDirectory(): string
    {
        if (!isset($this->langDir)) {
            $this->langDir = $this->filesystem->normalizePath(WP_LANG_DIR, true);
        }

        return $this->langDir;
    }




    public function getAbsPath(): string
    {
        if (!isset($this->absPath)) {
            $this->absPath = $this->filesystem->normalizePath(ABSPATH, true);
        }

        return $this->absPath;
    }




    public function getWpContentDirectory(): string
    {
        if (!isset($this->wpContentDirectory)) {
            $this->wpContentDirectory = $this->filesystem->normalizePath(WP_CONTENT_DIR, true);
        }

        return $this->wpContentDirectory;
    }




    public function getWpIncludesDirectory(): string
    {
        if (!isset($this->wpIncludesDirectory)) {
            $this->wpIncludesDirectory = trailingslashit($this->getAbsPath()) . 'wp-includes/';
        }

        return $this->wpIncludesDirectory;
    }




    public function getWpAdminDirectory(): string
    {
        if (!isset($this->wpAdminDirectory)) {
            $this->wpAdminDirectory = trailingslashit($this->getAbsPath()) . 'wp-admin/';
        }

        return $this->wpAdminDirectory;
    }




    public function getWpDefaultRootDirectories(): array
    {
        return [
            $this->getWpAdminDirectory(),
            $this->getWpContentDirectory(),
            $this->getWpIncludesDirectory(),
        ];
    }




    public function getWpStagingRestoreDirs(): array
    {
        return [
            $this->getAbsPath() . 'wpstg-restore/',
            $this->getAbsPath() . 'wpstg-extract/',
        ];
    }








    public function isPathInWpRoot(string $path): bool
    {
        $path = $this->filesystem->normalizePath($path);
        $path = $this->getAbsPath() . str_replace($this->getAbsPath(), '', $path);
        return file_exists($path);
    }




    public function getFileSystem(): Filesystem
    {
        return $this->filesystem;
    }




    public function getDownloadsDirectory(): string
    {
        if (isset($this->downloadsDirectory)) {
            return $this->downloadsDirectory;
        }

        $this->downloadsDirectory = trailingslashit(wp_normalize_path($this->getPluginUploadsDirectory() . 'tmp/downloads'));
        wp_mkdir_p($this->downloadsDirectory);

        return $this->downloadsDirectory;
    }





    public function isBackupPathOutsideAbspath(): bool
    {
        $defaultBackupDirAbsPath = $this->getPluginUploadsDirectory() . Archiver::BACKUP_DIR_NAME;
        $absPath                 = $this->getAbsPath();

        return $absPath !== substr($defaultBackupDirAbsPath, 0, strlen($absPath));
    }










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
 
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

 
            foreach ($iterator as $file) {
                try {
                    $totalBytes += $file->getSize();
                } catch (Exception $e) { 
                    $this->errors[] = "{$file} is a symbolic link or for some reason its size is invalid";
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $totalBytes;
    }











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








    private function ensureDirectoryPermissions(string $directory)
    {
 
        if (!file_exists($directory)) {
            wp_mkdir_p($directory);
        }

 
 
        if (is_readable($directory) && is_writable($directory)) {
            return;
        }

 
        $dirPermissions = defined('FS_CHMOD_DIR') ? FS_CHMOD_DIR : Permissions::DEFAULT_DIR_PERMISSION;

 
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
