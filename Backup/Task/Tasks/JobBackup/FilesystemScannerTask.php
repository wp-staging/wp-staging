<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use DirectoryIterator;
use Exception;
use SplFileInfo;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\FilesystemExceptions;
use WPStaging\Framework\Filesystem\FilterableDirectoryIterator;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Exceptions\DiskNotWritableException;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Task\FileBackupTask;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\PluginInfo;

class FilesystemScannerTask extends BackupTask
{
    /**
     * @var string
     */
    const PATH_SEPARATOR = '::';

    /** @var Directory */
    protected $directory;

    /** @var Filesystem */
    protected $filesystem;

    /** @var SeekableQueueInterface */
    protected $compressorQueue;

    /** @var PathIdentifier */
    protected $pathIdentifier;

    /** @var PluginInfo */
    private $pluginInfo;

    protected $ignoreFileExtensions;
    protected $ignoreFileBiggerThan;
    protected $ignoreFileExtensionFilesBiggerThan;

    /**
     * Store information of file discovered in each parent path
     * @var array
     */
    protected $files;

    /**
     * The parent path which is currently being scanned
     * Can be either plugins, mu_plugins, themes, uploads or other
     * Where other means base wp-content directory but skipping plugins, mu_plugins, themes and uploads as they are handle separately
     * @var string
     */
    protected $currentPathScanning;

    /** @var bool */
    protected $isSiteHostedOnWordPressCom = false;

    /**
     * @param Directory $directory
     * @param PathIdentifier $pathIdentifier
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     * @param SeekableQueueInterface $compressorQueue
     * @param Filesystem $filesystem
     * @param SiteInfo $siteInfo
     */
    public function __construct(
        Directory $directory,
        PathIdentifier $pathIdentifier,
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue,
        SeekableQueueInterface $compressorQueue,
        Filesystem $filesystem,
        SiteInfo $siteInfo,
        PluginInfo $pluginInfo
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->directory                  = $directory;
        $this->filesystem                 = $filesystem;
        $this->pathIdentifier             = $pathIdentifier;
        $this->compressorQueue            = $compressorQueue;
        $this->isSiteHostedOnWordPressCom = $siteInfo->isHostedOnWordPressCom();
        $this->pluginInfo                 = $pluginInfo;
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

        if ($this->stepsDto->getCurrent() === 0) {
            $this->currentPathScanning = BackupOtherFilesTask::IDENTIFIER;
            $this->setupCompressorQueue();
            $this->scanWpContentDirectory();
            $this->unlockQueue();
            return $this->generateResponse();
        }

        if ($this->stepsDto->getCurrent() === 1) {
            $this->currentPathScanning = BackupPluginsTask::IDENTIFIER;
            $this->setupCompressorQueue();
            $this->scanPluginsDirectories();
            $this->unlockQueue();
            return $this->generateResponse();
        }

        if ($this->stepsDto->getCurrent() === 2) {
            $this->currentPathScanning = BackupMuPluginsTask::IDENTIFIER;
            $this->setupCompressorQueue();
            $this->scanMuPluginsDirectory();
            $this->unlockQueue();
            return $this->generateResponse();
        }

        if ($this->stepsDto->getCurrent() === 3) {
            $this->currentPathScanning = BackupThemesTask::IDENTIFIER;
            $this->setupCompressorQueue();
            $this->scanThemesDirectory();
            $this->unlockQueue();
            return $this->generateResponse();
        }

        if ($this->stepsDto->getCurrent() === 4) {
            $this->currentPathScanning = BackupUploadsTask::IDENTIFIER;
            $this->setupCompressorQueue();
            $this->scanUploadsDirectory();
            $this->unlockQueue();
            return $this->generateResponse();
        }

        while (!$this->isThreshold() && !$this->stepsDto->isFinished()) {
            $this->scan();
        }

        if ($this->stepsDto->isFinished()) {
            $this->stepsDto->setManualPercentage(100);
            $this->logger->info(sprintf(__('Finished discovering Files. (%d files)', 'wp-staging'), $this->jobDataDto->getDiscoveredFiles()));
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
            $this->logger->info(sprintf(__('Discovering Files (%d files)', 'wp-staging'), $this->jobDataDto->getDiscoveredFiles()));
        }

        return $this->generateResponse(false);
    }

