<?php

/**
 * Manages the file backup process for adding files to backup archives
 *
 * Coordinates file archiving operations including reading files from disk,
 * handling large files across multiple requests, and managing backup queues.
 */

namespace WPStaging\Backup\Service;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Dto\Service\ArchiverDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Exceptions\BackupSkipItemException;
use WPStaging\Backup\Task\FileBackupTask;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\FilesystemScanner;
use WPStaging\Framework\Job\Exception\ThresholdException;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Traits\EndOfLinePlaceholderTrait;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

use function WPStaging\functions\debug_log;

class FileBackupService implements ServiceInterface
{
    use ResourceTrait;
    use EndOfLinePlaceholderTrait;

    /** @var Archiver */
    protected $archiver;

    /** @var Directory */
    protected $directory;

    /** @var Filesystem */
    protected $filesystem;

    /** @var SeekableQueueInterface */
    protected $taskQueue;

    /** @var LoggerInterface */
    protected $logger;

    /** @var JobBackupDataDto */
    protected $jobDataDto;

    /** @var StepsDto */
    protected $stepsDto;

    /** @var int|ArchiverDto If a file couldn't be processed in a single request, this will be populated */
    protected $bigFileBeingProcessed;

    /** @var FileBackupTask */
    protected $fileBackupTask;

    /** @var bool */
    protected $isWpContentOutsideAbspath = false;

    /** @var bool */
    protected $isGracefulShutdown = true;

    /** @var float */
    protected $start;

    /** @var string */
    protected $fileIdentifier;

    public function __construct(Archiver $archiver, Directory $directory, Filesystem $filesystem, SiteInfo $siteInfo)
    {
        $this->archiver   = $archiver;
        $this->directory  = $directory;
        $this->filesystem = $filesystem;

        $this->isWpContentOutsideAbspath = $siteInfo->isWpContentOutsideAbspath();
    }

    /**
     * @param FileBackupTask $fileBackupTask
     * @param SeekableQueueInterface $taskQueue
     * @param LoggerInterface $logger
     * @param JobBackupDataDto $jobDataDto
     * @param StepsDto $stepsDto
     * @return void
     */
    public function inject(FileBackupTask $fileBackupTask, SeekableQueueInterface $taskQueue, LoggerInterface $logger, JobBackupDataDto $jobDataDto, StepsDto $stepsDto)
    {
        $this->fileBackupTask = $fileBackupTask;
        $this->taskQueue      = $taskQueue;
        $this->logger         = $logger;
        $this->jobDataDto     = $jobDataDto;
        $this->stepsDto       = $stepsDto;
    }

    /**
     * @param bool $isGracefulShutdown
     * @return void
     */
    public function setIsGracefulShutdown(bool $isGracefulShutdown)
    {
        $this->isGracefulShutdown = $isGracefulShutdown;
    }

    /**
     * @param string $fileIdentifier
     * @param bool $isOtherWpRootFilesTask (Used in Pro)
     * @return void
     */
    public function setupArchiver(string $fileIdentifier, bool $isOtherWpRootFilesTask = false)
    {
        $this->fileIdentifier = $fileIdentifier;
        $this->archiver->createArchiveFile(Archiver::CREATE_BINARY_HEADER);
    }

    /**
     * @return void
     */
    public function execute()
    {
        $this->archiver->setFileAppendTimeLimit($this->jobDataDto->getFileAppendTimeLimit());
        $this->start = microtime(true);

        while (!$this->isThreshold() && !$this->stepsDto->isFinished()) {
            try {
                $this->backup();
            } catch (ThresholdException $exception) {
                break;
            } catch (FinishedQueueException $exception) {
                $this->stepsDto->finish();
                $this->logger->info(sprintf('Added %d/%d %s files to backup (%s)', $this->stepsDto->getCurrent(), $this->stepsDto->getTotal(), $this->getTranslatedFileIdentifier(), $this->getBackupSpeed()));
                $this->updateMultipartInfo();

                return;
            } catch (DiskNotWritableException $exception) {
                // Probably disk full. Should be handled in Job\AbstractJob::prepareAndExecute(). Let's stop the code here if it did not happen!
                throw new \Exception('Disk is probably full. Error message: ' . $exception->getMessage());
            } catch (\Throwable $th) {
                throw new \Exception('Fail to create backup. Error message: ' . $th->getMessage());
            }
        }

        $this->logExecution();

        $this->updateMultipartInfo();
    }

