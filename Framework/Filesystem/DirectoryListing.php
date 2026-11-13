<?php

namespace WPStaging\Framework\Filesystem;

use RuntimeException;
use WPStaging\Backup\Security\BackupDirectoryProtectionService;
use WPStaging\Framework\Notices\Notices;
use WPStaging\Core\Utils\Htaccess;
use WPStaging\Core\Utils\IISWebConfig;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Hooks;








class DirectoryListing
{
 
    const FILTER_DIRECTORY_LISTING_INTERVAL_CHECK = 'wpstg.directory_listing.interval_check';

 
    private $directory;

 
    private $htaccess;

 
    private $webConfig;

    public function __construct(Directory $directory, Htaccess $htaccess, IISWebConfig $webConfig)
    {
        $this->directory = $directory;
        $this->htaccess  = $htaccess;
        $this->webConfig = $webConfig;
    }




    public function isPathInOpenBaseDir($path): bool
    {
        $openBaseDirPaths = array_map(function ($input) {
            return trim($input);
        }, explode(":", ini_get('open_basedir')));

        // @phpstan-ignore-next-line
        if (empty($openBaseDirPaths) || empty($openBaseDirPaths[0])) {
            return true;
        }

        if (in_array($path, $openBaseDirPaths)) {
            return true;
        }

        foreach ($openBaseDirPaths as $openBaseDirPath) {
            if (strpos($path, trailingslashit($openBaseDirPath)) === 0 && !empty($openBaseDirPath)) {
                return true;
            }
        }

        return false;
    }




