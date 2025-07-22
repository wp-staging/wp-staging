<?php

namespace WPStaging\Staging\Service;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\FilesystemScanner;
use WPStaging\Framework\Filesystem\Permissions;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Traits\EndOfLinePlaceholderTrait;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Staging\Dto\Service\BigFileDto;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

use function WPStaging\functions\debug_log;

class FileCopier
{
    use ResourceTrait;
    use EndOfLinePlaceholderTrait;

    /**
     * @var int 512KB
     */
    const BATCH_SIZE = 512 * 1024;

    /**
     * @var string
     */
    const FILTER_COPY_BATCH_SIZE = 'wpstg.clone.copy_batch_size';

    /** @var Filesystem */
    protected $filesystem;

    /** @var Permissions */
    protected $permissions;

    /** @var Strings */
    protected $strings;

    /** @var SeekableQueueInterface */
    protected $taskQueue;

    /** @var LoggerInterface */
    protected $logger;

    /** @var StepsDto */
    protected $stepsDto;

    /** @var ?BigFileDto If a file couldn't be processed in a single request, this will be populated */
    protected $bigFileDto = null;

    /** @var bool */
    protected $isWpContentOutsideAbspath = false;

    /** @var string */
    protected $fileIdentifier;

    /** @var int */
    protected $batchSize = 0;

    /** @var string */
    protected $stagingSitePath = '';

    /** @var string */
    protected $absPath = ABSPATH;

    /** @var string */
    protected $wpContentDir = WP_CONTENT_DIR;

    /**
     * @var bool
     */
    protected $isWpContent = false;

    public function __construct(Filesystem $filesystem, Directory $directory, SiteInfo $siteInfo, Permissions $permissions, Strings $strings)
    {
        $this->filesystem  = $filesystem;
        $this->permissions = $permissions;
        $this->strings     = $strings;

        $this->isWpContentOutsideAbspath = $siteInfo->isWpContentOutsideAbspath();
        $this->absPath                   = $this->filesystem->normalizePath($directory->getAbsPath(), true);
        $this->wpContentDir              = $this->filesystem->normalizePath($directory->getWpContentDirectory(), true);
    }

    /**
     * @param SeekableQueueInterface $taskQueue
     * @param LoggerInterface $logger
     * @param StepsDto $stepsDto
     * @return void
     */
    public function inject(SeekableQueueInterface $taskQueue, LoggerInterface $logger, StepsDto $stepsDto)
    {
        $this->taskQueue = $taskQueue;
        $this->logger    = $logger;
        $this->stepsDto  = $stepsDto;
    }

    /**
     * @param BigFileDto $bigFileDto
     * @return void
     */
    public function setupBigFileBeingCopied(BigFileDto $bigFileDto)
    {
        $this->bigFileDto = $bigFileDto;
    }

    /**
     * @return ?BigFileDto
     */
    public function getBigFileDto()
    {
        return $this->bigFileDto;
    }

    /**
     * @param string $stagingSitePath
     * @param string $fileIdentifier
     * @param bool $isWpContent
     * @return void
     */
    public function setup(string $stagingSitePath, string $fileIdentifier, bool $isWpContent = false)
    {
        $this->stagingSitePath = $this->filesystem->normalizePath($stagingSitePath, true);
        $this->fileIdentifier  = $fileIdentifier;
        $this->isWpContent     = $isWpContent;

        // Default batch size is 512KB
        $this->batchSize = Hooks::applyFilters(self::FILTER_COPY_BATCH_SIZE, self::BATCH_SIZE);
    }

    /**
     * @return void
     */
    public function execute()
    {
        while (!$this->isThreshold() && !$this->stepsDto->isFinished()) {
            try {
                $this->copy();
            } catch (FinishedQueueException $exception) {
                $this->stepsDto->finish();
                $this->logger->info(sprintf('Copied %d/%d %s files to staging site', $this->stepsDto->getCurrent(), $this->stepsDto->getTotal(), $this->fileIdentifier));

                return;
            } catch (DiskNotWritableException $exception) {
                // Probably disk full. Should be handled in Job\AbstractJob::prepareAndExecute(). Let's stop the code here if it did not happen!
                throw new \Exception('Disk is probably full. Error message: ' . $exception->getMessage());
            } catch (\Throwable $th) {
                throw new \Exception('Fail to copy file. Error message: ' . $th->getMessage());
            }
        }

        if ($this->bigFileDto instanceof BigFileDto) {
            $relativePathForLogging = str_replace($this->filesystem->normalizePath(ABSPATH, true), '', $this->filesystem->normalizePath($this->bigFileDto->getFilePath(), true));
            $percentProcessed       = ceil(($this->bigFileDto->getWrittenBytesTotal() / $this->bigFileDto->getFileSize()) * 100);
            $this->logger->info(sprintf('Copying big %s file: %s - %s/%s (%s%%)', $this->fileIdentifier, $relativePathForLogging, size_format($this->bigFileDto->getWrittenBytesTotal(), 2), size_format($this->bigFileDto->getFileSize(), 2), $percentProcessed));
        } else {
            $this->logger->info(sprintf('Copied %d/%d %s files to the staging site', $this->stepsDto->getCurrent(), $this->stepsDto->getTotal(), $this->fileIdentifier));
        }
    }