    /**
     * @return void
     */
    protected function setupCompressorQueue()
    {
        $compressorTaskName = FileBackupTask::getTaskName() . '_' . $this->currentPathScanning;
        $this->compressorQueue->setup($compressorTaskName, SeekableQueueInterface::MODE_WRITE);
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
     * @throws DiskNotWritableException
     */
    protected function scan()
    {
        try {
            $path = $this->taskQueue->dequeue();

            if (is_null($path)) {
                throw new FinishedQueueException('Directory Scanner Queue is Finished');
            }

            if (empty($path)) {
                return;
            }

            list($path, $linkPath) = $this->resolvePath($path);

            $path = untrailingslashit($this->filesystem->normalizePath($path, true));

            if (!file_exists($path)) {
                throw new Exception("$path is not a directory. Skipping...");
            }

            $this->setupCompressorQueue();
            $this->recursivePathScanning($path, $linkPath);
        } catch (FinishedQueueException $e) {
            try {
                WPStaging::make(DiskWriteCheck::class)->checkPathCanStoreEnoughBytes($this->directory->getPluginUploadsDirectory(), $this->jobDataDto->getFilesystemSize());
            } catch (DiskNotWritableException $e) {
                throw $e;
            } catch (\RuntimeException $e) {
                // soft error, no action needed, but log
                $this->logger->debug($e->getMessage());
            }

            $this->stepsDto->finish();

            return;
        } catch (\OutOfBoundsException $e) {
            $this->logger->debug($e->getMessage());
        } catch (Exception $e) {
            $this->logger->warning($e->getMessage());
        }
    }

    /**
     * Resolve path on non-wp.com sites (sites with no symlinks structure) to [base_directory, path, '']
     * Resolve path on wp.com sites (sites with symlinks structure) to [base_directory, path, link]
     * Where base_directory can be either plugins, mu_plugins, themes, uploads or other etc
     * Where path is the path to scan
     * Where link is the link to path (empty in case of non-wp.com sites)
     * @param string $pathToResolve - Path to resolve in format base_directory::path::link or base_directory::path
     * @return array [string pathToScan, string linkToPath]
     */
    protected function resolvePath(string $pathToResolve): array
    {
        $linkPath  = '';
        $pathInfos = explode(self::PATH_SEPARATOR, $pathToResolve);
        // On non-wp.com sites, we don't have link, we only have base directory and path to scan
        // On wp.com sites, we have base directory, path to scan and link to path, so path info contains 3 elements
        if (count($pathInfos) > 2) {
            // link to path
            $linkPath = $pathInfos[2];
        }

        // base directory
        $this->currentPathScanning = $pathInfos[0];

        // path to scan
        $path = $pathInfos[1];

        return [$path, $linkPath];
    }

    /**
     * @return void
     */
    protected function setupFilesystemScanner()
    {
        if ($this->stepsDto->getTotal() > 0) {
            return;
        }

        $excludedDirs = array_map(function ($path) {
            return $this->filesystem->normalizePath($path, true);
        }, $this->getExcludedDirectories());

        $this->jobDataDto->setExcludedDirectories($excludedDirs);

        // Browsers will do mime type sniffing on download. Adding binary to header avoids parsing as text/plain and forces download.
        //if (!$this->jobDataDto->getIsMultipartBackup()) {
        //    $this->enqueueFileInBackup(new \SplFileInfo(WPSTG_PLUGIN_DIR . 'Backup/wpstgBackupHeader.txt'));
        //}

        $this->stepsDto->setTotal(6);
        $this->taskQueue->seek(0);
    }

    /**
     * Scan wp-content directory(wp-content/) but doesn't scan sub folders.
     *
     * @return void
     */
    protected function scanWpContentDirectory()
    {
        if (!$this->jobDataDto->getIsExportingOtherWpContentFiles()) {
            return;
        }

        // wp-content root
        $wpContentIt = new DirectoryIterator($this->directory->getWpContentDirectory());

        foreach ($wpContentIt as $otherFiles) {
            // Early bail: We don't dots
            if ($this->isDot($otherFiles)) {
                continue;
            }

            if ($otherFiles->isLink()) {
                // We will only scan file symlinks in wp content dir
                $this->processLink($otherFiles, $scanDirectory = false);
                continue;
            }

            // Handle files at root level of wp-content
            if ($otherFiles->isFile()) {
                $this->enqueueFileInBackup($otherFiles);
                continue;
            }

            if ($otherFiles->isDir()) {
                if (!in_array($this->filesystem->normalizePath($otherFiles->getPathname(), true), $this->directory->getDefaultWordPressFolders())) {
                    $this->enqueueDirToBeScanned($otherFiles);
                }
            }
        }
    }

    /**
     * Scan plugins directory(wp-content/plugins) but doesn't scan sub folders.
     *
     * @return void
     */
    protected function scanPluginsDirectories()
    {
        if (!$this->jobDataDto->getIsExportingPlugins()) {
            return;
        }

        $pluginsIt = new DirectoryIterator($this->directory->getPluginsDirectory());

        foreach ($pluginsIt as $plugin) {
            if ($this->isDot($plugin)) {
                continue;
            }

            if ($plugin->isLink()) {
                $this->processLink($plugin);
                continue;
            }

            if ($plugin->isFile()) {
                $this->enqueueFileInBackup($plugin);
                continue;
            }

            if ($this->canEnqueuePluginDir($plugin)) {
                $this->enqueueDirToBeScanned($plugin);
            }
        }
    }

    /**
     * Scan mu-plugins directory(wp-content/mu-plugins) but doesn't scan sub folders.
     *
     * @return void
     */
    protected function scanMuPluginsDirectory()
    {
        if (!$this->jobDataDto->getIsExportingMuPlugins()) {
            return;
        }

        // Early bail: mu-plugins directory doesn't exist
        if (!is_dir($this->directory->getMuPluginsDirectory())) {
            return;
        }

        $muPluginsIt = new DirectoryIterator($this->directory->getMuPluginsDirectory());

        /** @var SplFileInfo $muPlugin */
        foreach ($muPluginsIt as $muPlugin) {
            if ($this->isDot($muPlugin)) {
                continue;
            }

            // We do not scan mu plugins links in wordpress.com
            if ($muPlugin->isLink()) {
                continue;
            }

            if ($muPlugin->isFile()) {
                if ($muPlugin->getBasename() === 'wp-staging-optimizer.php') {
                    continue;
                }

                $this->enqueueFileInBackup($muPlugin);
            }

            if ($muPlugin->isDir()) {
                $this->enqueueDirToBeScanned($muPlugin);
            }
        }
    }

    /**
     * Scan themes directory(wp-content/themes) but doesn't scan sub folders.
     *
     * @return void
     */
    protected function scanThemesDirectory()
    {
        if (!$this->jobDataDto->getIsExportingThemes()) {
            return;
        }

        foreach ($this->directory->getAllThemesDirectories() as $themesDirectory) {
            $themesIt = new DirectoryIterator($themesDirectory);

            foreach ($themesIt as $theme) {
                if ($this->isDot($theme)) {
                    continue;
                }

                if ($theme->isLink()) {
                    $this->processLink($theme);
                    continue;
                }

                if ($theme->isFile()) {
                    $this->enqueueFileInBackup($theme);
                    continue;
                }

                if ($this->canEnqueueThemeDir($theme)) {
                    $this->enqueueDirToBeScanned($theme);
                }
            }
        }
    }

    /**
     * @return void
     */
    protected function scanUploadsDirectory()
    {
        if (!$this->jobDataDto->getIsExportingUploads()) {
            return;
        }

        // Early bail: Uploads directory doesn't exist
        if (!is_dir($this->getUploadsDirectory())) {
            return;
        }

        $uploadsIt = new DirectoryIterator($this->getUploadsDirectory());

        foreach ($uploadsIt as $uploadItem) {
            // Early bail: We don't touch links
            if ($uploadItem->isLink() || $this->isDot($uploadItem)) {
                continue;
            }

            if ($uploadItem->isFile()) {
                $this->enqueueFileInBackup($uploadItem);
            } elseif ($uploadItem->isDir()) {
                /*
                 * This is a default WordPress year-month uploads folder.
                 *
                 * Here we break down the uploads folder by months, considering it's often the largest folder in a website,
                 * and we need to be able to scan each folder in one request.
                 */
                if (is_numeric($uploadItem->getBasename()) && $uploadItem->getBasename() > 1970 && $uploadItem->getBasename() < 2100) {
                    /** @var SplFileInfo $uploadMonth */
                    foreach (new DirectoryIterator($uploadItem->getPathname()) as $uploadMonth) {
                        // Early bail: We don't touch links
                        if ($uploadMonth->isLink() || $this->isDot($uploadMonth)) {
                            continue;
                        }

                        if ($uploadMonth->isFile()) {
                            $this->enqueueFileInBackup($uploadMonth);
                        }

                        if ($uploadMonth->isDir()) {
                            $this->enqueueDirToBeScanned($uploadMonth);
                        }
                    }
                } else {
                    if ($uploadItem->isFile()) {
                        $this->enqueueFileInBackup($uploadItem);
                    }

                    if ($uploadItem->isDir()) {
                        $this->enqueueDirToBeScanned($uploadItem);
                    }
                }
            }
        }
    }

    /**
     * @param SplFileInfo $file
     * @return void
     */
    protected function enqueueFileInBackup(SplFileInfo $file, string $link = null)
    {
        $normalizedPath = $this->filesystem->normalizePath($file->getPathname(), true);
        $fileSize       = $file->getSize();

        $fileExtension  = $file->getExtension();

        // Lazy-built relative path
        $relativePath = str_replace($this->filesystem->normalizePath(ABSPATH, true), '', $normalizedPath);

        if ($this->canExcludeLogFile($fileExtension) || $this->canExcludeCacheFile($fileExtension) || isset($this->ignoreFileExtensions[$fileExtension])) {
            // Early bail: File has an ignored extension
            $this->logger->info(sprintf(
                __('%s: Skipped file "%s." Extension "%s" is excluded by rule.', 'wp-staging'),
                static::getTaskTitle(),
                $relativePath,
                $fileExtension
            ));

            return;
        }

        if (isset($this->ignoreFileExtensionFilesBiggerThan[$fileExtension])) {
            if ($fileSize > $this->ignoreFileExtensionFilesBiggerThan[$fileExtension]) {
                // Early bail: File bigger than expected for given extension
                $this->logger->info(sprintf(
                    __('%s: Skipped file "%s" (%s). It exceeds the maximum allowed file size for files with the extension "%s" (%s).', 'wp-staging'),
                    static::getTaskTitle(),
                    $relativePath,
                    size_format($fileSize),
                    $fileExtension,
                    size_format($this->ignoreFileExtensionFilesBiggerThan[$fileExtension])
                ));

                return;
            }
        } elseif ($fileSize > $this->ignoreFileBiggerThan) {
            // Early bail: File is larger than max allowed size.
            $this->logger->info(sprintf(
                __('%s: Skipped file "%s" (%s). It exceeds the maximum file size for backup (%s).', 'wp-staging'),
                static::getTaskTitle(),
                $relativePath,
                size_format($fileSize),
                size_format($this->ignoreFileBiggerThan)
            ));

            return;
        }

        $this->jobDataDto->setDiscoveredFiles($this->jobDataDto->getDiscoveredFiles() + 1);
        $filesDiscoveredForCurrentPath = $this->jobDataDto->getDiscoveredFilesByCategory($this->currentPathScanning) + 1;
        $this->jobDataDto->setDiscoveredFilesByCategory($this->currentPathScanning, $filesDiscoveredForCurrentPath);
        $this->jobDataDto->setFilesystemSize($this->jobDataDto->getFilesystemSize() + $fileSize);

        // $this->logger->debug('Enqueueing file: ' . rtrim($normalizedPath, '/'));

        if ($link !== null) {
            $linkPath = $this->filesystem->normalizePath($link, true);
            $path = rtrim($relativePath, '/') . self::PATH_SEPARATOR . rtrim($linkPath, '/');
            $this->compressorQueue->enqueue($path);
            return;
        }

        $this->compressorQueue->enqueue(rtrim($relativePath, '/'));
    }

    /**
     * @param  string $fileExtension
     * @return bool
     */
    private function canExcludeLogFile(string $fileExtension): bool
    {
        if ($fileExtension !== 'log') {
            return false;
        }

        if (!$this->jobDataDto->getIsExcludingLogs()) {
            return false;
        }

        return true;
    }

    /**
     * @param  string $fileExtension
     * @return bool
     */
    private function canExcludeCacheFile(string $fileExtension): bool
    {
        if ($fileExtension !== 'cache') {
            return false;
        }

        if (!$this->jobDataDto->getIsExcludingCaches()) {
            return false;
        }

        return true;
    }

    /**
     * @param SplFileInfo $dir
     * @return bool
     */
    private function canExcludeCacheDir(SplFileInfo $dir): bool
    {
        if (!$dir->isDir()) {
            return false;
        }

        if (!$this->jobDataDto->getIsExcludingCaches()) {
            return false;
        }

        if (!$this->isPathContainsCache($dir->getRealPath())) {
            return false;
        }

        $this->logger->info(sprintf(
            __('%s: Skipped directory "%s". Excluded by smart exclusion rule: Excluding cache folder.', 'wp-staging'),
            static::getTaskTitle(),
            $dir->getRealPath()
        ));

        return true;
    }

    /**
     * Check if "cache" is one of the directory names.
     *
     * @param string $path
     * @return bool
     */
    private function isPathContainsCache(string $path): bool
    {
        $pathParts = explode(DIRECTORY_SEPARATOR, $path);

        return in_array('cache', $pathParts);
    }

    /**
     * @param SplFileInfo $dir
     * @param SplFileInfo $link
     * @return void
     */
    protected function enqueueDirToBeScanned(SplFileInfo $dir, SplFileInfo $link = null)
    {
        $normalizedPath = $this->filesystem->normalizePath($dir->getPathname(), true);

        if ($this->isExcludedDirectory($dir->getPathname()) || $this->canExcludeCacheDir($dir)) {
            return;
        }

        if ($link !== null && $this->isExcludedDirectory($link->getPathname())) {
            return;
        }

        $this->jobDataDto->setTotalDirectories($this->jobDataDto->getTotalDirectories() + 1);

        // $this->logger->debug("Enqueueing directory: $normalizedPath");

        if ($link !== null) {
            $linkPath = $this->filesystem->normalizePath($link->getPathname(), true);
            $this->taskQueue->enqueue($this->currentPathScanning . self::PATH_SEPARATOR . $normalizedPath . self::PATH_SEPARATOR . $linkPath);
            return;
        }

        // we need to know
        $this->taskQueue->enqueue($this->currentPathScanning . self::PATH_SEPARATOR . $normalizedPath);
    }

    /**
     * @param SplFileInfo $fileInfo
     * @return bool
     */
    protected function isDot(SplFileInfo $fileInfo): bool
    {
        return $fileInfo->getBasename() === '.' || $fileInfo->getBasename() === '..';
    }

    /**
     * @param SplFileInfo $linkInfo
     * @param bool $scanDirectory
     * @return void
     */
    protected function processLink(SplFileInfo $linkInfo, bool $scanDirectory = true)
    {
        // Bail if no link
        if (!$linkInfo->isLink()) {
            return;
        }

        // Early bail: We don't touch links if it is not wordpress.com
        if (!$this->isSiteHostedOnWordPressCom) {
            return;
        }

        $linkTarget = $linkInfo->getRealPath();
        $fileInfo   = new SplFileInfo($linkTarget);
        if ($fileInfo->isLink()) {
            return;
        }

        if ($fileInfo->isFile()) {
            $this->enqueueFileInBackup($fileInfo, $linkInfo->getPathname());
            return;
        }

        if ($fileInfo->isDir() && $scanDirectory) {
            $this->enqueueDirToBeScanned($fileInfo, $linkInfo);
            return;
        }
    }

    /**
     * @param string $path - Path to scan
     * @param string $link - If original $path is resolved from link, then this is the link
     *                       We need it to keep original path after restore
     *                       e.g. $link = /var/www/html/wp-content/themes/twentytwenty is a link to /var/www/libs/themes/twentytwenty (a $path)
     * @return void
     * @throws FilesystemExceptions
     */
    protected function recursivePathScanning(string $path, string $link = '')
    {
        $iterator = (new FilterableDirectoryIterator())
            ->setDirectory(trailingslashit($path))
            ->setRecursive(false)
            ->setDotSkip()
            ->setWpRootPath(ABSPATH)
            ->get();

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            // Always check link first otherwise it may be treated as directory
            if ($item->isLink()) {
                continue;
            }

            $linkPath = '';
            if (!empty($link)) {
                $linkPath = trailingslashit($link) . $item->getFilename();
            }

            if ($item->isDir() && !$this->isExcludedDirectory($item->getPathname())) {
                $this->recursivePathScanning($item->getPathname(), $linkPath);
                continue;
            }

            if ($item->isFile()) {
                $this->enqueueFileInBackup($item, $linkPath);
            }
        }
    }

