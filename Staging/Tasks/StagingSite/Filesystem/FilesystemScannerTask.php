<?php

namespace WPStaging\Staging\Tasks\StagingSite\Filesystem;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\FilesystemScanner;
use WPStaging\Framework\Filesystem\FilesystemScannerDto;
use WPStaging\Framework\Filesystem\LegacyFileRulesTrait;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathChecker;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Job\Interfaces\FilesystemScannerDtoInterface;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;
use WPStaging\Staging\Sites;
use WPStaging\Staging\Tasks\FileCopierTask;
use WPStaging\Staging\Tasks\StagingTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

/**
 * @todo: In Finalizing PR, re-use the existing cloning filters here
 */
class FilesystemScannerTask extends StagingTask
{
    use LegacyFileRulesTrait;

    /** @var int */
    const STEP_SCAN_WP_ROOT_FILES = 0;

    /** @var int */
    const STEP_SCAN_WP_ADMIN_DIRECTORY = 1;

    /** @var int */
    const STEP_SCAN_WP_INCLUDES_DIRECTORY = 2;

    /** @var int */
    const STEP_SCAN_PLUGINS_DIRECTORY = 3;

    /** @var int */
    const STEP_SCAN_MU_PLUGINS_DIRECTORY = 4;

    /** @var int */
    const STEP_SCAN_THEMES_DIRECTORY = 5;

    /** @var int */
    const STEP_SCAN_UPLOADS_DIRECTORY = 6;

    /** @var int */
    const STEP_SCAN_OTHER_WP_CONTENT_DIRECTORIES = 7;

    /** @var int */
    const STEP_SCAN_EXTRA_DIRECTORIES = 8;

    /** @var string */
    const FILTER_IGNORE_FILE_EXTENSION = 'wpstg.cloning.files.ignore.file_extension';

    /** @var string */
    const FILTER_IGNORE_FILE_BIGGER_THAN = 'wpstg.cloning.files.ignore.file_bigger_than';

    /** @var string */
    const FILTER_EXCLUDE_DIRECTORIES = 'wpstg.cloning.exclude.directories';

    /** @var string */
    const FILTER_LEGACY_EXCLUDE_FILES_FULL_PATH = 'wpstg.clone.excluded_files_full_path';

    /** @var string */
    const FILTER_LEGACY_EXCLUDE_FILES = 'wpstg_clone_excluded_files';

    /** @var string */
    const FILTER_LEGACY_EXCLUDED_FILE_SIZE = 'wpstg_clone_file_size_exclude';

    /**
     * 9 steps for scanning each identifier and the last step to deep scan non-scanned directories
     * @var int
     */
    const TOTAL_STEPS = 10;

    /** @var Directory */
    protected $directory;

    /** @var Filesystem */
    protected $filesystem;

    /** @var FilesystemScanner */
    protected $filesystemScanner;

    /** @var PathChecker */
    protected $pathChecker;

    /** @var array */
    protected $ignoreFileExtensions = [];

    /** @var int */
    protected $ignoreFileBiggerThan = 0;

    /** @var array */
    protected $ignoreFileExtensionFilesBiggerThan = [];

    /** @var array */
    protected $legacyExcludedFileNameRules = [];

    /** @var JobDataDto|FilesystemScannerDtoInterface|StagingOperationDtoInterface $jobDataDto */
    protected $jobDataDto; // @phpstan-ignore-line

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     * @param Directory $directory
     * @param Filesystem $filesystem
     * @param FilesystemScanner $filesystemScanner
     * @param PathChecker $pathChecker
     */
    public function __construct(
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue,
        Directory $directory,
        Filesystem $filesystem,
        FilesystemScanner $filesystemScanner,
        PathChecker $pathChecker
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->directory         = $directory;
        $this->filesystem        = $filesystem;
        $this->filesystemScanner = $filesystemScanner;
        $this->pathChecker       = $pathChecker;
    }

    /**
     * @return string
     */
    public static function getTaskName(): string
    {
        return 'staging_filesystem_scan';
    }

