<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Backup\Task;

use WPStaging\Backup\Dto\Service\CompressorDto;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Exceptions\DiskNotWritableException;
use WPStaging\Backup\Service\Compressor;
use WPStaging\Backup\Service\Multipart\MultipartSplitInterface;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\FilesystemScannerTask;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

use function WPStaging\functions\debug_log;

abstract class FileBackupTask extends BackupTask
{
    /** @var Compressor */
    private $compressor;

    /** @var int|CompressorDto If a file couldn't be processed in a single request, this will be populated */
    private $bigFileBeingProcessed;

    /** @var Filesystem */
    protected $filesystem;

    /** @var float */
    protected $start;

    /** @var MultipartSplitInterface */
    protected $multipartSplit;

    /** @var bool */
    protected $isWpContentOutsideAbspath = false;

    public function __construct(Compressor $compressor, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Filesystem $filesystem, MultipartSplitInterface $multipartSplit)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->compressor     = $compressor;
        $this->filesystem     = $filesystem;
        $this->multipartSplit = $multipartSplit;
    }

    public static function getTaskName()
    {
        return 'backup_file_task';
    }

    public static function getTaskTitle()
    {
        return 'Adding Files to Backup';
    }

    public function execute()
    {
        $this->prepareFileBackupTask();
        $this->setupCompressor();
        $this->start = microtime(true);

        /** @var SiteInfo */
        $siteInfo                        = WPStaging::make(SiteInfo::class);
        $this->isWpContentOutsideAbspath = $siteInfo->isWpContentOutsideAbspath();

        while (!$this->isThreshold() && !$this->stepsDto->isFinished()) {
            try {
                $this->backup();
            } catch (FinishedQueueException $exception) {
                $this->stepsDto->finish();

                if ($this->jobDataDto->getIsMultipartBackup() && $this->stepsDto->getTotal() > 0) {
                    $this->multipartSplit->setBackupPartInfo($this->jobDataDto, $this->compressor);
                }

                $this->logger->info(sprintf('Added %d/%d %s files to backup (%s)', $this->stepsDto->getCurrent(), $this->stepsDto->getTotal(), $this->getFileIdentifier(), $this->getBackupSpeed()));

                return $this->generateResponse(false);
            } catch (DiskNotWritableException $exception) {
                // Probably disk full. No-op, as this is handled elsewhere.
            }
        }

        if ($this->bigFileBeingProcessed instanceof CompressorDto) {
            $relativePathForLogging = str_replace($this->filesystem->normalizePath(ABSPATH, true), '', $this->filesystem->normalizePath($this->bigFileBeingProcessed->getFilePath(), true));
            $percentProcessed       = ceil(($this->bigFileBeingProcessed->getWrittenBytesTotal() / $this->bigFileBeingProcessed->getFileSize()) * 100);
            $this->logger->info(sprintf('Adding big %s file: %s - %s/%s (%s%%) (%s)', $this->getFileIdentifier(), $relativePathForLogging, size_format($this->bigFileBeingProcessed->getWrittenBytesTotal(), 2), size_format($this->bigFileBeingProcessed->getFileSize(), 2), $percentProcessed, $this->getBackupSpeed()));
        } else {
            $this->logger->info(sprintf('Added %d/%d %s files to backup (%s)', $this->stepsDto->getCurrent(), $this->stepsDto->getTotal(), $this->getFileIdentifier(), $this->getBackupSpeed()));
        }

        if ($this->stepsDto->isFinished() && $this->jobDataDto->getIsMultipartBackup() && $this->stepsDto->getTotal() > 0) {
            $this->multipartSplit->setBackupPartInfo($this->jobDataDto, $this->compressor);
        }

        return $this->generateResponse(false);
    }

    /** @return string */
    abstract protected function getFileIdentifier();

    protected function setupCompressor()
    {
        if (!$this->jobDataDto->getIsMultipartBackup() || !WPStaging::isPro()) {
            $this->compressor->setCategory('', $create = true);
            return;
        }

        $this->multipartSplit->setupCompressor($this->jobDataDto, $this->compressor, $this->getFileIdentifier(), $this->stepsDto->getTotal() > 0);
    }

    protected function getBackupSpeed()
    {
        $elapsed = microtime(true) - $this->start;
        // Fixes a "division by zero fatal error" when $elapsed was 0. issue #2571
        $elapsed = empty($elapsed) ? 1 : $elapsed;

        $bytesPerSecond = min(10 * GB_IN_BYTES, absint($this->compressor->getBytesWrittenInThisRequest() / $elapsed));

        if ($bytesPerSecond === 10 * GB_IN_BYTES) {
            return '10GB/s+';
        }

        return size_format($bytesPerSecond) . '/s';
    }

    /**
     * @throws DiskNotWritableException
     * @throws FinishedQueueException
     */
    public function backup()
    {
        $compressorDto = $this->compressor->getDto();
        $compressorDto->setWrittenBytesTotal($this->jobDataDto->getFileBeingBackupWrittenBytes());

        if ($compressorDto->getWrittenBytesTotal() !== 0) {
            $compressorDto->setIndexPositionCreated(true);
        }

        $path = $this->taskQueue->dequeue();

        if (is_null($path)) {
            debug_log("Backup error: no task to dequeue");
            throw new FinishedQueueException();
        }

        if (empty($path)) {
            //$this->logger->warning("Path is empty. Cannot add file to backup.");
            return;
        }

        $indexPath = '';
        if (strpos($path, FilesystemScannerTask::PATH_SEPARATOR) !== false) {
            list($path, $indexPath) = explode(FilesystemScannerTask::PATH_SEPARATOR, $path);
        }

        if ($this->isWpContentOutsideAbspath === false) {
            $path = trailingslashit(ABSPATH) . $path;
        }

        try {
            $this->multipartSplit->maybeIncrementBackupFileIndex($this->jobDataDto, $this->compressor, $this->getFileIdentifier(), $path);
            $isFileWrittenCompletely = $this->compressor->appendFileToBackup($path, $indexPath);
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
            $this->jobDataDto->setFileBeingBackupWrittenBytes($compressorDto->getWrittenBytesTotal());
            $this->taskQueue->retry(false);

            if ($compressorDto->getWrittenBytesTotal() < $compressorDto->getFileSize() && $compressorDto->getFileSize() > 10 * MB_IN_BYTES) {
                $this->bigFileBeingProcessed = $compressorDto;
            }
        }
    }

    private function prepareFileBackupTask()
    {
        if ($this->stepsDto->getTotal() > 0) {
            return;
        }

        $this->stepsDto->setTotal($this->jobDataDto->getDiscoveredFilesByCategory($this->getFileIdentifier()));
    }
}