    /**
     * @return void
     */
    protected function unlockQueue()
    {
        $this->compressorQueue->shutdown();
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isExcludedDirectory(string $path): bool
    {
        $normalizedPath = $this->filesystem->normalizePath($path, true);

        if (in_array($normalizedPath, $this->jobDataDto->getExcludedDirectories())) {
            $relativePathForLogging = str_replace($this->filesystem->normalizePath(WP_CONTENT_DIR, true), '', $normalizedPath);

            $this->logger->info(sprintf(
                __('%s: Skipped directory "%s". Excluded by rule', 'wp-staging'),
                static::getTaskTitle(),
                $relativePathForLogging
            ));

            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    protected function getExcludedDirectories(): array
    {
        $excludedDirs = [];

        $excludedDirs[] = WPSTG_PLUGIN_DIR;
        $excludedDirs[] = $this->directory->getPluginUploadsDirectory();
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

    /**
     * @param DirectoryIterator $theme
     * @return bool
     */
    private function canEnqueueThemeDir(DirectoryIterator $theme): bool
    {
        if (!$theme->isDir()) {
            return false;
        }

        if (!$this->jobDataDto->getIsExcludingUnusedThemes()) {
            return true;
        }

        if (in_array($theme->getRealPath(), $this->getActiveThemes())) {
            return true;
        }

        return false;
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

    /**
     * @param DirectoryIterator $plugin
     * @return bool
     */
    private function canEnqueuePluginDir(DirectoryIterator $plugin): bool
    {
        if (!$plugin->isDir()) {
            return false;
        }

        if (!$this->jobDataDto->getIsExcludingDeactivatedPlugins()) {
            return true;
        }

        $activePlugins = array_unique($this->getActivePlugins());

        foreach ($activePlugins as $activePlugin) {
            if (strpos($activePlugin, $plugin->getRealPath()) !== false) {
                return true;
            }
        }

        return false;
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
