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

class BackupSizeCalculator extends AbstractFilesystemScanner
{
    /** @var Sanitize */
    protected $sanitize;

    /** @var Auth */
    protected $auth;

    /** @var array */
    protected $ignoreFileExtensions = [];

    /** @var int */
    protected $ignoreFileBiggerThan = 0;

    /** @var array */
    protected $ignoreFileExtensionFilesBiggerThan = [];

    /** @var bool */
    protected $isSiteHostedOnWordPressCom = false;

    /** @var array */
    protected $excludedDirectories = [];

    /** @var bool */
    protected $isExcludingCaches = false;

    /** @var bool */
    protected $isExcludingLogs = false;

    /** @var bool */
    protected $isExcludingDeactivatedPlugins = false;

    /** @var bool */
    protected $isExcludingUnusedThemes = false;

    /** @var Math */
    protected $math;

    /** @var FilesystemScannerDto */
    protected $scannerDto;

    /** @var bool */
    private $isNetworkSiteBackup = false;

    /**
     * @param Auth $auth
     * @param Directory $directory
     * @param Filesystem $filesystem
     * @param PluginInfo $pluginInfo
     * @param SiteInfo $siteInfo
     * @param Math $math
     * @param Sanitize $sanitize
     * @param PathIdentifier $pathIdentifier
     */
    public function __construct(Auth $auth, Directory $directory, Filesystem $filesystem, PluginInfo $pluginInfo, SiteInfo $siteInfo, Math $math, Sanitize $sanitize, PathIdentifier $pathIdentifier)
    {
        parent::__construct($directory, $pathIdentifier, $filesystem, $pluginInfo);

        $this->auth                       = $auth;
        $this->isSiteHostedOnWordPressCom = $siteInfo->isHostedOnWordPressCom();
        $this->math                       = $math;
        $this->sanitize                   = $sanitize;
        $this->scannerDto                 = new FilesystemScannerDto();
    }

    /**
     * Set filters for file exclusion
     * @return void
     */
    private function setFilters()
    {
        /**
         * Allow user to exclude certain file extensions from being backup.
         */
        $this->ignoreFileExtensions = (array)apply_filters('wpstg.export.files.ignore.file_extension', [
            'wpstg', // WP STAGING backup files
            'gz',
            'tmp',
        ]);

        /**
         * Allow user to exclude files larger than given size from being backup.
         */
        $this->ignoreFileBiggerThan = (int)apply_filters('wpstg.export.files.ignore.file_bigger_than', 200 * MB_IN_BYTES);

        /**
         * Allow user to exclude files with extension larger than given size from being backup.
         */
        $this->ignoreFileExtensionFilesBiggerThan = (array)apply_filters('wpstg.export.files.ignore.file_extension_bigger_than', [
            'zip' => 10 * MB_IN_BYTES,
        ]);

        // Allows us to use isset for performance
        $this->ignoreFileExtensions = array_flip($this->ignoreFileExtensions);
    }

    /**
     * @return void
     * @throws WPStagingException
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * Calculate the size of themes directory
     * @return void
     */
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