    public function protectPluginUploadDirectory()
    {
        $lastChecked = get_transient('wpstg.directory_listing.last_checked');
        $now         = current_time('timestamp');

        if (!empty($lastChecked)) {
            if (($now - $lastChecked) < $this->getInterval()) {
 
                return;
            }
        }

        set_transient('wpstg.directory_listing.last_checked', $now);

        try {
            $it = new \RecursiveDirectoryIterator($this->directory->getPluginUploadsDirectory());
            $it = new \RecursiveIteratorIterator($it);

            $dirsToProtect = [];

 
            foreach ($it as $item) {
                $realPath = $item->getRealPath();
                if (!$this->isPathInOpenBaseDir($realPath)) {
                    continue;
                }

                if ($item->isDir() && $item->getBasename() !== '..') {
                    $dirsToProtect[] = $realPath;
                }
            }

            $dirsToProtect = array_unique($dirsToProtect);

            foreach ($dirsToProtect as $dir) {
                try {
                    $this->preventDirectoryListing($dir);
                    $this->upgradeOutdatedProtectionOfDirectory($dir, $this->isCacheDirectory($dir));
                } catch (\Exception $e) {





                    WPStaging::getInstance()->getContainer()->pushToArray(Notices::$directoryListingErrors, $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            \WPStaging\functions\debug_log('WP STAGING: Could not open plugin upload directory to protect from directory listing. ' . $e->getMessage());
        }

        foreach ($this->getPluginWpContentDirectories() as $dir) {
            $this->upgradeOutdatedListingHtaccess($dir, $this->isCacheDirectory($dir));
        }

        foreach ($this->getPluginUploadsDirectoriesOfNetworkSites() as $dir) {
            $this->upgradeOutdatedProtectionOfDirectory($dir, false);
            $this->upgradeOutdatedProtectionOfDirectory(trailingslashit($dir) . 'cache', true);
        }
    }

 
    private function getPluginUploadsDirectoriesOfNetworkSites(): array
    {
        if (!is_multisite()) {
            return [];
        }

        $mainSiteUploadsDirectory = $this->directory->getMainSiteUploadsDirectory();
        $directories              = [$mainSiteUploadsDirectory . WPSTG_PLUGIN_DOMAIN];
        $subsiteDirectoryPatterns = [
            $mainSiteUploadsDirectory . 'sites/*/' . WPSTG_PLUGIN_DOMAIN,
            $this->directory->getWpContentDirectory() . 'blogs.dir/*/files/' . WPSTG_PLUGIN_DOMAIN,
        ];
        foreach ($subsiteDirectoryPatterns as $pattern) {
            $subsiteDirectories = glob($pattern, GLOB_ONLYDIR);
            $directories        = array_merge($directories, is_array($subsiteDirectories) ? $subsiteDirectories : []);
        }

        return $directories;
    }

 
    private function getPluginWpContentDirectories(): array
    {
        $pluginWpContentDirectory = $this->directory->getPluginWpContentDirectory();
        if (!is_dir($pluginWpContentDirectory)) {
            return [];
        }

        $subdirectories = glob(trailingslashit($pluginWpContentDirectory) . '*', GLOB_ONLYDIR);

        return array_merge([$pluginWpContentDirectory], is_array($subdirectories) ? $subdirectories : []);
    }














    private function getInterval()
    {
        return (int)Hooks::applyFilters(self::FILTER_DIRECTORY_LISTING_INTERVAL_CHECK, 15 * 60);
    }








    public function preventDirectoryListing($path)
    {
        $path = trailingslashit(wp_normalize_path($path));

 
 
        if (file_exists($path . 'index.php') && file_exists($path . '.htaccess') && file_exists($path . 'web.config')) {
            return;
        }

 
        if (!is_dir($path)) {
            return;
        }

 
        if (!is_writable($path) && !file_exists($path . 'index.php')) {
            throw new RuntimeException(sprintf(__('Could not prevent directory listing on %s (Reason: Directory is not writable and does not contain an index file)', 'wp-staging'), untrailingslashit($path)));
        }

 
        if (!file_exists($path . 'index.php')) {
            $indexPhpCreated = file_put_contents($path . 'index.php', <<<PHP
<?php
/**
 * WPSTAGING automatically places this index file on all folders it creates to prevent
 * directory listing on servers that might have directory listing enabled.
 *
 * You might have Directory Listing disabled already. If you do, feel free to ignore this file.
 *
 * @link https://www.google.com/search?q=directory+listing+vulnerability
 *       Read more about why Directory Listing can be a security risk.
 *
 * @link https://www.google.com/search?q=disable+directory+listing+apache
 *       How to disable Directory Listing on Apache.
 *
 * @link https://www.google.com/search?q=disable+directory+listing+nginx
 *       How to disable Directory Listing on Nginx.
 */
PHP
            );

            if ($indexPhpCreated === false) {
                throw new RuntimeException(sprintf(__('Could not prevent directory listing on %s (Reason: Failed to create index.php)', 'wp-staging'), untrailingslashit($path)));
            }
        }

 
        if (!file_exists($path . 'index.html')) {
            file_put_contents($path . 'index.html', '');
 
        }

 
        if (!file_exists($path . '.htaccess') && $this->isCacheDirectory($path)) {
            $this->htaccess->createDeny($path . '.htaccess');
        } elseif (!file_exists($path . '.htaccess')) {
            $this->htaccess->create($path . '.htaccess');
 
        }

 
        if (!file_exists($path . 'web.config') && $this->isCacheDirectory($path)) {
            $this->webConfig->createDeny($path . 'web.config');
        } elseif (!file_exists($path . 'web.config')) {
            $this->webConfig->create($path . 'web.config');
 
        }
    }









    public function upgradeOutdatedProtectionOfDirectory(string $directory, bool $isCacheDirectory)
    {
        $directory = trailingslashit(wp_normalize_path($directory));
        $this->upgradeOutdatedListingHtaccess($directory, $isCacheDirectory);
        if (!$isCacheDirectory || !is_file($directory . 'web.config')) {
            return;
        }

        $contents = (string)file_get_contents($directory . 'web.config');
        if ($this->isBackupVaultFile($contents) || strpos($contents, BackupDirectoryProtectionService::WEB_CONFIG_LEGACY_MARKER) === false) {
            return;
        }

        $this->webConfig->createDeny($directory . 'web.config');
    }






    public function replaceOutdatedProtectionOfJobFolders()
    {
        $pluginUploadsDirectory = $this->directory->getPluginUploadsDirectory();
        $cacheDirectory         = $this->directory->getCacheDirectory();
        $this->upgradeOutdatedListingHtaccess($pluginUploadsDirectory, $this->isCacheDirectory($pluginUploadsDirectory));
        $this->upgradeOutdatedProtectionOfDirectory($cacheDirectory, $this->isCacheDirectory($cacheDirectory));
    }







    public function maybeUpdateOldHtaccessWebConfig($backupDirectory)
    {
        $backupDirectory = trailingslashit($backupDirectory);

        $htaccess = $backupDirectory . '.htaccess';
        if (file_exists($htaccess)) {
            $contents = (string)file_get_contents($htaccess);
            if (!$this->isBackupVaultFile($contents) && strpos($contents, 'RemoveHandler ' . Htaccess::PHP_HANDLER_EXTENSIONS) === false) {
                $this->htaccess->create($htaccess);
            }
        }

        $webConfig = $backupDirectory . 'web.config';
        if (file_exists($webConfig)) {
            $contents = (string)file_get_contents($webConfig);
            if (!$this->isBackupVaultFile($contents) && strpos($contents, IISWebConfig::STATIC_FILE_HANDLER) === false) {
                $this->webConfig->create($webConfig);
            }
        }
    }

    private function isOutdatedListingHtaccess(string $contents): bool
    {
        return strpos($contents, BackupDirectoryProtectionService::HTACCESS_LEGACY_MARKER) !== false
            && strpos($contents, Htaccess::PHP_FILES_BLOCK_DIRECTIVE) === false
            && !$this->isBackupVaultFile($contents)
            && strpos($contents, BackupDirectoryProtectionService::HTACCESS_MIME_MARKER) === false;
    }









    private function upgradeOutdatedListingHtaccess(string $directory, bool $isCacheDirectory)
    {
        $directory = trailingslashit(wp_normalize_path($directory));
        if (!is_file($directory . '.htaccess') || !$this->isOutdatedListingHtaccess((string)file_get_contents($directory . '.htaccess'))) {
            return;
        }

        if ($isCacheDirectory) {
            $this->htaccess->createDeny($directory . '.htaccess');
            return;
        }

        $this->htaccess->addPhpFilesBlock($directory . '.htaccess');
    }

    private function isCacheDirectory(string $directory): bool
    {
        $directory      = trailingslashit(wp_normalize_path($directory));
        $cacheDirectory = $this->directory->getCacheDirectory();
        if ($directory === $cacheDirectory) {
            return true;
        }

        $realCacheDirectory = realpath($cacheDirectory);

        return $realCacheDirectory !== false && $directory === trailingslashit(wp_normalize_path($realCacheDirectory));
    }





    private function isBackupVaultFile(string $contents): bool
    {
        return strpos($contents, BackupDirectoryProtectionService::BACKUP_VAULT_MARKER) !== false;
    }
}
