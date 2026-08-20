<?php

 

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use WPStaging\Backup\Ajax\BackupSizeCalculator;
use WPStaging\Backup\Exceptions\NothingToBackupException;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Backup\Task\FileBackupTask;
use WPStaging\Core\WPStaging;
use WPStaging\Staging\Sites;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Adapter\WpAdapter;
use WPStaging\Framework\Facades\Hooks;
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
 
    const STEP_BACKUP_OTHER_WP_CONTENT_FILES = 0;

 
    const STEP_BACKUP_PLUGINS_FILES = 1;

 
    const STEP_BACKUP_MU_PLUGINS_FILES = 2;

 
    const STEP_BACKUP_THEMES_FILES = 3;

 
    const STEP_BACKUP_UPLOADS_FILES = 4;

 
    const STEP_BACKUP_OTHER_WP_ROOT_FILES = 5;





    const TOTAL_STEPS = 7;

 
    protected $directory;

 
    protected $filesystem;

 
    private $pluginInfo;

 
    protected $filesystemScanner;

 
    protected $ignoreFileExtensions = [];

 
    protected $ignoreFileBiggerThan = 0;

 
    protected $ignoreFileExtensionFilesBiggerThan = [];

 
    protected $isSiteHostedOnWordPressCom = false;












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





    public static function getTaskName(): string
    {
        return 'backup_filesystem_scan';
    }





    public static function getTaskTitle(): string
    {
        return 'Discovering Files';
    }






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
            $this->throwIfNothingToBackup();
        } else {
            $this->jobDataDto->setDiscoveringFilesRequests($this->jobDataDto->getDiscoveringFilesRequests() + 1);

 
            if ($this->jobDataDto->getDiscoveringFilesRequests() <= 3) {
 
                $manualPercentage = $this->jobDataDto->getDiscoveringFilesRequests() * 30;
            } elseif ($this->jobDataDto->getDiscoveringFilesRequests() >= 4 && $this->jobDataDto->getDiscoveringFilesRequests() <= 14) {
 
                $manualPercentage = 90;
                $manualPercentage += $this->jobDataDto->getDiscoveringFilesRequests() - 3;
            } else {
 
                $manualPercentage = 99;
            }

            $this->stepsDto->setManualPercentage(min($manualPercentage, 100));
            $this->logger->info(sprintf('Discovering Files (%d files)', $this->jobDataDto->getDiscoveredFiles()));
        }

        return $this->generateResponse(false);
    }









    protected function throwIfNothingToBackup()
    {
        if ($this->jobDataDto->getIsSyncRequest()) {
            return;
        }

        if ($this->jobDataDto->getDiscoveredFiles() > 0 || $this->jobDataDto->getIsExportingDatabase()) {
            return;
        }

        throw new NothingToBackupException(esc_html__('Nothing to backup. The items you selected contain no files.', 'wp-staging'));
    }




    protected function setupFilters()
    {



        $this->ignoreFileExtensions = (array)Hooks::applyFilters(BackupSizeCalculator::FILTER_EXPORT_FILES_IGNORE_FILE_EXTENSIONS, [
            'wpstg', 
            'gz',
            'tmp',
        ]);




        $this->ignoreFileBiggerThan = (int)Hooks::applyFilters(BackupSizeCalculator::FILTER_EXPORT_FILES_IGNORE_FILE_BIGGER_THAN, 200 * MB_IN_BYTES);




        $this->ignoreFileExtensionFilesBiggerThan = (array)Hooks::applyFilters(BackupSizeCalculator::FILTER_EXPORT_FILES_IGNORE_LARGE_FILES_BY_EXTENSION, [
            'zip' => 50 * MB_IN_BYTES,
        ]);

 
        $this->ignoreFileExtensions = array_flip($this->ignoreFileExtensions);
    }




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
        $this->filesystemScanner->setRecursiveExcludeRules([
            '**/wp-staging*/**/node_modules', 
        ]);
        $this->filesystemScanner->setLogTitle(static::getTaskTitle());
        $this->filesystemScanner->setQueueCacheName(FileBackupTask::getTaskName());
        $this->filesystemScanner->inject($this->logger, $this->taskQueue, $this->getScannerDto());
    }




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




    protected function scanMuPluginsDirectory(): TaskResponseDto
    {
        if (!$this->jobDataDto->getIsExportingMuPlugins()) {
            return $this->generateResponse();
        }

 
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









    protected function scanWpRootDirectory(): TaskResponseDto
    {
        if (!$this->jobDataDto->getIsExportingOtherWpRootFiles()) {
            return $this->generateResponse();
        }

 
        $stagingSites     = WPStaging::make(Sites::class);
        $stagingSitesDirs = $stagingSites->getStagingDirectories();

        $dirsToSkip = $this->directory->getWpDefaultRootDirectories();
        $dirsToSkip = array_merge($dirsToSkip, $stagingSitesDirs);
        $dirsToSkip = array_merge($dirsToSkip, $this->directory->getWpStagingRestoreDirs());
        $dirsToSkip = array_unique(array_merge($dirsToSkip, $this->jobDataDto->getBackupExcludedDirectories()));
        $dirsToSkip = array_map(function ($path) {
            return rtrim($path, "/");
        }, $dirsToSkip);

        $dirToScan  = $this->directory->getAbsPath();

        $this->filesystemScanner->setOnlyDirectories();
        $this->preScanPath($dirToScan, PartIdentifier::OTHER_WP_ROOT_PART_IDENTIFIER, $dirsToSkip);

        return $this->generateResponse();
    }




    protected function getExcludedDirectories(): array
    {
        $excludedDirs = [];

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








        $excludedDirs = (array)apply_filters(BackupSizeCalculator::FILTER_BACKUP_EXCLUDED_DIRECTORIES, $excludedDirs);

        return $excludedDirs;
    }




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




    protected function updateJobDataDto()
    {
        $scannerDto = $this->filesystemScanner->getFilesystemScannerDto();

        $this->jobDataDto->setDiscoveredFiles($scannerDto->getDiscoveredFiles());
        $this->jobDataDto->setDiscoveredFilesArray($scannerDto->getDiscoveredFilesArray());
        $this->jobDataDto->setFilesystemSize($scannerDto->getFilesystemSize());
        $this->jobDataDto->setTotalDirectories($scannerDto->getTotalDirectories());
    }










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




    private function getActiveThemes(): array
    {
 
        if (!is_multisite()) {
            return $this->pluginInfo->getActiveThemes();
        }

 
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
 
            if ($pluginDir === $pluginsDir) {
                $excludeRules[] = "!" . $plugin;
                continue;
            }

            $excludeRules[] = "!" . $pluginDir;
        }

        return $excludeRules;
    }




    private function getActivePlugins(): array
    {
 
        remove_all_filters(WpAdapter::FILTER_OPTION_ACTIVE_PLUGINS);

 
        if (!is_multisite()) {
            return wp_get_active_and_valid_plugins();
        }

 
        if ($this->jobDataDto->getIsNetworkSiteBackup()) {
            return wp_get_active_and_valid_plugins();
        }

 
        remove_all_filters(WpAdapter::FILTER_SITE_OPTION_ACTIVE_SITEWIDE_PLUGINS);

        return array_merge(wp_get_active_network_plugins(), $this->pluginInfo->getAllActivePluginsInSubsites());
    }
}
