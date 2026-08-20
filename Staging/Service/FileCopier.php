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




    const BATCH_SIZE = 512 * 1024;




    const FILTER_COPY_BATCH_SIZE = 'wpstg.clone.copy_batch_size';

 
    protected $filesystem;

 
    protected $directory;

 
    protected $siteInfo;

 
    protected $permissions;

 
    protected $strings;

 
    protected $taskQueue;

 
    protected $logger;

 
    protected $stepsDto;

 
    protected $bigFileDto = null;

 
    protected $isWpContentOutsideAbspath = false;

 
    protected $fileIdentifier;

 
    protected $batchSize = 0;

 
    protected $stagingSitePath = '';

 
    protected $absPath = ABSPATH;

 
    protected $wpContentDir = WP_CONTENT_DIR;




    protected $isWpContent = false;

    public function __construct(Filesystem $filesystem, Directory $directory, SiteInfo $siteInfo, Permissions $permissions, Strings $strings)
    {
        $this->filesystem  = $filesystem;
        $this->permissions = $permissions;
        $this->strings     = $strings;
        $this->siteInfo    = $siteInfo;
        $this->directory   = $directory;

        $this->isWpContentOutsideAbspath = $this->siteInfo->isWpContentOutsideAbspath();
        $this->absPath                   = $this->filesystem->normalizePath($this->directory->getAbsPath(), true);
        $this->wpContentDir              = $this->filesystem->normalizePath($this->directory->getWpContentDirectory(), true);
    }







    public function inject(SeekableQueueInterface $taskQueue, LoggerInterface $logger, StepsDto $stepsDto)
    {
        $this->taskQueue = $taskQueue;
        $this->logger    = $logger;
        $this->stepsDto  = $stepsDto;
    }





    public function setupBigFileBeingCopied(BigFileDto $bigFileDto)
    {
        if (empty($bigFileDto->getFilePath()) || $bigFileDto->getFileSize() <= 0) {
            $this->bigFileDto = null;
            return;
        }

        $this->bigFileDto = $bigFileDto;
    }




    public function getBigFileDto()
    {
        return $this->bigFileDto;
    }







    public function setup(string $stagingSitePath, string $fileIdentifier, bool $isWpContent = false)
    {
        $this->stagingSitePath = $this->filesystem->normalizePath($stagingSitePath, true);
        $this->fileIdentifier  = $fileIdentifier;
        $this->isWpContent     = $isWpContent;

 
        $this->batchSize = Hooks::applyFilters(self::FILTER_COPY_BATCH_SIZE, self::BATCH_SIZE);
    }




    public function execute()
    {
        while (!$this->isThreshold() && !$this->stepsDto->isFinished()) {
            try {
                $this->copy();
            } catch (FinishedQueueException $exception) {
                $this->stepsDto->finish();
                $this->logger->info(sprintf('Copied %d/%d %s files', $this->stepsDto->getCurrent(), $this->stepsDto->getTotal(), $this->fileIdentifier));

                return;
            } catch (DiskNotWritableException $exception) {
 
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
            $this->logger->info(sprintf('Copied %d/%d %s files', $this->stepsDto->getCurrent(), $this->stepsDto->getTotal(), $this->fileIdentifier));
        }
    }






    public function copy()
    {
        $path = $this->taskQueue->dequeue();
        $path = $this->replacePlaceholdersWithEOLs($path);

        if (is_null($path)) {
            throw new FinishedQueueException();
        }

        if (empty($path)) {
            return;
        }

        $indexPath = '';
        if (strpos($path, FilesystemScanner::PATH_SEPARATOR) !== false) {
            list($path, $indexPath) = explode(FilesystemScanner::PATH_SEPARATOR, $path);
        }

 
        $path = $this->maybePrependSitePath($path);

        try {
 
 
            if (is_dir($path)) {
                $isFileWrittenCompletely = $this->processEmptyDirectory($path, $indexPath);
            } else {
                $isFileWrittenCompletely = $this->processFile($path, $indexPath);
            }
        } catch (\RuntimeException $e) {
            $this->logger->warning($e->getMessage());
            debug_log($e->getMessage());
            $isFileWrittenCompletely = true;
        } catch (\Throwable $th) {
            throw $th;
        }

 
        if ($isFileWrittenCompletely === true) {
            $this->stepsDto->incrementCurrentStep();
            $this->bigFileDto = null;

            return;
        }

 
        $this->taskQueue->retry(false);
    }

    protected function maybePrependSitePath(string $filePath): string
    {
        return $this->shouldPrependAbsPath() ? $this->absPath . $filePath : $filePath;
    }







    protected function shouldPrependAbsPath(): bool
    {
        return !$this->isWpContentOutsideAbspath || !$this->isWpContent;
    }










    protected function processEmptyDirectory(string $sourcePath, string $indexPath): bool
    {
        $staging = empty($indexPath) ? $sourcePath : $indexPath;
        $staging = $this->filesystem->normalizePath($staging);

        if ($this->isWpContentOutsideAbspath && $this->isWpContent) {
            $relStagingPath  = $this->strings->replaceStartWith($this->wpContentDir, '', $staging);
            $destinationPath = $this->stagingSitePath . 'wp-content/' . $relStagingPath;
        } else {
            $relStagingPath  = $this->strings->replaceStartWith($this->absPath, '', $staging);
            $destinationPath = $this->stagingSitePath . $relStagingPath;
        }

        $destinationPath = $this->filesystem->normalizePath($destinationPath);

        if (!is_dir($destinationPath) && !$this->filesystem->mkdir($destinationPath)) {
            return false;
        }

        $this->chmod($destinationPath, $this->permissions->getDirectoryOctal());
        return true;
    }

    protected function processFile(string $filePath, string $indexPath): bool
    {
 
        if (!is_file($filePath)) {
            throw new \RuntimeException("Invalid file. Could not copy file: $filePath");
        }

 
        if (!$this->filesystem->isReadableFile($filePath)) {
            throw new \RuntimeException("Can't read file {$filePath}");
        }

        $destinationPath = $this->getDestinationPath($filePath, $indexPath);

 
        $fileSize = filesize($filePath);

        $result = false;
 
        if ($fileSize > $this->batchSize) {
            $result = $this->copyBigFile($filePath, $destinationPath, $this->batchSize);
        } else {
            $result = $this->filesystem->copyFile($filePath, $destinationPath);
        }

        if (!$result) {
            return false;
        }

 
        $this->chmod($destinationPath, $this->permissions->getFilePermission($destinationPath));

        $this->setDirPermissions($destinationPath);

        return true;
    }

    protected function copyBigFile(string $sourcePath, string $destinationPath, int $batchSize): bool
    {
        $this->setupBigFileCopy($sourcePath, $destinationPath);

        if ($this->bigFileDto->isFinished()) {
            return true;
        }

        $srcFile  = fopen($sourcePath, 'rb');
        $destFile = fopen($destinationPath, $this->getBigFileWriteMode($destinationPath));

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

    protected function setupBigFileCopy(string $sourcePath, string $destinationPath)
    {
        if ($this->bigFileDto instanceof BigFileDto && $this->isSameBigFileCopy($sourcePath, $destinationPath)) {
            return;
        }

        $this->bigFileDto = new BigFileDto();
        $this->bigFileDto->setFilePath($sourcePath);
        $this->bigFileDto->setDestinationPath($destinationPath);
        $this->bigFileDto->setFileSize(filesize($sourcePath));
        $this->bigFileDto->setWrittenBytesTotal(0);
    }

    protected function isSameBigFileCopy(string $sourcePath, string $destinationPath): bool
    {
        return $this->bigFileDto->getFilePath() === wp_normalize_path($sourcePath)
            && $this->bigFileDto->getDestinationPath() === wp_normalize_path($destinationPath)
            && $this->bigFileDto->getFileSize() === filesize($sourcePath);
    }

    protected function getBigFileWriteMode(string $destinationPath): string
    {
        if ($this->bigFileDto->getWrittenBytesTotal() <= 0) {
            return 'wb';
        }

        if (!is_file($destinationPath) || filesize($destinationPath) !== $this->bigFileDto->getWrittenBytesTotal()) {
            $this->bigFileDto->setWrittenBytesTotal(0);
            return 'wb';
        }

        return 'ab';
    }








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
 
        if (is_dir($destinationDirectory)) {
            return $this->filesystem->normalizePath($destinationPath);
        }

 
        if ($this->filesystem->mkdir($destinationDirectory)) {
            return $this->filesystem->normalizePath($destinationPath);
        }

 
        if (!is_dir($destinationDirectory)) {
            throw new \RuntimeException("Can not create directory {$destinationDirectory}." . $this->filesystem->getLogs()[0]);
        }

        return $this->filesystem->normalizePath($destinationPath);
    }

    private function setDirPermissions(string $file): bool
    {
        $dir = dirname($file);
        if (is_dir($dir)) {
            return $this->chmod($dir, $this->permissions->getDirectoryOctal());
        }

        return false;
    }






    protected function chmod(string $path, int $mode): bool
    {
        $lastWarning = null;
        set_error_handler(function ($severity, $message, $file, $line) use (&$lastWarning) {
            if (($severity & (E_WARNING | E_NOTICE)) === 0 || $file !== __FILE__ || strpos($message, 'chmod') !== 0) {
                return false;
            }

            $lastWarning = $message;
            return true;
        });

        try {
            $result = \chmod($path, $mode);
        } finally {
            restore_error_handler();
        }

        if ($lastWarning !== null) {
            debug_log(sprintf('chmod warning: %s', $lastWarning));
        }

        return $result !== false;
    }
}
