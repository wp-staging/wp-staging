<?php

namespace WPStaging\Backup\Ajax;

use DirectoryIterator;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Framework\Filesystem\AbstractFilesystemScanner;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\FilesystemScannerDto;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\Math;
use WPStaging\Framework\Utils\PluginInfo;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Staging\Sites;
use SplFileInfo;
use WPStaging\Framework\Adapter\WpAdapter;
use WPStaging\Framework\Facades\Hooks;

class BackupSizeCalculator extends AbstractFilesystemScanner
{
 
    const FILTER_EXPORT_FILES_IGNORE_FILE_EXTENSIONS = 'wpstg.export.files.ignore.file_extension';

 
    const FILTER_EXPORT_FILES_IGNORE_FILE_BIGGER_THAN = 'wpstg.export.files.ignore.file_bigger_than';

 
    const FILTER_EXPORT_FILES_IGNORE_LARGE_FILES_BY_EXTENSION = 'wpstg.export.files.ignore.file_extension_bigger_than';

 
    const FILTER_BACKUP_EXCLUDED_DIRECTORIES = 'wpstg.backup.exclude.directories';

 
    protected $sanitize;

 
    protected $auth;

 
    protected $ignoreFileExtensions = [];

 
    protected $ignoreFileBiggerThan = 0;

 
    protected $ignoreFileExtensionFilesBiggerThan = [];

 
    protected $isSiteHostedOnWordPressCom = false;

 
    protected $excludedDirectories = [];

 
    protected $isExcludingCaches = false;

 
    protected $isExcludingLogs = false;

 
    protected $isExcludingDeactivatedPlugins = false;

 
    protected $isExcludingUnusedThemes = false;

 
    protected $math;

 
    protected $scannerDto;

 
    private $isNetworkSiteBackup = false;











    public function __construct(Auth $auth, Directory $directory, Filesystem $filesystem, PluginInfo $pluginInfo, SiteInfo $siteInfo, Math $math, Sanitize $sanitize, PathIdentifier $pathIdentifier)
    {
        parent::__construct($directory, $pathIdentifier, $filesystem, $pluginInfo);

        $this->auth                       = $auth;
        $this->isSiteHostedOnWordPressCom = $siteInfo->isHostedOnWordPressCom();
        $this->math                       = $math;
        $this->sanitize                   = $sanitize;
        $this->scannerDto                 = new FilesystemScannerDto();
    }





    private function setFilters()
    {



        $this->ignoreFileExtensions = (array)Hooks::applyFilters(self::FILTER_EXPORT_FILES_IGNORE_FILE_EXTENSIONS, [
            'wpstg', 
            'gz',
            'tmp',
        ]);




        $this->ignoreFileBiggerThan = (int)Hooks::applyFilters(self::FILTER_EXPORT_FILES_IGNORE_FILE_BIGGER_THAN, 200 * MB_IN_BYTES);




        $this->ignoreFileExtensionFilesBiggerThan = (array)Hooks::applyFilters(self::FILTER_EXPORT_FILES_IGNORE_LARGE_FILES_BY_EXTENSION, [
            'zip' => 50 * MB_IN_BYTES,
        ]);

 
        $this->ignoreFileExtensions = array_flip($this->ignoreFileExtensions);
    }