    /**
     * @throws DiskNotWritableException
     * @throws FinishedQueueException
     * @return void
     */
    public function copy()
    {
        $path = $this->taskQueue->dequeue();
        $path = $this->replacePlaceholdersWithEOLs($path);

        if (is_null($path)) {
            debug_log("Copy error: no task to dequeue");
            throw new FinishedQueueException();
        }

        if (empty($path)) {
            return;
        }

        $indexPath = '';
        if (strpos($path, FilesystemScanner::PATH_SEPARATOR) !== false) {
            list($path, $indexPath) = explode(FilesystemScanner::PATH_SEPARATOR, $path);
        }

        // When wp-content is inside of ABSPATH, we need to prepend ABSPATH to the file path, as it was removed while scanning
        if ($this->shouldPrependAbsPath()) {
            $path = trailingslashit(ABSPATH) . $path;
        }

        try {
            $isFileWrittenCompletely = $this->processFile($path, $indexPath);
        } catch (\RuntimeException $e) {
            $this->logger->warning($e->getMessage());
            debug_log($e->getMessage());
            $isFileWrittenCompletely = true;
        } catch (\Throwable $th) {
            throw $th;
        }

        // Done processing this file
        if ($isFileWrittenCompletely === true) {
            $this->stepsDto->incrementCurrentStep();
            $this->bigFileDto = null;

            return;
        }

        // Processing a file that could not be finished in this request
        $this->taskQueue->retry(false);
    }

    protected function shouldPrependAbsPath(): bool
    {
        return $this->isWpContentOutsideAbspath === false;
    }

    protected function processFile(string $filePath, string $indexPath): bool
    {
        // Invalid file
        if (!is_file($filePath)) {
            throw new \RuntimeException("Invalid file. Could not copy file to staging site: $filePath");
        }

        // If file is unreadable, skip it as if succeeded
        if (!$this->filesystem->isReadableFile($filePath)) {
            throw new \RuntimeException("Can't read file {$filePath}");
        }

        $destinationPath = $this->getDestinationPath($filePath, $indexPath);

        // Get file size
        $fileSize = filesize($filePath);

        $result = false;
        // File is over batch size
        if ($fileSize >= $this->batchSize) {
            $result = $this->copyBigFile($filePath, $destinationPath, $this->batchSize);
        } else {
            $result = $this->filesystem->copyFile($filePath, $destinationPath);
        }

        if (!$result) {
            return false;
        }

        // Set file permissions
        @chmod($destinationPath, $this->permissions->getFilesOctal());

        $this->setDirPermissions($destinationPath);

        return true;
    }

    protected function copyBigFile(string $sourcePath, string $destinationPath, int $batchSize): bool
    {
        if ($this->bigFileDto === null) {
            $this->bigFileDto = new BigFileDto();
            $this->bigFileDto->setFilePath($sourcePath);
            $this->bigFileDto->setFileSize(filesize($sourcePath));

            $this->bigFileDto->setWrittenBytesTotal(0);
        }

        if ($this->bigFileDto->isFinished()) {
            return true;
        }

        $srcFile  = fopen($sourcePath, 'rb');
        $destFile = fopen($destinationPath, 'ab');

        if ($srcFile === false || $destFile === false) {
            throw new \RuntimeException('Could not open file for reading or writing');
        }

        fseek($srcFile, $this->bigFileDto->getWrittenBytesTotal());

        do {
            $bytesWritten = fwrite($destFile, fread($srcFile, $batchSize));
            if ($bytesWritten === false) {
                throw new \RuntimeException('Could not write to file');
            }

            $this->bigFileDto->appendWrittenBytes($bytesWritten);
        } while (!$this->isThreshold() && !$this->bigFileDto->isFinished());

        fclose($srcFile);
        fclose($destFile);
        $srcFile  = null;
        $destFile = null;

        return $this->bigFileDto->getWrittenBytesTotal() === $this->bigFileDto->getFileSize();
    }

    /**
     * Gets destination file and checks if the directory exists, if it does not attempts to create it.
     * If creating destination directory fails, it will throw exception.
     * @param string $filePath
     * @param string $indexPath
     * @return string
     */
    protected function getDestinationPath(string $filePath, string $indexPath): string
    {
        if (empty($indexPath)) {
            $stagingPath = $filePath;
        } else {
            $stagingPath = $indexPath;
        }

        $stagingPath = $this->filesystem->normalizePath($stagingPath);
        if ($this->isWpContentOutsideAbspath && $this->isWpContent) {
            $relStagingPath  = $this->strings->replaceStartWith($this->wpContentDir, '', $stagingPath);
            $destinationPath = $this->stagingSitePath . 'wp-content/' . $relStagingPath;
        } else {
            $relStagingPath  = $this->strings->replaceStartWith($this->absPath, '', $stagingPath);
            $destinationPath = $this->stagingSitePath . $relStagingPath;
        }

        $destinationDirectory  = dirname($destinationPath);
        // If directory already exists, return the destination path
        if (is_dir($destinationDirectory)) {
            return $this->filesystem->normalizePath($destinationPath);
        }

        // If directory does not exist, create it
        if ($this->filesystem->mkdir($destinationDirectory)) {
            return $this->filesystem->normalizePath($destinationPath);
        }

        // If directory still does not exist, throw an exception
        if (!is_dir($destinationDirectory)) {
            throw new \RuntimeException("Can not create directory {$destinationDirectory}." . $this->filesystem->getLogs()[0]);
        }

        return $this->filesystem->normalizePath($destinationPath);
    }

    private function setDirPermissions(string $file): bool
    {
        $dir = dirname($file);
        if (is_dir($dir)) {
            return @chmod($dir, $this->permissions->getDirectoryOctal());
        }

        return false;
    }
}
