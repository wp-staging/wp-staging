<?php

// TODO PHP7.1; constant visibility

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use WPStaging\Backup\Task\BackupTask;
use WPStaging\Backup\Task\FileBackupTask;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\FilesystemScanner;
use WPStaging\Framework\Filesystem\FilesystemScannerDto;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Utils\PluginInfo;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class FilesystemScannerTask extends BackupTask
{
    /** @var int */
    const STEP_BACKUP_OTHER_WP_CONTENT_FILES = 0;

    /** @var int */
    const STEP_BACKUP_PLUGINS_FILES = 1;

    /** @var int */
    const STEP_BACKUP_MU_PLUGINS_FILES = 2;

    /** @var int */
    const STEP_BACKUP_THEMES_FILES = 3;

    /** @var int */
    const STEP_BACKUP_UPLOADS_FILES = 4;

    /** @var int */
    const STEP_BACKUP_OTHER_WP_ROOT_FILES = 5;

    /**
     * 6 steps for scanning each identifier and the last step to deep scan non-scanned directories
     * @var int
     */
    const TOTAL_STEPS = 7;

    /** @var Directory */
    protected $directory;

    /** @var Filesystem */
    protected $filesystem;

    /** @var PluginInfo */
    private $pluginInfo;

    /** @var FilesystemScanner */
    protected $filesystemScanner;

    /** @var array */
    protected $ignoreFileExtensions = [];

    /** @var int */
    protected $ignoreFileBiggerThan = 0;

    /** @var array */
    protected $ignoreFileExtensionFilesBiggerThan = [];

    /** @var bool */
    protected $isSiteHostedOnWordPressCom = false;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     * @param Directory $directory
     * @param Filesystem $filesystem
     * @param PluginInfo $pluginInfo
     * @param SiteInfo $siteInfo
     * @param FilesystemScanner $filesystemScanner
     */
    public function __construct(
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue,
        Directory $directory,
        Filesystem $filesystem,
        PluginInfo $pluginInfo,
        SiteInfo $siteInfo,
        FilesystemScanner $filesystemScanner
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->directory                  = $directory;
        $this->filesystem                 = $filesystem;
        $this->isSiteHostedOnWordPressCom = $siteInfo->isHostedOnWordPressCom();
        $this->pluginInfo                 = $pluginInfo;
        $this->filesystemScanner          = $filesystemScanner;
    }

    /**
     * @return string
     * @example 'backup_site_restore_themes'
     */
    public static function getTaskName(): string
    {
        return 'backup_filesystem_scan';
    }

    /**
     * @return string
     * @example 'Restoring Themes From Backup'
     */
    public static function getTaskTitle(): string
    {
        return 'Discovering Files';
    }

    /**
     * @inheritDoc
     * @throws DiskNotWritableException
     */
    public function execute(): TaskResponseDto
    {
        $this->setupFilters();
        $this->setupFilesystemScanner();

        if ($this->stepsDto->getCurrent() === self::STEP_BACKUP_OTHER_WP_CONTENT_FILES) {
            return $this->scanWpContentDirectory();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_BACKUP_PLUGINS_FILES) {
            return $this->scanPluginsDirectories();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_BACKUP_MU_PLUGINS_FILES) {
            return $this->scanMuPluginsDirectory();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_BACKUP_THEMES_FILES) {
            return $this->scanThemesDirectory();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_BACKUP_UPLOADS_FILES) {
            return $this->scanUploadsDirectory();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_BACKUP_OTHER_WP_ROOT_FILES) {
            return $this->scanWpRootDirectory();
        }

        while (!$this->isThreshold() && !$this->stepsDto->isFinished()) {
            try {
                $this->filesystemScanner->processQueue();
            } catch (FinishedQueueException $e) {
                $this->stepsDto->finish();
            }

            $this->updateJobDataDto();
        }

        if ($this->stepsDto->isFinished()) {
            $this->stepsDto->setManualPercentage(100);
            $this->logger->info(sprintf('Finished discovering Files. (%d files)', $this->jobDataDto->getDiscoveredFiles()));
        } else {
            $this->jobDataDto->setDiscoveringFilesRequests($this->jobDataDto->getDiscoveringFilesRequests() + 1);

            // The manual percentage increments 30% per request, until it hits 90%, point of which it increments 1%
            if ($this->jobDataDto->getDiscoveringFilesRequests() <= 3) {
                // 30%, 60%, 90%...
                $manualPercentage = $this->jobDataDto->getDiscoveringFilesRequests() * 30;
            } elseif ($this->jobDataDto->getDiscoveringFilesRequests() >= 4 && $this->jobDataDto->getDiscoveringFilesRequests() <= 14) {
                // 91%, 92%, 93%...
                $manualPercentage = 90;
                $manualPercentage += $this->jobDataDto->getDiscoveringFilesRequests() - 3;
            } else {
                // 99%
                $manualPercentage = 99;
            }

            $this->stepsDto->setManualPercentage(min($manualPercentage, 100));
            $this->logger->info(sprintf('Discovering Files (%d files)', $this->jobDataDto->getDiscoveredFiles()));
        }

        return $this->generateResponse(false);
    }

    /**
     * @return void
     */
    protected function setupFilters()
    {
        /**
         * Allow user to exclude certain file extensions from being backup.
         */
        $this->ignoreFileExtensions = (array)apply_filters('wpstg.export.files.ignore.file_extension', [
            'log',
            'wpstg', // WP STAGING backup files
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
     */
    protected function setupFilesystemScanner()
    {
        if (empty($this->stepsDto->getTotal())) {
            $excludedDirs = array_map(function ($path) {
                return $this->filesystem->normalizePath($path, true);
            }, $this->getExcludedDirectories());

            $this->jobDataDto->setExcludedDirectories($excludedDirs);

            $this->stepsDto->setTotal(self::TOTAL_STEPS);
            $this->taskQueue->seek(0);
        }

        $this->filesystemScanner->setFilters($this->ignoreFileBiggerThan, $this->ignoreFileExtensions, $this->ignoreFileExtensionFilesBiggerThan);
        $this->filesystemScanner->setLogTitle(static::getTaskTitle());
        $this->filesystemScanner->setQueueCacheName(FileBackupTask::getTaskName());
        $this->filesystemScanner->inject($this->logger, $this->taskQueue, $this->getScannerDto());
    }

    /**
     * Scan wp-content directory(wp-content/) but doesn't scan sub folders.
     */
    protected function scanWpContentDirectory(): TaskResponseDto
    {
        if (!$this->jobDataDto->getIsExportingOtherWpContentFiles()) {
            return $this->generateResponse();
        }

        $dirToScan    = $this->directory->getWpContentDirectory();
        $excludeRules = array_map(function ($path) {
            return rtrim($path, '/');
        }, $this->directory->getDefaultWordPressFolders());

        $this->preScanPath($dirToScan, PartIdentifier::OTHER_WP_CONTENT_PART_IDENTIFIER, $excludeRules);

        return $this->generateResponse();
    }

    /**
     * Scan plugins directory(wp-content/plugins) but doesn't scan sub folders.
     */
    protected function scanPluginsDirectories(): TaskResponseDto
    {
        if (!$this->jobDataDto->getIsExportingPlugins()) {
            return $this->generateResponse();
        }

        $dirToScan    = $this->directory->getPluginsDirectory();
        $excludeRules = $this->getPluginsExcludeRules();

        $this->preScanPath($dirToScan, PartIdentifier::PLUGIN_PART_IDENTIFIER, $excludeRules, $this->isSiteHostedOnWordPressCom);

        return $this->generateResponse();
    }

    /**
     * Scan mu-plugins directory(wp-content/mu-plugins) but doesn't scan sub folders.
     */
    protected function scanMuPluginsDirectory(): TaskResponseDto
    {
        if (!$this->jobDataDto->getIsExportingMuPlugins()) {
            return $this->generateResponse();
        }

        // Early bail: mu-plugins directory doesn't exist
        if (!is_dir($this->directory->getMuPluginsDirectory())) {
            return $this->generateResponse();
        }

        $dirToScan    = $this->directory->getMuPluginsDirectory();
        $excludeRules = [
            trailingslashit($this->directory->getMuPluginsDirectory()) . 'wp-staging-optimizer.php'
        ];

        $this->preScanPath($dirToScan, PartIdentifier::MU_PLUGIN_PART_IDENTIFIER, $excludeRules);

        return $this->generateResponse();
    }

    /**
     * Scan themes directory(wp-content/themes) but doesn't scan sub folders.
     */
    protected function scanThemesDirectory(): TaskResponseDto
    {
        if (!$this->jobDataDto->getIsExportingThemes()) {
            return $this->generateResponse();
        }

        $excludeRules = $this->getThemesExcludeRules();
        $this->filesystemScanner->setCurrentPathScanning(PartIdentifier::THEME_PART_IDENTIFIER);
        $this->filesystemScanner->setupFilesystemQueue();
        $this->filesystemScanner->setRootPath($this->getRootPath());
        $this->filesystemScanner->setExcludeRules($excludeRules);
        foreach ($this->directory->getAllThemesDirectories() as $themesDirectory) {
            // Only process links if site hosted on wp.com
            $this->filesystemScanner->preScanPath($themesDirectory, $this->isSiteHostedOnWordPressCom);
        }

        $this->filesystemScanner->unlockQueue();
        $this->updateJobDataDto();

        return $this->generateResponse();
    }

    protected function scanUploadsDirectory(): TaskResponseDto
    {
        if (!$this->jobDataDto->getIsExportingUploads()) {
            return $this->generateResponse();
        }

        // Early bail: Uploads directory doesn't exist
        if (!is_dir($this->getUploadsDirectory())) {
            return $this->generateResponse();
        }

        $dirToScan    = $this->getUploadsDirectory();
        $excludeRules = [];

        $this->preScanPath($dirToScan, PartIdentifier::UPLOAD_PART_IDENTIFIER, $excludeRules);

        return $this->generateResponse();
    }

    protected function getRootPath(): string
    {
        if ($this->isSiteHostedOnWordPressCom) {
            return $this->directory->getWpContentDirectory();
        }

        return $this->directory->getAbsPath();
    }

    /**
     * Scan WP root directory(ABSPATH) but doesn't scan sub folders. (Used in pro)
     */
    protected function scanWpRootDirectory(): TaskResponseDto
    {
        return $this->generateResponse();
    }

    /**
     * @return array
     */
    protected function getExcludedDirectories(): array
    {
        $excludedDirs = [];

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
        $excludedDirs = (array)apply_filters('wpstg.backup.exclude.directories', $excludedDirs);

        return $excludedDirs;
    }

    /**
     * @return string
     */
    protected function getUploadsDirectory(): string
    {
        return $this->directory->getUploadsDirectory();
    }

    protected function getScannerDto(): FilesystemScannerDto
    {
        $scannerDto = new FilesystemScannerDto();

        $scannerDto->setIsExcludingCaches($this->jobDataDto->getIsExcludingCaches() ?? false);
        $scannerDto->setIsExcludingLogs($this->jobDataDto->getIsExcludingLogs() ?? false);
        $scannerDto->setExcludedDirectories($this->jobDataDto->getExcludedDirectories() ?? []);
        $scannerDto->setDiscoveredFiles($this->jobDataDto->getDiscoveredFiles() ?? 0);
        $scannerDto->setDiscoveredFilesArray($this->jobDataDto->getDiscoveredFilesArray() ?? []);
        $scannerDto->setFilesystemSize($this->jobDataDto->getFilesystemSize() ?? 0);
        $scannerDto->setTotalDirectories($this->jobDataDto->getTotalDirectories() ?? 0);

        return $scannerDto;
    }

    /**
     * @return void
     */
    protected function updateJobDataDto()
    {
        $scannerDto = $this->filesystemScanner->getFilesystemScannerDto();

        $this->jobDataDto->setDiscoveredFiles($scannerDto->getDiscoveredFiles());
        $this->jobDataDto->setDiscoveredFilesArray($scannerDto->getDiscoveredFilesArray());
        $this->jobDataDto->setFilesystemSize($scannerDto->getFilesystemSize());
        $this->jobDataDto->setTotalDirectories($scannerDto->getTotalDirectories());
    }

    /**
     * Pre scan path
     * This is common method for pre scanning path, but cannot be used for scanning themes folders i.e. because there can be multiple themes folders
     * @param string $dirToScan
     * @param string $partIdentifier
     * @param array $excludeRules
     * @param bool $processLinks
     * @return void
     */
    protected function preScanPath(string $dirToScan, string $partIdentifier, array $excludeRules = [], bool $processLinks = false)
    {
        $this->filesystemScanner->setCurrentPathScanning($partIdentifier);
        $this->filesystemScanner->setupFilesystemQueue();
        $this->filesystemScanner->setRootPath($this->getRootPath());
        $this->filesystemScanner->setExcludeRules($excludeRules);
        $this->filesystemScanner->preScanPath($dirToScan, $processLinks);
        $this->filesystemScanner->unlockQueue();
        $this->updateJobDataDto();
    }

    private function getThemesExcludeRules(): array
    {
        if (!$this->jobDataDto->getIsExcludingUnusedThemes()) {
            return [];
        }

        $activeThemes = $this->getActiveThemes();
        $allThemesDirectories = $this->directory->getAllThemesDirectories();
        foreach ($allThemesDirectories as $themeDir) {
            $excludeRules[] = rtrim($themeDir, "/");
        }

        foreach ($activeThemes as $theme) {
            $excludeRules[] = "!" . $theme;
        }

        return $excludeRules;
    }

    /**
     * @return array
     */
    private function getActiveThemes(): array
    {
        // Not multisite
        if (!is_multisite()) {
            return $this->pluginInfo->getActiveThemes();
        }

        // Multisite but only current site is being backup
        if ($this->jobDataDto->getIsNetworkSiteBackup()) {
            return $this->pluginInfo->getActiveThemes();
        }

        return $this->pluginInfo->getAllActiveThemesInSubsites();
    }

    private function getPluginsExcludeRules(): array
    {
        if (!$this->jobDataDto->getIsExcludingDeactivatedPlugins()) {
            return [];
        }

        $pluginsDir = rtrim($this->directory->getPluginsDirectory(), "/");

        $activePlugins = array_unique($this->getActivePlugins());
        $excludeRules  = [
            $pluginsDir,
        ];

        foreach ($activePlugins as $plugin) {
            $pluginDir = dirname($plugin);
            // Single file plugin
            if ($pluginDir === $pluginsDir) {
                $excludeRules[] = "!" . $plugin;
                continue;
            }

            $excludeRules[] = "!" . $pluginDir;
        }

        return $excludeRules;
    }

    /**
     * @return array
     */
    private function getActivePlugins(): array
    {
        // Prevent filters tampering with the active plugins list, such as wpstg-optimizer.php itself.
        remove_all_filters('option_active_plugins');

        // Not multisite
        if (!is_multisite()) {
            return wp_get_active_and_valid_plugins();
        }

        // Multisite but only current site is being backup
        if ($this->jobDataDto->getIsNetworkSiteBackup()) {
            return wp_get_active_and_valid_plugins();
        }

        // Prevent filters tampering with the active plugins list, such as wpstg-optimizer.php itself.
        remove_all_filters('site_option_active_sitewide_plugins');

        return array_merge(wp_get_active_network_plugins(), $this->pluginInfo->getAllActivePluginsInSubsites());
    }
}