    public function ajaxCalculateBackupPartsSize()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }

        $backupPart = isset($_POST['backup_part']) ? $this->sanitize->sanitizeString($_POST['backup_part']) : '';
        if (empty($backupPart)) {
            wp_send_json_error([
                'message' => 'Invalid or missing backup part parameter',
            ]);
        }

        $this->setFilters();
        $this->getExcludedDirectories();
        $backupType                          = isset($_POST['backup_type']) ? $this->sanitize->sanitizeString($_POST['backup_type']) : '';
        if (is_multisite() && $backupType === 'multi') {
            $this->isNetworkSiteBackup = true;
        }

        $advanceExclusion                    = isset($_POST['advanceExclusion']) && is_array($_POST['advanceExclusion']) ? $this->sanitize->sanitizeArray($_POST['advanceExclusion']) : [];
        $this->isExcludingUnusedThemes       = isset($advanceExclusion['wpstgExcludeUnusedThemes']) && $advanceExclusion['wpstgExcludeUnusedThemes'] === 'true';
        $this->isExcludingDeactivatedPlugins = isset($advanceExclusion['wpstgExcludeDeactivatedPlugins']) && $advanceExclusion['wpstgExcludeDeactivatedPlugins'] === 'true';
        $this->isExcludingCaches             = isset($advanceExclusion['wpstgExcludeCaches']) && $advanceExclusion['wpstgExcludeCaches'] === 'true';
        $this->isExcludingLogs               = isset($advanceExclusion['wpstgExcludeLogs']) && $advanceExclusion['wpstgExcludeLogs'] === 'true';

        $this->scannerDto->setIsExcludingLogs($this->isExcludingLogs);
        $this->scannerDto->setIsExcludingCaches($this->isExcludingCaches);
        $this->scannerDto->setExcludedDirectories($this->excludedDirectories);

        if ($backupPart === 'includePluginsInBackup') {
            $this->calculatePluginsSize();
        }

        if ($backupPart === 'includeMuPluginsInBackup') {
            $this->calculateMuPluginsSize();
        }

        if ($backupPart === 'includeThemesInBackup') {
            $this->calculateThemesSize();
        }

        if ($backupPart === 'includeMediaLibraryInBackup') {
            $this->calculateUploadsSize();
        }

        if ($backupPart === 'wpstgIncludeOtherFilesInWpRoot') {
            $this->calculateOtherFilesInRootSize();
        }

        if ($backupPart === 'includeOtherFilesInWpContent') {
            $this->calculateOtherFilesInWpContentSize();
        }

        wp_send_json_success();
    }




    protected function calculatePluginsSize()
    {
        $dirToScan    = $this->directory->getPluginsDirectory();
        $excludeRules = $this->getPluginsExcludeRules();
        $partSize     = $this->calculateDirectorySize($dirToScan, PartIdentifier::PLUGIN_PART_IDENTIFIER, $excludeRules);
        $formatedSize = $this->math->formatSize($partSize);

        wp_send_json_success([
            'size'     => empty($formatedSize) ? '0.0 B' : $formatedSize,
            'size_raw' => $partSize,
        ]);
    }




    protected function calculateMuPluginsSize()
    {
        $dirToScan    = $this->directory->getMuPluginsDirectory();
        $excludeRules = [ trailingslashit($dirToScan) . 'wp-staging-optimizer.php'];
        $partSize     = $this->calculateDirectorySize($dirToScan, PartIdentifier::MU_PLUGIN_PART_IDENTIFIER, $excludeRules);
        $formatedSize = $this->math->formatSize($partSize);

        wp_send_json_success([
            'size'     => empty($formatedSize) ? '0.0 B' : $formatedSize,
            'size_raw' => $partSize,
        ]);
    }




    protected function calculateUploadsSize()
    {
        $dirToScan    = $this->directory->getUploadsDirectory();
        $partSize     = $this->calculateDirectorySize($dirToScan, PartIdentifier::UPLOAD_PART_IDENTIFIER);
        $formatedSize = $this->math->formatSize($partSize);

        wp_send_json_success([
            'size'     => empty($formatedSize) ? '0.0 B' : $formatedSize,
            'size_raw' => $partSize,
        ]);
    }





    protected function calculateThemesSize()
    {
        $excludeRules      = $this->getThemesExcludeRules();
        $totalSize         = 0;
        $themesDirectories = $this->directory->getAllThemesDirectories();

        foreach ($themesDirectories as $themesDirectory) {
            if (!is_dir($themesDirectory)) {
                continue;
            }

            $this->setCurrentPathScanning(PartIdentifier::THEME_PART_IDENTIFIER);
            $this->setExcludeRules($excludeRules);
            $this->preScanPath($themesDirectory, true);
            $totalSize += $this->scannerDto->getFilesystemSize();
        }

        $formatedSize = $this->math->formatSize($totalSize);
        wp_send_json_success([
            'size'     => empty($formatedSize) ? '0.0 B' : $formatedSize,
            'size_raw' => $totalSize,
        ]);
    }





    protected function calculateOtherFilesInRootSize()
    {
        $this->scannerDto->setExcludedDirectories($this->getWpRootExcludedDirs());
        $dirToScan    = $this->directory->getAbsPath();
        $excludeRules = $this->getWpRootExcludeRules();
        $partSize     = $this->calculateDirectorySize($dirToScan, PartIdentifier::WP_ROOT_PART_IDENTIFIER, $excludeRules);
        $formatedSize = $this->math->formatSize($partSize);
        wp_send_json_success([
            'size'     => empty($formatedSize) ? '0.0 B' : $formatedSize,
            'size_raw' => $partSize,
        ]);
    }




    protected function calculateOtherFilesInWpContentSize()
    {
        $dirToScan    = $this->directory->getWpContentDirectory();
        $excludeRules = array_map(function ($path) {
            return rtrim($path, '/');
        }, $this->directory->getDefaultWordPressFolders());

        $partSize = $this->calculateDirectorySize($dirToScan, PartIdentifier::WP_CONTENT_PART_IDENTIFIER, $excludeRules);
        $formatedSize = $this->math->formatSize($partSize);
        wp_send_json_success([
            'size'     => empty($formatedSize) ? '0.0 B' : $formatedSize,
            'size_raw' => $partSize,
        ]);
    }








    protected function calculateDirectorySize(string $directory, string $partIdentifier, array $excludeRules = []): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $this->setCurrentPathScanning($partIdentifier);
        $this->setExcludeRules($excludeRules);
        $this->preScanPath($directory, true);

        return $this->scannerDto->getFilesystemSize();
    }






    protected function getWpRootExcludeRules(): array
    {
 
        $stagingSites     = WPStaging::make(Sites::class);
        $stagingSitesDirs = $stagingSites->getStagingDirectories();
        $dirsToSkip       = $this->directory->getWpDefaultRootDirectories();
        $dirsToSkip       = array_merge($dirsToSkip, $stagingSitesDirs);
        $dirsToSkip       = array_merge($dirsToSkip, $this->directory->getWpStagingRestoreDirs());

        return array_map(function ($path) {
            return rtrim($path, '/');
        }, $dirsToSkip);
    }






    protected function getWpRootExcludedDirs(): array
    {
        if (!$this->isBaseNetworkSite()) {
            return $this->excludedDirectories;
        }

        $refresh = true;

        if ($this->isNetworkSiteBackup) {
            $this->excludedDirectories[] = $this->directory->getUploadsDirectory($refresh) . 'sites';
            return $this->excludedDirectories;
        }

 
        $sitesDirectory = $this->directory->getUploadsDirectory($refresh) . 'sites';

        if (is_dir($sitesDirectory) === false) {
            return $this->excludedDirectories;
        }

        $uploadsIt = new DirectoryIterator($sitesDirectory);

        foreach ($uploadsIt as $uploadItem) {
 
            if ($uploadItem->isLink() || $uploadItem->isDot()) {
                continue;
            }

            if ($uploadItem->isFile()) {
                continue;
            }

            if ($uploadItem->isDir()) {
                $this->excludedDirectories[] = trailingslashit($uploadItem->getPathname()) . 'wp-staging';
            }
        }

        return $this->excludedDirectories;
    }




    protected function preRecursivePathScanningStep()
    {
        $this->scannerDto->setFilesystemSize(0);
    }






    protected function processFile(SplFileInfo $fileInfo, string $linkPath = '')
    {
        $pathname = $fileInfo->getPathname();
        if (empty($pathname)) {
            return;
        }

        $normalizedPath = $this->filesystem->normalizePath($pathname, true);
        $fileSize       = $fileInfo->getSize();
        $fileExtension  = $fileInfo->getExtension();
 
        if ($this->shouldSkipFile($fileInfo, $fileExtension, $normalizedPath)) {
            return;
        }

 
        $this->scannerDto->incrementDiscoveredFiles();
        $this->scannerDto->incrementDiscoveredFilesByCategory($this->currentPathScanning);
        $this->scannerDto->addFilesystemSize($fileSize);
    }








    protected function shouldSkipFile(SplFileInfo $fileInfo, string $fileExtension, string $normalizedPath): bool
    {
        if ($this->isExcludedByExtension($fileInfo)) {
            return true;
        }

        if ($this->isExcludedBySize($fileInfo)) {
            return true;
        }

        if ($this->isExcludingLogs && $this->canExcludeLogFile($fileExtension)) {
            return true;
        }

        if ($this->isExcludingCaches && $this->canExcludeCacheFile($fileExtension)) {
            return true;
        }

        if ($this->isExcludedByRules($normalizedPath, $this->excludeRules)) {
            return true;
        }

        return false;
    }






    protected function processDirectory(SplFileInfo $fileInfo, $linkInfo = null)
    {
        $pathname = $fileInfo->getPathname();
        if (empty($pathname)) {
            return;
        }

        $normalizedPath = $this->filesystem->normalizePath($pathname, true);
        if ($this->shouldSkipDirectory($fileInfo, $normalizedPath)) {
            return;
        }

        $this->preScanPath($pathname, true);
    }







    protected function shouldSkipDirectory(SplFileInfo $fileInfo, string $normalizedPath): bool
    {
        if ($this->isExcludedDirectory($fileInfo->getPathname())) {
            return true;
        }

        if ($this->isExcludingCaches && $this->canExcludeCacheDir($fileInfo)) {
            return true;
        }

        if ($this->isExcludedByRules($normalizedPath, $this->excludeRules)) {
            return true;
        }

        return false;
    }






    protected function isExcludedDirectory(string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        $normalizedPath = $this->filesystem->normalizePath($path, true);
        foreach ($this->excludedDirectories as $excludedDir) {
            if (strpos($normalizedPath, $excludedDir) === 0) {
                return true;
            }
        }

        return false;
    }






    protected function isExcludedByExtension(SplFileInfo $file): bool
    {
        $extension = strtolower($file->getExtension());
        return isset($this->ignoreFileExtensions[$extension]);
    }






    protected function isExcludedBySize(SplFileInfo $file): bool
    {
        if ($file->getSize() > $this->ignoreFileBiggerThan) {
            return true;
        }

        $extension = strtolower($file->getExtension());
        if (isset($this->ignoreFileExtensionFilesBiggerThan[$extension]) && $file->getSize() > $this->ignoreFileExtensionFilesBiggerThan[$extension]) {
            return true;
        }

        return false;
    }






    protected function canExcludeLogFile(string $fileExtension): bool
    {
        $logExtensions = ['log'];
        return in_array(strtolower($fileExtension), $logExtensions);
    }






    protected function canExcludeCacheFile(string $fileExtension): bool
    {
        $cacheExtensions = ['cache'];
        return in_array(strtolower($fileExtension), $cacheExtensions);
    }






    protected function canExcludeCacheDir(SplFileInfo $dir): bool
    {
        $cacheDirs = ['cache'];
        return in_array(strtolower($dir->getFilename()), $cacheDirs);
    }





    protected function getExcludedDirectories(): array
    {
        $excludedDirs   = [];
        $excludedDirs[] = WPSTG_PLUGIN_DIR;
        $excludedDirs[] = $this->directory->getPluginUploadsDirectory();
        $excludedDirs[] = $this->directory->getPluginWpContentDirectory();
        $excludedDirs[] = trailingslashit(WP_CONTENT_DIR) . 'cache';

 
        $backupUploadsDirPostFix = '.wpstg_backup';

 
        $excludedDirs[] = untrailingslashit($this->directory->getUploadsDirectory()) . $backupUploadsDirPostFix;
 
        $backupUploadsDir = trailingslashit(WP_CONTENT_DIR) . 'uploads' . $backupUploadsDirPostFix;
        if (!in_array($backupUploadsDir, $excludedDirs)) {
            $excludedDirs[] = $backupUploadsDir;
        }





        $excludedDirs[] = trailingslashit(WP_CONTENT_DIR) . 'ai1wm-backups';






        $excludedDirs[] = $this->directory->getUploadsDirectory() . 'wio_backup';





        $excludedDirs[] = $this->directory->getStagingSiteDirectoryInsideWpcontent($createDir = false);








        $this->excludedDirectories = (array)apply_filters(self::FILTER_BACKUP_EXCLUDED_DIRECTORIES, $excludedDirs);

        return $this->excludedDirectories;
    }





    protected function getRootPath(): string
    {
        if ($this->isSiteHostedOnWordPressCom) {
            return $this->directory->getWpContentDirectory();
        }

        return $this->directory->getAbsPath();
    }





    protected function getUploadsDirectory(): string
    {
        $uploadsDir = $this->directory->getUploadsDirectory();
        return $uploadsDir ?: '';
    }





    protected function getThemesExcludeRules(): array
    {
        if (!$this->isExcludingUnusedThemes) {
            return [];
        }

        $activeThemes         = $this->getActiveThemes();
        $allThemesDirectories = $this->directory->getAllThemesDirectories();
        $excludeRules         = [];
        foreach ($allThemesDirectories as $themeDir) {
            $excludeRules[] = rtrim($themeDir, "/");
        }

        foreach ($activeThemes as $theme) {
            $excludeRules[] = "!" . $theme;
        }

        return $excludeRules;
    }





    protected function getActiveThemes(): array
    {
        if (!is_multisite()) {
            return $this->pluginInfo->getActiveThemes();
        }

        if ($this->isNetworkSiteBackup) {
            return $this->pluginInfo->getActiveThemes();
        }

        return $this->pluginInfo->getAllActiveThemesInSubsites();
    }





    protected function getPluginsExcludeRules(): array
    {
        if (!$this->isExcludingDeactivatedPlugins) {
            return [];
        }

        $pluginsDir    = rtrim($this->directory->getPluginsDirectory(), "/");
        $activePlugins = array_unique($this->getActivePlugins());
        $excludeRules  = [$pluginsDir];

        foreach ($activePlugins as $plugin) {
            $pluginDir = dirname($plugin);
            if ($pluginDir === $pluginsDir) {
                $excludeRules[] = "!" . $plugin;
                continue;
            }

            $excludeRules[] = "!" . $pluginDir;
        }

        return $excludeRules;
    }






    protected function getActivePlugins(): array
    {
 
        remove_all_filters(WpAdapter::FILTER_OPTION_ACTIVE_PLUGINS);

 
        if (!is_multisite()) {
            return wp_get_active_and_valid_plugins();
        }

 
        if ($this->isNetworkSiteBackup) {
            return wp_get_active_and_valid_plugins();
        }

 
        remove_all_filters(WpAdapter::FILTER_SITE_OPTION_ACTIVE_SITEWIDE_PLUGINS);

        return array_merge(wp_get_active_network_plugins(), $this->pluginInfo->getAllActivePluginsInSubsites());
    }








    protected function isExcludedByRules(string $path, array $excludeRules): bool
    {
        if (empty($excludeRules)) {
            return false;
        }

        $normalizedPath = $this->filesystem->normalizePath($path, true);
        $isExcluded = false;

        foreach ($excludeRules as $rule) {
            $isInclusionRule = strpos($rule, '!') === 0;
            $rulePath = $isInclusionRule ? substr($rule, 1) : $rule;
            $rulePath = $this->filesystem->normalizePath($rulePath, true);

            if (strpos($normalizedPath, $rulePath) === 0) {
                $isExcluded = !$isInclusionRule;
            }
        }

        return $isExcluded;
    }




    protected function isBaseNetworkSite(): bool
    {
        if (!is_multisite()) {
            return false;
        }

        $blogId = get_current_blog_id();
        return $blogId === 1 || $blogId === 0;
    }
}
