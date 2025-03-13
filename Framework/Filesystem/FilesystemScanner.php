<?php

namespace WPStaging\Framework\Filesystem;

use OutOfBoundsException;
use RuntimeException;
use SplFileInfo;
use Throwable;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\PluginInfo;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class FilesystemScanner extends AbstractFilesystemScanner
{
    /** @var SeekableQueueInterface */
    protected $filesystemQueue;

    /** @var SeekableQueueInterface */
    protected $taskQueue;

    /** @var LoggerInterface */
    protected $logger;

    /** @var FilesystemScannerDto */
    protected $scannerDto;

    /** @var string */
    protected $logTitle = '';

    /** @var string */
    protected $queueCacheName = '';

    /** @var int */
    protected $ignoreFileBiggerThan = 0;

    /** @var array */
    protected $ignoreFileExtensions = [];

    /** @var array */
    protected $ignoreFileExtensionFilesBiggerThan = [];

    /** @var bool */
    protected $isSiteHostedOnWordPressCom = false;

    /**
     * @param Directory $directory
     * @param PathIdentifier $pathIdentifier
     * @param Filesystem $filesystem
     * @param PluginInfo $pluginInfo
     * @param SiteInfo $siteInfo
     * @param SeekableQueueInterface $filesystemQueue
     */
    public function __construct(
        Directory $directory,
        PathIdentifier $pathIdentifier,
        Filesystem $filesystem,
        PluginInfo $pluginInfo,
        SiteInfo $siteInfo,
        SeekableQueueInterface $filesystemQueue
    ) {
        parent::__construct($directory, $pathIdentifier, $filesystem, $pluginInfo);
        $this->isSiteHostedOnWordPressCom = $siteInfo->isHostedOnWordPressCom();
        $this->filesystemQueue            = $filesystemQueue;
    }

    /**
     * @param int $ignoreFileBiggerThan
     * @param array $ignoreFileExtensions
     * @param array $ignoreFileExtensionFilesBiggerThan
     * @return void
     */
    public function setFilters(int $ignoreFileBiggerThan, array $ignoreFileExtensions, array $ignoreFileExtensionFilesBiggerThan)
    {
        $this->ignoreFileBiggerThan               = $ignoreFileBiggerThan;
        $this->ignoreFileExtensions               = $ignoreFileExtensions;
        $this->ignoreFileExtensionFilesBiggerThan = $ignoreFileExtensionFilesBiggerThan;
    }

    /**
     * @return void
     */
    public function setupFilesystemQueue()
    {
        $fileBackupQueueCacheName = $this->queueCacheName . '_' . $this->currentPathScanning;
        $this->filesystemQueue->setup($fileBackupQueueCacheName, SeekableQueueInterface::MODE_WRITE);
    }

    /**
     * @param string $logTitle
     * @return void
     */
    public function setLogTitle(string $logTitle)
    {
        $this->logTitle = $logTitle;
    }

    /**
     * @param string $queueCacheName
     * @return void
     */
    public function setQueueCacheName(string $queueCacheName)
    {
        $this->queueCacheName = $queueCacheName;
    }

    /**
     * @param LoggerInterface $logger
     * @param SeekableQueueInterface $taskQueue
     * @param FilesystemScannerDto $scannerDto
     * @return void
     */
    public function inject(LoggerInterface $logger, SeekableQueueInterface $taskQueue, FilesystemScannerDto $scannerDto)
    {
        $this->logger     = $logger;
        $this->taskQueue  = $taskQueue;
        $this->scannerDto = $scannerDto;
    }

    public function getFilesystemScannerDto(): FilesystemScannerDto
    {
        return $this->scannerDto;
    }

    /**
     * @return void
     */
    public function unlockQueue()
    {
        $this->filesystemQueue->shutdown();
    }

    /**
     * @return void
     * @throws FinishedQueueException
     * @throws DiskNotWritableException
     */
    public function processQueue()
    {
        try {
            $path = $this->taskQueue->dequeue();
            if ($path === null) {
                throw new FinishedQueueException('Directory Scanner Queue is Finished');
            }

            $this->processPath($path);
        } catch (FinishedQueueException $ex) {
            try {
                WPStaging::make(DiskWriteCheck::class)->checkPathCanStoreEnoughBytes($this->directory->getPluginUploadsDirectory(), $this->scannerDto->getFilesystemSize());
            } catch (DiskNotWritableException $e) {
                throw $e;
            } catch (RuntimeException $e) {
                // soft error, no action needed, but log
                $this->logger->debug($e->getMessage());
            }

            throw $ex;
        } catch (OutOfBoundsException $e) {
            $this->logger->debug($e->getMessage());
        } catch (Throwable $e) {
            $this->logger->warning($e->getMessage());
        }
    }

    /**
     * @return void
     */
    protected function preRecursivePathScanningStep()
    {
        $this->setupFilesystemQueue();
    }

    /**
     * @param SplFileInfo $file
     * @param string $linkPath
     * @return void
     * @throws FinishedQueueException
     */
    protected function processFile(SplFileInfo $file, string $linkPath = '')
    {
        $normalizedPath = $this->filesystem->normalizePath($file->getPathname(), true);
        $fileSize       = $file->getSize();

        $fileExtension  = $file->getExtension();

        // Lazy-built relative path
        $relativePath = str_replace($this->filesystem->normalizePath(ABSPATH, true), '', $normalizedPath);

        if ($this->canExcludeLogFile($fileExtension) || $this->canExcludeCacheFile($fileExtension) || isset($this->ignoreFileExtensions[$fileExtension])) {
            // Early bail: File has an ignored extension
            $this->logger->info(sprintf(
                '%s: Skipped file "%s." Extension "%s" is excluded by rule.',
                esc_html($this->logTitle),
                esc_html($relativePath),
                esc_html($fileExtension)
            ));

            return;
        }

        if (isset($this->ignoreFileExtensionFilesBiggerThan[$fileExtension])) {
            if ($fileSize > $this->ignoreFileExtensionFilesBiggerThan[$fileExtension]) {
                // Early bail: File bigger than expected for given extension
                $this->logger->info(sprintf(
                    '%s: Skipped file "%s" (%s). It exceeds the maximum allowed file size for files with the extension "%s" (%s).',
                    esc_html($this->logTitle),
                    esc_html($relativePath),
                    size_format($fileSize),
                    esc_html($fileExtension),
                    size_format($this->ignoreFileExtensionFilesBiggerThan[$fileExtension])
                ));

                return;
            }
        } elseif ($fileSize > $this->ignoreFileBiggerThan) {
            // Early bail: File is larger than max allowed size.
            $this->logger->info(sprintf(
                '%s: Skipped file "%s" (%s). It exceeds the maximum file size for backup (%s).',
                esc_html($this->logTitle),
                esc_html($relativePath),
                size_format($fileSize),
                size_format($this->ignoreFileBiggerThan)
            ));

            return;
        }

        $this->scannerDto->incrementDiscoveredFiles();
        $this->scannerDto->incrementDiscoveredFilesByCategory($this->currentPathScanning);
        $this->scannerDto->addFilesystemSize($fileSize);

        if (!empty($linkPath)) {
            $linkPath     = $this->filesystem->normalizePath($linkPath, true);
            $relativePath = $this->replaceEOLsWithPlaceholders($relativePath);
            $path = rtrim($relativePath, '/') . self::PATH_SEPARATOR . rtrim($linkPath, '/');
            $this->filesystemQueue->enqueue($path);
            return;
        }

        $relativePath = $this->replaceEOLsWithPlaceholders($relativePath);
        $this->filesystemQueue->enqueue(rtrim($relativePath, '/'));
    }

    /**
     * @param SplFileInfo $dir
     * @param SplFileInfo|null $link
     * @return void
     */
    protected function processDirectory(SplFileInfo $dir, $link = null)
    {
        if ($this->isUploadsYearMonthDirectory($dir)) {
            $this->preScanPath($dir->getPathname());
            return;
        }

        $normalizedPath = $this->filesystem->normalizePath($dir->getPathname(), true);

        if ($this->isExcludedDirectory($dir->getPathname()) || $this->canExcludeCacheDir($dir)) {
            return;
        }

        if ($link !== null && $this->isExcludedDirectory($link->getPathname())) {
            return;
        }

        if ($link !== null) {
            $linkPath = $this->filesystem->normalizePath($link->getPathname(), true);
            $this->taskQueue->enqueue($this->currentPathScanning . self::PATH_SEPARATOR . $normalizedPath . self::PATH_SEPARATOR . $linkPath);
            return;
        }

        // we need to know
        $this->taskQueue->enqueue($this->currentPathScanning . self::PATH_SEPARATOR . $normalizedPath);
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isExcludedDirectory(string $path): bool
    {
        $normalizedPath = $this->filesystem->normalizePath($path, true);

        if (in_array($normalizedPath, $this->scannerDto->getExcludedDirectories())) {
            $relativePathForLogging = str_replace($this->filesystem->normalizePath(WP_CONTENT_DIR, true), '', $normalizedPath);

            $this->logger->info(sprintf(
                '%s: Skipped directory "%s". Excluded by rule',
                esc_html($this->logTitle),
                esc_html($relativePathForLogging)
            ));

            return true;
        }

        return false;
    }

    /**
     * RecursivePathScanning method extended to include exclude filter and directory increment
     * @inheritdoc
     */
    protected function recursivePathScanning(string $path, string $link = '')
    {
        if ($this->isExcludedDirectory($path)) {
            return;
        }

        $this->scannerDto->incrementTotalDirectories();

        parent::recursivePathScanning($path, $link);
    }

    /**
     * @param \SplFileInfo $dir
     * @return bool
     */
    protected function isUploadsYearMonthDirectory(SplFileInfo $dir): bool
    {
        if ($this->currentPathScanning !== PartIdentifier::UPLOAD_PART_IDENTIFIER) {
            return false;
        }

        $parentDir = $dir->getPathInfo();
        if ($parentDir === false) {
            return false;
        }

        if ($this->filesystem->normalizePath($parentDir->getPathname(), true) !== $this->directory->getUploadsDirectory()) {
            return false;
        }

        /**
         * This is a default WordPress year-month uploads folder.
         *
         * Here we break down the uploads folder by months, considering it's often the largest folder in a website,
         * and we need to be able to scan each folder in one request.
         */
        return is_numeric($dir->getBasename()) && $dir->getBasename() > 1970 && $dir->getBasename() < 2100;
    }

    /**
     * @param string $fileExtension
     * @return bool
     */
    private function canExcludeLogFile(string $fileExtension): bool
    {
        if ($fileExtension !== 'log') {
            return false;
        }

        if (!$this->scannerDto->getIsExcludingLogs()) {
            return false;
        }

        return true;
    }

    /**
     * @param string $fileExtension
     * @return bool
     */
    private function canExcludeCacheFile(string $fileExtension): bool
    {
        if ($fileExtension !== 'cache') {
            return false;
        }

        if (!$this->scannerDto->getIsExcludingCaches()) {
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

        if (!$this->scannerDto->getIsExcludingCaches()) {
            return false;
        }

        if (!$this->isPathContainsCache($dir->getRealPath())) {
            return false;
        }

        $this->logger->info(sprintf(
            '%s: Skipped directory "%s". Excluded by smart exclusion rule: Excluding cache folder.',
            esc_html($this->logTitle),
            esc_html($dir->getRealPath())
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
}