    /**
     * @return string
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

        if ($this->stepsDto->getCurrent() === self::STEP_SCAN_WP_ROOT_FILES) {
            return $this->scanWpRootFiles();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_SCAN_WP_ADMIN_DIRECTORY) {
            return $this->scanWpAdminDirectory();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_SCAN_WP_INCLUDES_DIRECTORY) {
            return $this->scanWpIncludesDirectory();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_SCAN_PLUGINS_DIRECTORY) {
            return $this->scanPluginsDirectories();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_SCAN_MU_PLUGINS_DIRECTORY) {
            return $this->scanMuPluginsDirectory();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_SCAN_THEMES_DIRECTORY) {
            return $this->scanThemesDirectory();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_SCAN_UPLOADS_DIRECTORY) {
            return $this->scanUploadsDirectory();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_SCAN_OTHER_WP_CONTENT_DIRECTORIES) {
            return $this->scanWpContentDirectory();
        }

        if ($this->stepsDto->getCurrent() === self::STEP_SCAN_EXTRA_DIRECTORIES) {
            return $this->scanExtraDirectories();
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
         * Allow user to exclude certain file extensions from being copied.
         */
        $this->ignoreFileExtensions = $this->directory->getExcludedFileExtensions($this->jobDataDto->getExcludeExtensionRules());

        $this->ignoreFileExtensions = (array)apply_filters(self::FILTER_IGNORE_FILE_EXTENSION, $this->ignoreFileExtensions);

        $legacyExcludedFiles = (array)apply_filters(self::FILTER_LEGACY_EXCLUDE_FILES, [
            '.DS_Store',
            '*.git',
            '*.svn',
            '*.tmp',
            'desktop.ini',
            '.gitignore',
            '*.log',
            'web.config',
            '.wp-staging',
            '.wp-staging-cloneable',
        ]);

        $legacyExcludedFilesFullPath = (array)apply_filters(self::FILTER_LEGACY_EXCLUDE_FILES_FULL_PATH, [
            '.htaccess',
            'db.php',
            'object-cache.php',
            'advanced-cache.php',
        ]);

        $this->legacyExcludedFileNameRules = $this->extractLegacyFileNameRules(array_merge($legacyExcludedFiles, $legacyExcludedFilesFullPath));

        $excludeSizeGreaterThanInMb = $this->jobDataDto->getExcludeSizeGreaterThan();

        /**
         * Allow user to exclude files larger than given size from being copied.
         */
        $this->ignoreFileBiggerThan = (int)apply_filters(self::FILTER_IGNORE_FILE_BIGGER_THAN, $excludeSizeGreaterThanInMb * MB_IN_BYTES);
        $legacyFileSizeFilterValue  = apply_filters(self::FILTER_LEGACY_EXCLUDED_FILE_SIZE, $this->ignoreFileBiggerThan);
        if (is_numeric($legacyFileSizeFilterValue)) {
            $this->ignoreFileBiggerThan = (int)$legacyFileSizeFilterValue;
        }

        $legacyExcludedExtensions = $this->extractFileExtensions($this->legacyExcludedFileNameRules);
        $this->ignoreFileExtensions = array_merge($this->ignoreFileExtensions, $legacyExcludedExtensions);

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

            $this->jobDataDto->setExcludedDirectoriesForScanner($excludedDirs);

