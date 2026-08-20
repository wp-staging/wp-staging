<?php

namespace WPStaging\Framework\Filesystem;

use OutOfBoundsException;
use RuntimeException;
use SplFileInfo;
use Throwable;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filters\PathFilterHelper;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\PluginInfo;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class FilesystemScanner extends AbstractFilesystemScanner
{
    use LegacyFileRulesTrait;

 
    protected $filesystemQueue;

 
    protected $taskQueue;

 
    protected $logger;

 
    protected $scannerDto;

 
    protected $logTitle = '';

 
    protected $queueCacheName = '';

 
    protected $ignoreFileBiggerThan = 0;

 
    protected $ignoreFileExtensions = [];

 
    protected $ignoreFileExtensionFilesBiggerThan = [];

 
    protected $isSiteHostedOnWordPressCom = false;

 
    protected $folderNameRules = [];

 
    protected $fileNameRules = [];








    protected $recursiveExcludeFilter = null;








    protected $enqueueEmptyDirectories = false;









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







    public function setFilters(int $ignoreFileBiggerThan, array $ignoreFileExtensions, array $ignoreFileExtensionFilesBiggerThan)
    {
        $this->ignoreFileBiggerThan               = $ignoreFileBiggerThan;
        $this->ignoreFileExtensions               = $ignoreFileExtensions;
        $this->ignoreFileExtensionFilesBiggerThan = $ignoreFileExtensionFilesBiggerThan;
    }







    public function setNameExcludeRules(array $folderNameRules, array $fileNameRules)
    {
        $this->folderNameRules = $folderNameRules;
        $this->fileNameRules   = $fileNameRules;
    }









    public function setRecursiveExcludeRules(array $rules)
    {
        if (empty($rules)) {
            $this->recursiveExcludeFilter = null;
            return;
        }

        $filter = new PathFilterHelper();
        $filter->setWpRootPath($this->rootPath);
        $filter->categorizeRules($rules);
        $this->recursiveExcludeFilter = $filter;
    }





    public function setRootPath(string $rootPath)
    {
        parent::setRootPath($rootPath);
        if ($this->recursiveExcludeFilter !== null) {
            $this->recursiveExcludeFilter->setWpRootPath($rootPath);
        }
    }





    public function setEnqueueEmptyDirectories(bool $enqueueEmptyDirectories)
    {
        $this->enqueueEmptyDirectories = $enqueueEmptyDirectories;
    }




    public function setupFilesystemQueue()
    {
        $fileBackupQueueCacheName = $this->queueCacheName . '_' . $this->currentPathScanning;
        $this->filesystemQueue->setup($fileBackupQueueCacheName, SeekableQueueInterface::MODE_WRITE);
    }





    public function setLogTitle(string $logTitle)
    {
        $this->logTitle = $logTitle;
    }





    public function setQueueCacheName(string $queueCacheName)
    {
        $this->queueCacheName = $queueCacheName;
    }







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




    public function unlockQueue()
    {
        $this->filesystemQueue->shutdown();
    }






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
 
                $this->logger->debug($e->getMessage());
            }

            throw $ex;
        } catch (OutOfBoundsException $e) {
            $this->logger->debug($e->getMessage());
        } catch (Throwable $e) {
            $this->logger->warning($e->getMessage());
        }
    }




    protected function preRecursivePathScanningStep()
    {
        $this->setupFilesystemQueue();
    }







    protected function processFile(SplFileInfo $file, string $linkPath = '')
    {
        $normalizedPath = $this->filesystem->normalizePath($file->getPathname(), !$file->isFile());
        $fileSize       = $file->getSize();
        $fileExtension  = $file->getExtension();

        $relativePath = $this->computeRelativePathFromRoot($normalizedPath);

        if ($this->isExcludeByFileNameRule($file->getFilename())) {
            $this->scannerDto->addFileExcludedInRequest($relativePath);
            return;
        }

 
        $normalizedDebugPath = $this->filesystem->normalizePath($this->contentPath . '/debug.log');
        if ($normalizedPath === $normalizedDebugPath) {
            $this->scannerDto->addFileExcludedInRequest($relativePath);
            $this->logger->notice(sprintf(
                '%s: Skipped file "%s". Excluded by rule.',
                esc_html($this->logTitle),
                esc_html($relativePath)
            ));
            return;
        }

        if ($this->canExcludeLogFile($fileExtension) || $this->canExcludeCacheFile($fileExtension) || isset($this->ignoreFileExtensions[$fileExtension])) {
            $this->scannerDto->addFileExcludedInRequest($relativePath);
            $this->logger->notice(sprintf(
                '%s: Skipped file: "%s". Extension: "%s" is excluded by rule.',
                esc_html($this->logTitle),
                esc_html($relativePath),
                esc_html($fileExtension)
            ));

            return;
        }

        if (isset($this->ignoreFileExtensionFilesBiggerThan[$fileExtension])) {
            if ($fileSize > $this->ignoreFileExtensionFilesBiggerThan[$fileExtension]) {
                $this->scannerDto->addFileExcludedInRequest($relativePath);
                $this->logger->notice(sprintf(
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
            $this->scannerDto->addFileExcludedInRequest($relativePath);
            $this->logger->notice(sprintf(
                '%s: Skipped file "%s" (%s). It exceeds the maximum file size (%s).',
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





    private function computeRelativePathFromRoot(string $normalizedPath): string
    {
        return str_replace($this->filesystem->normalizePath($this->rootPath, true), '', $normalizedPath);
    }






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

        $folderName = $link !== null ? $link->getFilename() : $dir->getFilename();
        if ($this->isExcludeByFolderNameRule($folderName)) {
            return;
        }

        if ($link !== null) {
            $linkPath = $this->filesystem->normalizePath($link->getPathname(), true);
            $this->taskQueue->enqueue($this->currentPathScanning . self::PATH_SEPARATOR . $normalizedPath . self::PATH_SEPARATOR . $linkPath);
            return;
        }

 
        $this->taskQueue->enqueue($this->currentPathScanning . self::PATH_SEPARATOR . $normalizedPath);
    }







    protected function isExcludedByRecursiveRule(string $path): bool
    {
        if ($this->recursiveExcludeFilter === null) {
            return false;
        }

        return $this->recursiveExcludeFilter->isMatched(new SplFileInfo($path));
    }





    protected function isExcludedDirectory(string $path): bool
    {
        $normalizedPath = $this->filesystem->normalizePath($path, true);

        if (in_array($normalizedPath, $this->scannerDto->getExcludedDirectories())) {
            $relativePathForLogging = str_replace($this->filesystem->normalizePath($this->contentPath, true), '', $normalizedPath);

            $this->logger->notice(sprintf(
                '%s: Skipped directory "%s". Excluded by rule',
                esc_html($this->logTitle),
                esc_html($relativePathForLogging)
            ));

            return true;
        }

        return false;
    }





    protected function recursivePathScanning(string $path, string $link = '')
    {
        if ($this->isExcludedDirectory($path) || $this->isExcludedByRecursiveRule($path)) {
            return;
        }

        $this->scannerDto->incrementTotalDirectories();

        if (!$this->enqueueEmptyDirectories) {
            parent::recursivePathScanning($path, $link);
            return;
        }

 
 
        $discoveredFilesBefore = $this->scannerDto->getDiscoveredFiles();

        parent::recursivePathScanning($path, $link);

        if ($this->scannerDto->getDiscoveredFiles() === $discoveredFilesBefore) {
            $this->enqueueEmptyDirectory($path, $link);
        }
    }








    public function maybeEnqueueAsEmptyDirectory(string $path): bool
    {
        if (!$this->enqueueEmptyDirectories || !is_dir($path)) {
            return false;
        }

        $iterator = new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS);
        if ($iterator->valid()) {
            return false;
        }

        $this->scannerDto->incrementTotalDirectories();
        $this->enqueueEmptyDirectory($path);

        return true;
    }









    protected function enqueueEmptyDirectory(string $path, string $link = '')
    {
        $normalizedPath = $this->filesystem->normalizePath($path, true);
        $rootPath       = $this->filesystem->normalizePath($this->rootPath, true);
        $relativePath   = str_replace($rootPath, '', $normalizedPath);
        $relativePath   = $this->replaceEOLsWithPlaceholders($relativePath);

        $this->scannerDto->incrementDiscoveredFiles();
        $this->scannerDto->incrementDiscoveredFilesByCategory($this->currentPathScanning);

        if (!empty($link)) {
            $linkPath  = $this->filesystem->normalizePath($link, true);
            $queueItem = rtrim($relativePath, '/') . self::PATH_SEPARATOR . rtrim($linkPath, '/');
            $this->filesystemQueue->enqueue($queueItem);
            return;
        }

        $this->filesystemQueue->enqueue(rtrim($relativePath, '/'));
    }





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







        return is_numeric($dir->getBasename()) && $dir->getBasename() > 1970 && $dir->getBasename() < 2100;
    }

    protected function isExcludeByFileNameRule(string $fileName): bool
    {
        if (empty($this->fileNameRules)) {
            return false;
        }

        foreach ($this->fileNameRules as $rule) {
            if ($this->ruleMatch($rule, $fileName)) {
                $this->logger->info(sprintf(
                    '%s: Skipped file "%s". Excluded by file name rule: "%s".',
                    esc_html($this->logTitle),
                    esc_html($fileName),
                    esc_html($rule)
                ));

                return true;
            }
        }

        return false;
    }

    protected function isExcludeByFolderNameRule(string $folderName): bool
    {
        if (empty($this->folderNameRules)) {
            return false;
        }

        foreach ($this->folderNameRules as $rule) {
            if ($this->ruleMatch($rule, $folderName)) {
                $this->logger->info(sprintf(
                    '%s: Skipped directory "%s". Excluded by folder name rule: "%s".',
                    esc_html($this->logTitle),
                    esc_html($folderName),
                    esc_html($rule)
                ));

                return true;
            }
        }

        return false;
    }





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

        $this->logger->notice(sprintf(
            '%s: Skipped directory "%s". Excluded by smart exclusion rule: Excluding cache folder.',
            esc_html($this->logTitle),
            esc_html($dir->getRealPath())
        ));

        return true;
    }







    private function isPathContainsCache(string $path): bool
    {
        $pathParts = explode(DIRECTORY_SEPARATOR, $path);

        return in_array('cache', $pathParts);
    }
}
