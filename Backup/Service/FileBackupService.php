<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Dto\Service\ArchiverDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Exceptions\BackupSkipItemException;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Backup\Task\Tasks\JobBackup\FilesystemScannerTask;
use WPStaging\Framework\Filesystem\Filesystem;
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

    /** @var bool */
    protected $isWpContentOutsideAbspath = false;

    /** @var float */
    protected $start;

    /** @var string */
    protected $fileIdentifier;

    public function __construct(Archiver $archiver, Filesystem $filesystem, SiteInfo $siteInfo)
    {
        $this->archiver   = $archiver;
        $this->filesystem = $filesystem;

        $this->isWpContentOutsideAbspath = $siteInfo->isWpContentOutsideAbspath();
    }

    /**
     * @param SeekableQueueInterface $taskQueue
     * @param LoggerInterface $logger
     * @param JobBackupDataDto $jobDataDto
     * @param StepsDto $stepsDto
     * @return void
     */
    public function inject(SeekableQueueInterface $taskQueue, LoggerInterface $logger, JobBackupDataDto $jobDataDto, StepsDto $stepsDto)
    {
        $this->taskQueue  = $taskQueue;
        $this->logger     = $logger;
        $this->jobDataDto = $jobDataDto;
        $this->stepsDto   = $stepsDto;
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
        $this->start = microtime(true);

        while (!$this->isThreshold() && !$this->stepsDto->isFinished()) {
            try {
                $this->backup();
            } catch (FinishedQueueException $exception) {
                $this->stepsDto->finish();
                $this->logger->info(sprintf('Added %d/%d %s files to backup (%s)', $this->stepsDto->getCurrent(), $this->stepsDto->getTotal(), $this->fileIdentifier, $this->getBackupSpeed()));
                $this->updateMultipartInfo();

                return;
            } catch (DiskNotWritableException $exception) {
                // Probably disk full. No-op, as this is handled elsewhere.
            }
        }

        if ($this->bigFileBeingProcessed instanceof ArchiverDto) {
            $relativePathForLogging = str_replace($this->filesystem->normalizePath(ABSPATH, true), '', $this->filesystem->normalizePath($this->bigFileBeingProcessed->getFilePath(), true));
            $percentProcessed       = ceil(($this->bigFileBeingProcessed->getWrittenBytesTotal() / $this->bigFileBeingProcessed->getFileSize()) * 100);
            $this->logger->info(sprintf('Adding big %s file: %s - %s/%s (%s%%) (%s)', $this->fileIdentifier, $relativePathForLogging, size_format($this->bigFileBeingProcessed->getWrittenBytesTotal(), 2), size_format($this->bigFileBeingProcessed->getFileSize(), 2), $percentProcessed, $this->getBackupSpeed()));
        } else {
            $this->logger->info(sprintf('Added %d/%d %s files to backup (%s)', $this->stepsDto->getCurrent(), $this->stepsDto->getTotal(), $this->fileIdentifier, $this->getBackupSpeed()));
        }

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

        if ($archiverDto->getWrittenBytesTotal() !== 0) {
            $archiverDto->setIndexPositionCreated(true);
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
        if (strpos($path, FilesystemScannerTask::PATH_SEPARATOR) !== false) {
            list($path, $indexPath) = explode(FilesystemScannerTask::PATH_SEPARATOR, $path);
        }

        if ($this->shouldPrependAbsPath()) {
            $path = trailingslashit(ABSPATH) . $path;
        }

        try {
            $this->maybeIncrementPartNo($path);
            $isFileWrittenCompletely = $this->archiver->appendFileToBackup($path, $indexPath);
        } catch (BackupSkipItemException $e) {
            $isFileWrittenCompletely = null;
        } catch (\RuntimeException $e) {
            debug_log("Backup error: cannot append file to backup: $path");
            $isFileWrittenCompletely = null;
        }

        // Done processing this file
        if ($isFileWrittenCompletely === true) {
            $this->jobDataDto->setFileBeingBackupWrittenBytes(0);
            $this->stepsDto->incrementCurrentStep();

            return;
        } elseif ($isFileWrittenCompletely === null) {
            // Invalid file
            $this->logger->warning("Invalid file. Could not add file to backup: $path");
            $this->jobDataDto->setFileBeingBackupWrittenBytes(0);
            $this->stepsDto->incrementCurrentStep();
            debug_log("Backup error: cannot append file to backup: $path");

            return;
        } elseif ($isFileWrittenCompletely === false) {
            // Processing a file that could not be finished in this request
            $this->jobDataDto->setFileBeingBackupWrittenBytes($archiverDto->getWrittenBytesTotal());
            $this->taskQueue->retry(false);

            if ($archiverDto->getWrittenBytesTotal() < $archiverDto->getFileSize() && $archiverDto->getFileSize() > 10 * MB_IN_BYTES) {
                $this->bigFileBeingProcessed = $archiverDto;
            }
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

        return size_format($bytesPerSecond) . '/s';
    }

    protected function shouldPrependAbsPath(): bool
    {
        return $this->isWpContentOutsideAbspath === false;
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
}