            $this->stepsDto->setTotal(self::TOTAL_STEPS);
            $this->taskQueue->seek(0);
        }

        $this->filesystemScanner->setFilters($this->ignoreFileBiggerThan, $this->ignoreFileExtensions, $this->ignoreFileExtensionFilesBiggerThan);
        $excludeFileRules = array_values(array_unique(array_merge($this->jobDataDto->getExcludeFileRules(), $this->legacyExcludedFileNameRules)));
        $excludeFolderRules = array_values(array_unique(array_merge(
            $this->jobDataDto->getExcludeFolderRules(),
            $this->extractLegacyFolderNameRules($this->legacyExcludedFileNameRules)
        )));

        $this->filesystemScanner->setNameExcludeRules(
            $excludeFolderRules,
            $excludeFileRules
        );
        $this->filesystemScanner->setRecursiveExcludeRules([
            '**/wp-staging*/**/node_modules', // skip WP Staging plugins' node_modules during the deep scan
        ]);
        $this->filesystemScanner->setLogTitle(static::getTaskTitle());
        $this->filesystemScanner->setQueueCacheName(FileCopierTask::getTaskName());
        $this->filesystemScanner->setEnqueueEmptyDirectories(true);
        $this->filesystemScanner->inject($this->logger, $this->taskQueue, $this->getScannerDto());
    }

    /**
     * Only Scan files in root directory (ABSPATH) but doesn't scan any directory in it.
     * @return TaskResponseDto
     */
    protected function scanWpRootFiles(): TaskResponseDto
    {
        $dirToScan = $this->directory->getAbsPath();
        if ($this->isExcluded($this->directory->getWpAdminDirectory()) && $this->isExcluded($this->directory->getWpIncludesDirectory())) {
            $this->logger->warning('Skipping scanning of WP root files because wp-admin and wp-includes directories are excluded.');
            $this->jobDataDto->setIsRootFilesExcluded(true);
            return $this->generateResponse();
        }

        $excludeRules = [];
        $this->filesystemScanner->setOnlyFiles();
        $this->preScanPath($dirToScan, PartIdentifier::WP_ROOT_FILES_PART_IDENTIFIER, $excludeRules);

        return $this->generateResponse();
    }

    protected function scanWpAdminDirectory(): TaskResponseDto
    {
        $dirToScan = $this->directory->getWpAdminDirectory();
        if ($this->isExcluded($dirToScan)) {
            $this->logger->warning('Skipping scanning of wp-admin directory because it is excluded.');
            $this->jobDataDto->setIsWpAdminExcluded(true);
            return $this->generateResponse();
        }

        $excludeRules = [];
        $this->preScanPath($dirToScan, PartIdentifier::WP_ADMIN_PART_IDENTIFIER, $excludeRules);

        return $this->generateResponse();
    }

    protected function scanWpIncludesDirectory(): TaskResponseDto
    {
        $dirToScan = $this->directory->getWpIncludesDirectory();
        if ($this->isExcluded($dirToScan)) {
            $this->logger->warning('Skipping scanning of wp-includes directory because it is excluded.');
            $this->jobDataDto->setIsWpIncludesExcluded(true);
            return $this->generateResponse();
        }

        $excludeRules = [];
        $this->preScanPath($dirToScan, PartIdentifier::WP_INCLUDES_PART_IDENTIFIER, $excludeRules);

        return $this->generateResponse();
    }

    protected function scanPluginsDirectories(): TaskResponseDto
    {
        $dirToScan = $this->directory->getPluginsDirectory();
        if ($this->isExcluded($dirToScan)) {
            $this->logger->warning('Skipping scanning of plugins directory because it is excluded.');
            $this->jobDataDto->setIsPluginsExcluded(true);
            return $this->generateResponse();
        }

        $excludeRules = [];

        $this->preScanPath($dirToScan, PartIdentifier::PLUGIN_PART_IDENTIFIER, $excludeRules);

        return $this->generateResponse();
    }

    protected function scanMuPluginsDirectory(): TaskResponseDto
    {
        // Early bail: mu-plugins directory doesn't exist
        if (!is_dir($this->directory->getMuPluginsDirectory())) {
            $this->jobDataDto->setIsMuPluginsExcluded(true);
            return $this->generateResponse();
        }

        $dirToScan = $this->directory->getMuPluginsDirectory();
        if ($this->isExcluded($dirToScan)) {
            $this->logger->warning('Skipping scanning of mu-plugins directory because it is excluded.');
            $this->jobDataDto->setIsMuPluginsExcluded(true);
            return $this->generateResponse();
        }

        $excludeRules = [];

        $this->preScanPath($dirToScan, PartIdentifier::MU_PLUGIN_PART_IDENTIFIER, $excludeRules);

        return $this->generateResponse();
    }

    protected function scanThemesDirectory(): TaskResponseDto
    {
        $excludeRules = [];
        $this->filesystemScanner->setCurrentPathScanning(PartIdentifier::THEME_PART_IDENTIFIER);
        $this->filesystemScanner->setupFilesystemQueue();
        $this->filesystemScanner->setRootPath($this->getRootPath());
        $this->filesystemScanner->setExcludeRules($excludeRules);

        $isThemesExcluded = true;
        foreach ($this->directory->getAllThemesDirectories() as $themesDirectory) {
            if ($this->isExcluded($themesDirectory)) {
                continue;
            }

            $isThemesExcluded = false;
            $this->filesystemScanner->preScanPath($themesDirectory);
        }

        $this->filesystemScanner->unlockQueue();
        $this->updateJobDataDto();
        if ($isThemesExcluded) {
            $this->logger->warning('Skipping scanning of themes directories because all are excluded.');
            $this->jobDataDto->setIsThemesExcluded(true);
        }

        return $this->generateResponse();
    }

    protected function scanUploadsDirectory(): TaskResponseDto
    {
        // Early bail: Uploads directory doesn't exist
        if (!is_dir($this->getUploadsDirectory())) {
            $this->jobDataDto->setIsUploadsExcluded(true);
            return $this->generateResponse();
        }

        $dirToScan = $this->getUploadsDirectory();
        if ($this->isExcluded($dirToScan)) {
            $this->logger->warning('Skipping scanning of uploads directory because it is excluded.');
            $this->jobDataDto->setIsUploadsExcluded(true);
            return $this->generateResponse();
        }

        $excludeRules = [];

        $this->preScanPath($dirToScan, PartIdentifier::UPLOAD_PART_IDENTIFIER, $excludeRules);

        return $this->generateResponse();
    }

    /**
     * Scan wp-content directory (wp-content/) but doesn't scan plugins,mu-plugins,themes,uploads folders.
     */
    protected function scanWpContentDirectory(): TaskResponseDto
    {
        $dirToScan = $this->directory->getWpContentDirectory();
        if ($this->isExcluded($dirToScan)) {
            $this->logger->warning('Skipping scanning of wp-content directory because it is excluded.');
            $this->jobDataDto->setIsWpContentExcluded(true);
            return $this->generateResponse();
        }

        $excludeRules = array_map(function ($path) {
            return rtrim($path, '/');
        }, $this->directory->getDefaultWordPressFolders());

        $this->preScanPath($dirToScan, PartIdentifier::WP_CONTENT_PART_IDENTIFIER, $excludeRules);

        return $this->generateResponse();
    }

    /**
     * Scan Extra Directories in WP Root (ABSPATH) except wp-admin, wp-includes, wp-content.
     */
    protected function scanExtraDirectories(): TaskResponseDto
    {
        /** @var Sites */
        $stagingSites     = WPStaging::make(Sites::class);
        $stagingSitesDirs = $stagingSites->getStagingDirectories();

        $dirsToSkip = $this->directory->getWpDefaultRootDirectories();
        $dirsToSkip = array_merge($dirsToSkip, $stagingSitesDirs);
        $dirsToSkip = array_unique(array_merge($dirsToSkip, $this->jobDataDto->getExcludedDirectoriesForScanner()));

        $excludeRules = array_map(function ($path) {
            return rtrim($path, '/');
        }, $dirsToSkip);

        $this->filesystemScanner->setCurrentPathScanning(PartIdentifier::WP_ROOT_PART_IDENTIFIER);
        $this->filesystemScanner->setupFilesystemQueue();
        $this->filesystemScanner->setRootPath($this->getRootPath());
        $this->filesystemScanner->setExcludeRules($excludeRules);

        $isExtraDirectoriesExcluded = true;
        foreach ($this->jobDataDto->getExtraDirectories() as $extraDirectory) {
            if ($this->isExcluded($extraDirectory)) {
                continue;
            }

            $isExtraDirectoriesExcluded = false;
            $this->filesystemScanner->preScanPath($extraDirectory);
        }

        $this->filesystemScanner->unlockQueue();
        $this->updateJobDataDto();
        if ($isExtraDirectoriesExcluded) {
            $this->logger->info('No extra directories to scan.');
        }

        return $this->generateResponse();
    }


    protected function getRootPath(): string
    {
        return $this->directory->getAbsPath();
    }

    /**
     * @return array
     */
    protected function getExcludedDirectories(): array
    {
        // Only one filter must run: the multisite one on multisite, the normal one otherwise.
        $useMultisiteFilter = $this->shouldUseMultisiteLegacyExcludedDirectoriesFilter();
        $excludedDirs       = $this->directory->getDefaultExcludedDirectories(!$useMultisiteFilter);

        if ($useMultisiteFilter) {
            $excludedDirs = (array) apply_filters(Directory::FILTER_CLONE_MU_EXCLUDED_FOLDERS, $excludedDirs);
        }

        $excludedDirs = (array) apply_filters(self::FILTER_EXCLUDE_DIRECTORIES, $excludedDirs);

        return $this->directory->ensureWpStagingDataDirectoriesExcluded($excludedDirs);
    }

    protected function shouldUseMultisiteLegacyExcludedDirectoriesFilter(): bool
    {
        return defined('WPSTGPRO_VERSION') && is_multisite();
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

        $scannerDto->setExcludedDirectories($this->jobDataDto->getExcludedDirectoriesForScanner() ?? []);
        $scannerDto->setDiscoveredFiles($this->jobDataDto->getDiscoveredFiles() ?? 0);
        $scannerDto->setDiscoveredFilesArray($this->jobDataDto->getDiscoveredFilesIdentifiers() ?? []);
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
        $this->jobDataDto->setDiscoveredFilesIdentifiers($scannerDto->getDiscoveredFilesArray());
        $this->jobDataDto->setFilesystemSize($scannerDto->getFilesystemSize());
        $this->jobDataDto->setTotalDirectories($scannerDto->getTotalDirectories());
        $this->jobDataDto->mergeTmpExcludedFullPaths($scannerDto->getFilesExcludedInRequest());
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

    protected function isExcluded(string $directory): bool
    {
        return $this->pathChecker->isPathInPathsList($directory, $this->jobDataDto->getExcludedDirectories());
    }
}