    /**
     * @return void
     * @throws WPStagingException
     */
    protected function calculateOtherFilesInRootSize()
    {
        if (WPStaging::isBasic()) {
            wp_send_json_success([
                'size' => '0.0 B',
            ]);
        }

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

    /**
     * @return void
     */
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

    /**
     * Generic method to calculate directory size
     * @param string $directory Directory path to scan
     * @param string $partIdentifier Identifier for the part being scanned
     * @param array $excludeRules Rules for excluding paths
     * @return int
     */
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

    /**
     * Get WP Root exclude rules
     * @return array
     * @throws WPStagingException
     */
    protected function getWpRootExcludeRules(): array
    {
        /** @var Sites */
        $stagingSites     = WPStaging::make(Sites::class);
        $stagingSitesDirs = $stagingSites->getStagingDirectories();
        $dirsToSkip       = $this->directory->getWpDefaultRootDirectories();
        $dirsToSkip       = array_merge($dirsToSkip, $stagingSitesDirs);

        return array_map(function ($path) {
            return rtrim($path, '/');
        }, $dirsToSkip);
    }

    /**
     * Get WP Root exclude dir
     * @return array
     * @throws WPStagingException
     */
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

        // Exclude all wp staging uploads directories from subsites
        $sitesDirectory = $this->directory->getUploadsDirectory($refresh) . 'sites';

        if (is_dir($sitesDirectory) === false) {
            return $this->excludedDirectories;
        }

        $uploadsIt = new DirectoryIterator($sitesDirectory);

        foreach ($uploadsIt as $uploadItem) {
            // Early bail: We don't touch links and we also skip dots
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

    /**
     * @return void
     */
    protected function preRecursivePathScanningStep()
    {
        $this->scannerDto->setFilesystemSize(0);
    }

    /**
     * @param SplFileInfo $fileInfo
     * @param string $linkPath
     * @return void
     */
    protected function processFile(SplFileInfo $fileInfo, string $linkPath = '')
    {
        $pathname = $fileInfo->getPathname();
        if (empty($pathname)) {
            return;
        }

        $normalizedPath = $this->filesystem->normalizePath($pathname, true);
        $fileSize       = $fileInfo->getSize();
        $fileExtension  = $fileInfo->getExtension();
        // Skip files based on various criteria
        if ($this->shouldSkipFile($fileInfo, $fileExtension, $normalizedPath)) {
            return;
        }

        // Add file size to the total
        $this->scannerDto->incrementDiscoveredFiles();
        $this->scannerDto->incrementDiscoveredFilesByCategory($this->currentPathScanning);
        $this->scannerDto->addFilesystemSize($fileSize);
    }

    /**
     * Determine if a file should be skipped based on various criteria
     * @param SplFileInfo $fileInfo
     * @param string $fileExtension
     * @param string $normalizedPath
     * @return bool
     */
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

    /**
     * @param SplFileInfo $fileInfo
     * @param SplFileInfo|null $linkInfo
     * @return void
     */
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

    /**
     * Determine if a directory should be skipped based on various criteria
     * @param SplFileInfo $fileInfo
     * @param string $normalizedPath
     * @return bool
     */
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

    /**
     * Check if a directory is excluded
     * @param string $path
     * @return bool
     */
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

    /**
     * Check if a file is excluded by extension
     * @param SplFileInfo $file
     * @return bool
     */
    protected function isExcludedByExtension(SplFileInfo $file): bool
    {
        $extension = strtolower($file->getExtension());
        return isset($this->ignoreFileExtensions[$extension]);
    }

    /**
     * Check if a file is excluded by size
     * @param SplFileInfo $file
     * @return bool
     */
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

    /**
     * Check if a log file can be excluded
     * @param string $fileExtension
     * @return bool
     */
    protected function canExcludeLogFile(string $fileExtension): bool
    {
        $logExtensions = ['log'];
        return in_array(strtolower($fileExtension), $logExtensions);
    }

    /**
     * Check if a cache file can be excluded
     * @param string $fileExtension
     * @return bool
     */
    protected function canExcludeCacheFile(string $fileExtension): bool
    {
        $cacheExtensions = ['cache'];
        return in_array(strtolower($fileExtension), $cacheExtensions);
    }

    /**
     * Check if a cache directory can be excluded
     * @param SplFileInfo $dir
     * @return bool
     */
    protected function canExcludeCacheDir(SplFileInfo $dir): bool
    {
        $cacheDirs = ['cache'];
        return in_array(strtolower($dir->getFilename()), $cacheDirs);
    }

    /**
     * Get directories to exclude from backup
     * @return array
     */
    protected function getExcludedDirectories(): array
    {
        $excludedDirs   = [];
        $excludedDirs[] = WPSTG_PLUGIN_DIR;
        $excludedDirs[] = $this->directory->getPluginUploadsDirectory();
        $excludedDirs[] = $this->directory->getPluginWpContentDirectory();
        $excludedDirs[] = trailingslashit(WP_CONTENT_DIR) . 'cache';

        // @see BackupUploadsDir::BACKUP_UPLOADS_DIR_POSTFIX
        $backupUploadsDirPostFix = '.wpstg_backup';

        // Old uploads backup folder created by WP STAGING during push e.g. wp-content/uploads.wpstg_backup
        $excludedDirs[] = untrailingslashit($this->directory->getUploadsDirectory()) . $backupUploadsDirPostFix;
        // Just extra caution if someone changed uploads directory afterwards. Exclude this directory as well.
        $backupUploadsDir = trailingslashit(WP_CONTENT_DIR) . 'uploads' . $backupUploadsDirPostFix;
        if (!in_array($backupUploadsDir, $excludedDirs)) {
            $excludedDirs[] = $backupUploadsDir;
        }

        /**
         * @see https://wordpress.org/plugins/all-in-one-wp-migration/
         *      This folder contains backups generated by All In One WP Migration.
         */
        $excludedDirs[] = trailingslashit(WP_CONTENT_DIR) . 'ai1wm-backups';

        /**
         * @see https://wordpress.org/plugins/robin-image-optimizer/
         *      This folder contains a duplicate of the uploads folder, for optimized images.
         *      It can be manually re-generated from the existing media library later.
         */
        $excludedDirs[] = $this->directory->getUploadsDirectory() . 'wio_backup';

        /**
         * This is default directory that contains staging sites created by WP STAGING when ABSPATH is not writable.
         * There is no need to backup the staging sites directory
         */
        $excludedDirs[] = $this->directory->getStagingSiteDirectoryInsideWpcontent($createDir = false);

        /**
         * Allow user to filter the excluded directories in a site backup.
         *
         * @param array $excludedDirectories
         *
         * @return array An array of directories to exclude.
         */
        $this->excludedDirectories = (array)apply_filters('wpstg.backup.exclude.directories', $excludedDirs);

        return $this->excludedDirectories;
    }

    /**
     * Get the root path
     * @return string
     */
    protected function getRootPath(): string
    {
        if ($this->isSiteHostedOnWordPressCom) {
            return $this->directory->getWpContentDirectory();
        }

        return $this->directory->getAbsPath();
    }

    /**
     * Get the uploads directory
     * @return string
     */
    protected function getUploadsDirectory(): string
    {
        $uploadsDir = $this->directory->getUploadsDirectory();
        return $uploadsDir ?: '';
    }

    /**
     * Get the themes exclude rules
     * @return array
     */
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

    /**
     * Get the active themes
     * @return array
     */
    protected function getActiveThemes(): array
    {
        if (!is_multisite()) {
            return $this->pluginInfo->getActiveThemes();
        }

        if (isset($this->isNetworkSiteBackup) && $this->isNetworkSiteBackup) {
            return $this->pluginInfo->getActiveThemes();
        }

        return $this->pluginInfo->getAllActiveThemesInSubsites();
    }

    /**
     * Get the plugins exclude rules
     * @return array
     */
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

    /**
     * Get the active plugins
     *
     * @return array
     */
    protected function getActivePlugins(): array
    {
        // Prevent filters tampering with the active plugins list, such as wpstg-optimizer.php itself.
        remove_all_filters('option_active_plugins');

        // Not multisite
        if (!is_multisite()) {
            return wp_get_active_and_valid_plugins();
        }

        // Multisite but only current site is being backup
        if (isset($this->isNetworkSiteBackup) && $this->isNetworkSiteBackup) {
            return wp_get_active_and_valid_plugins();
        }

        // Prevent filters tampering with the active plugins list, such as wpstg-optimizer.php itself.
        remove_all_filters('site_option_active_sitewide_plugins');

        return array_merge(wp_get_active_network_plugins(), $this->pluginInfo->getAllActivePluginsInSubsites());
    }

    /**
     * Check if a path is excluded by rules
     *
     * @param string $path
     * @param array $excludeRules
     * @return bool
     */
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

    /**
     * @return bool
     */
    protected function isBaseNetworkSite(): bool
    {
        if (!is_multisite()) {
            return false;
        }

        $blogId = get_current_blog_id();
        return $blogId === 1 || $blogId === 0;
    }
}