    /**
     * @throws DiskNotWritableException
     * @throws FinishedQueueException
     * @return void
     */
    public function backup()
    {
        $archiverDto = $this->archiver->getDto();
        $archiverDto->setWrittenBytesTotal($this->jobDataDto->getFileBeingBackupWrittenBytes());
        $archiverDto->setFileHeaderSizeInBytes($this->jobDataDto->getCurrentWrittenFileHeaderBytes());
        $archiverDto->setStartOffset($this->jobDataDto->getCurrentFileStartOffset());

        if ($archiverDto->getWrittenBytesTotal() !== 0) {
            $archiverDto->setIndexPositionCreated(true);
            $this->logger->debug('Resuming backup of a large file from previous request.');
        }

        $path = $this->taskQueue->dequeue();
        $path = $this->replacePlaceholdersWithEOLs($path);

        if (is_null($path)) {
            debug_log("Backup error: no task to dequeue");
            throw new FinishedQueueException();
        }

        if (empty($path)) {
            return;
        }

        $indexPath = '';
        if (strpos($path, FilesystemScanner::PATH_SEPARATOR) !== false) {
            list($path, $indexPath) = explode(FilesystemScanner::PATH_SEPARATOR, $path);
        }

        if ($this->shouldPrependAbsPath()) {
            $path = trailingslashit(ABSPATH) . $path;
        }

        try {
            $this->maybeIncrementPartNo($path);
            $isFileWrittenCompletely = $this->archiver->appendFileToBackup($path, $indexPath);
        } catch (BackupSkipItemException $e) {
            $isFileWrittenCompletely = true;
        } catch (ThresholdException $e) {
            $isFileWrittenCompletely = null;
        } catch (\RuntimeException $e) {
            $isFileWrittenCompletely = true;
            // Invalid file
            $this->logger->warning("Invalid file. Could not add file to backup: $path");
            debug_log("Backup error: cannot append file to backup: $path");
        } catch (\Throwable $th) {
            throw $th;
        }

        $this->jobDataDto->setCurrentWrittenFileHeaderBytes(0);
        // Done processing this file
        if ($isFileWrittenCompletely === true) {
            $this->jobDataDto->setFileBeingBackupWrittenBytes(0);
            $this->stepsDto->incrementCurrentStep();
            $this->jobDataDto->setQueueOffset($this->taskQueue->getOffset());

            if (!$this->jobDataDto->getIsMultipartBackup()) {
                $this->jobDataDto->incrementFilesInPart($this->fileIdentifier);
            }

            $this->persistJobDataDto();
            return;
        }

        // Processing a file that could not be finished in this request
        $archiverDto = $this->archiver->getDto();
        $this->jobDataDto->setFileBeingBackupWrittenBytes($archiverDto->getWrittenBytesTotal());
        $this->jobDataDto->setCurrentFileStartOffset($archiverDto->getStartOffset());
        $this->taskQueue->retry(false);

        if ($archiverDto->getFileHeaderSizeInBytes() > 0) {
            $this->jobDataDto->setCurrentWrittenFileHeaderBytes($archiverDto->getFileHeaderSizeInBytes());
        }

        if ($archiverDto->getWrittenBytesTotal() < $archiverDto->getFileSize() && $archiverDto->getFileSize() > 10 * MB_IN_BYTES) {
            $this->bigFileBeingProcessed = $archiverDto;
        }

        $this->persistJobDataDto();
        if ($isFileWrittenCompletely === null) {
            throw new ThresholdException();
        }
    }

    protected function getBackupSpeed(): string
    {
        $elapsed = microtime(true) - $this->start;
        // Fixes a "division by zero fatal error" when $elapsed was 0. issue #2571
        $elapsed = empty($elapsed) ? 1 : $elapsed;

        $bytesPerSecond = min(10 * GB_IN_BYTES, absint($this->archiver->getBytesWrittenInThisRequest() / $elapsed));

        if ($bytesPerSecond === 10 * GB_IN_BYTES) {
            return '10GB/s+';
        }

        // Format with 2 decimal places if faster than 1MB/s
        if ($bytesPerSecond >= MB_IN_BYTES) {
            if ($bytesPerSecond >= GB_IN_BYTES) {
                return number_format($bytesPerSecond / GB_IN_BYTES, 2) . 'GB/s';
            }

            return number_format($bytesPerSecond / MB_IN_BYTES, 2) . 'MB/s';
        }

        return size_format($bytesPerSecond) . '/s';
    }

    protected function shouldPrependAbsPath(): bool
    {
        return $this->isWpContentOutsideAbspath === false;
    }

    /**
     * This method logs how many files processed in the current request.
     * @return void
     */
    protected function logExecution()
    {
        if ($this->bigFileBeingProcessed instanceof ArchiverDto) {
            $relativePathForLogging = str_replace($this->filesystem->normalizePath(ABSPATH, true), '', $this->filesystem->normalizePath($this->bigFileBeingProcessed->getFilePath()));
            $percentProcessed       = ceil(($this->bigFileBeingProcessed->getWrittenBytesTotal() / $this->bigFileBeingProcessed->getFileSize()) * 100);
            $this->logger->info(sprintf(
                'Adding big %s file: %s - %s/%s (%s%%) (%s)',
                $this->getTranslatedFileIdentifier(),
                $relativePathForLogging,
                size_format($this->bigFileBeingProcessed->getWrittenBytesTotal(), 2),
                size_format($this->bigFileBeingProcessed->getFileSize(), 2),
                $percentProcessed,
                $this->getBackupSpeed()
            ));
            return;
        }

        $this->logger->info(sprintf(
            'Added %d/%d %s files to backup (%s)',
            $this->stepsDto->getCurrent(),
            $this->stepsDto->getTotal(),
            $this->getTranslatedFileIdentifier(),
            $this->getBackupSpeed()
        ));
    }

    /**
     * @return void
     */
    protected function updateMultipartInfo()
    {
        // Used in Pro
    }

    /**
     * @return void
     */
    protected function maybeIncrementPartNo(string $path)
    {
        // Used in Pro
    }

    protected function getTranslatedFileIdentifier(): string
    {
        switch ($this->fileIdentifier) {
            case 'muplugins':
                return __('mu-plugin', 'wp-staging');
            case 'plugins':
                return __('plugin', 'wp-staging');
            case 'themes':
                return __('theme', 'wp-staging');
            case 'otherfiles':
                return __('other', 'wp-staging');
            case 'rootfiles':
                return __('root', 'wp-staging');
            default:
                return $this->fileIdentifier; // fallback
        }
    }

    protected function persistJobDataDto()
    {
        if ($this->jobDataDto->getIsFastPerformanceMode()) {
            return;
        }

        $this->fileBackupTask->persistJobDataDto();
    }
}
