<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Dto\Task\Restore\ExtractFilesTaskDto;
use WPStaging\Backup\Exceptions\DiskNotWritableException;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\Extractor;

class ExtractFilesTask extends RestoreTask
{
    /** @var Extractor */
    protected $extractorService;

    /** @var float */
    protected $start;

    /** @var int */
    protected $totalFiles;

    /** @var BackupMetadata */
    protected $metadata;

    /** @var ExtractFilesTaskDto */
    protected $currentTaskDto;

    public function __construct(Extractor $extractor, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->extractorService = $extractor;
    }

    public static function getTaskName()
    {
        return 'backup_restore_extract';
    }

    public static function getTaskTitle()
    {
        return 'Extracting Files';
    }

    public function execute()
    {
        try {
            $this->prepareTask();
        } catch (MissingFileException $ex) {
            $this->jobDataDto->setFilePartIndex($this->jobDataDto->getFilePartIndex() + 1);
            $this->currentTaskDto->totalFilesExtracted = 0;
            $this->currentTaskDto->currentIndexOffset  = 0;
            return $this->generateResponse(false);
        }

        $this->start = microtime(true);

        try {
            $this->extractorService->execute();
            $this->currentTaskDto->fromExtractorDto($this->extractorService->getExtractorDto());
        } catch (DiskNotWritableException $e) {
            $this->logger->warning($e->getMessage());
            $this->currentTaskDto->fromExtractorDto($this->extractorService->getExtractorDto());
            $this->setCurrentTaskDto($this->currentTaskDto);
            // No-op, just stop execution
            throw $e;
        } catch (FinishedQueueException $e) {
            $this->currentTaskDto->fromExtractorDto($this->extractorService->getExtractorDto());
            if ($this->currentTaskDto->totalFilesExtracted !== $this->stepsDto->getTotal()) {
                // Unexpected finish. Log the difference and continue.
                $this->logger->warning(sprintf('Expected to find %d files in Backup, but found %d files instead.', $this->stepsDto->getTotal(), $this->currentTaskDto->totalFilesExtracted));
                // Force the completion to avoid a loop.
                $this->currentTaskDto->totalFilesExtracted = $this->stepsDto->getTotal();
            }
        }

        $this->stepsDto->setCurrent($this->currentTaskDto->totalFilesExtracted);

        $this->logger->info(sprintf('Extracted %d/%d files (%s)', $this->stepsDto->getCurrent(), $this->stepsDto->getTotal(), $this->getExtractSpeed()));

        $this->setCurrentTaskDto($this->currentTaskDto);
        if (!$this->stepsDto->isFinished()) {
            return $this->generateResponse(false);
        }

        if (!$this->metadata->getIsMultipartBackup() && $this->metadata->getIsExportingUploads()) {
            $this->logger->info(__('Restored Media Library', 'wp-staging'));
            return $this->generateResponse(false);
        }

        if (!$this->metadata->getIsMultipartBackup()) {
            return $this->generateResponse(false);
        }

        $this->setNextBackupToExtract();

        return $this->generateResponse(false);
    }

    protected function getExtractSpeed()
    {
        $elapsed = microtime(true) - $this->start;
        $bytesPerSecond = min(10 * GB_IN_BYTES, absint($this->extractorService->getBytesWrittenInThisRequest() / $elapsed));

        if ($bytesPerSecond === 10 * GB_IN_BYTES) {
            return '10GB/s+';
        }

        return size_format($bytesPerSecond) . '/s';
    }

    /**
     * @return void
     */
    protected function prepareTask()
    {
        $this->metadata   = $this->jobDataDto->getBackupMetadata();
        $this->totalFiles = $this->metadata->getTotalFiles();
        $this->extractorService->setIsBackupFormatV1($this->metadata->getIsBackupFormatV1());
        $this->extractorService->setLogger($this->logger);
        $this->setupExtractor();
    }

    /**
     * @return void
     */
    protected function setupExtractor()
    {
        $this->stepsDto->setTotal($this->totalFiles);
        $this->extractorService->setup($this->currentTaskDto->toExtractorDto(), $this->jobDataDto->getFile(), $this->jobDataDto->getTmpDirectory());
    }

    /**
     * @return void
     */
    protected function setNextBackupToExtract()
    {
        // no-op
    }

    /** @return string */
    protected function getCurrentTaskType(): string
    {
        return ExtractFilesTaskDto::class;
    }
}
