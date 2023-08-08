<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Exceptions\DiskNotWritableException;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\Extractor;
use WPStaging\Backup\Service\Multipart\MultipartRestoreInterface;

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

    /** @var MultipartRestoreInterface */
    protected $multipartRestore;

    public function __construct(Extractor $extractor, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, MultipartRestoreInterface $multipartRestore)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->extractorService = $extractor;
        $this->multipartRestore = $multipartRestore;
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
            $this->prepareExtraction();
        } catch (MissingFileException $ex) {
            $this->jobDataDto->setFilePartIndex($this->jobDataDto->getFilePartIndex() + 1);
            $this->jobDataDto->setExtractorFilesExtracted(0);
            $this->jobDataDto->setExtractorMetadataIndexPosition(0);
            return $this->generateResponse(false);
        }

        $this->start = microtime(true);

        try {
            $this->extractorService->extract();
        } catch (DiskNotWritableException $e) {
            $this->logger->warning($e->getMessage());
            // No-op, just stop execution
        } catch (FinishedQueueException $e) {
            if ($this->jobDataDto->getExtractorFilesExtracted() !== $this->stepsDto->getTotal()) {
                // Unexpected finish. Log the difference and continue.
                $this->logger->warning(sprintf('Expected to find %d files in Backup, but found %d files instead.', $this->stepsDto->getTotal(), $this->jobDataDto->getExtractorFilesExtracted()));
                // Force the completion to avoid a loop.
                $this->jobDataDto->setExtractorFilesExtracted($this->stepsDto->getTotal());
            }
        }

        $this->stepsDto->setCurrent($this->jobDataDto->getExtractorFilesExtracted());

        $this->logger->info(sprintf('Extracted %d/%d files (%s)', $this->stepsDto->getCurrent(), $this->stepsDto->getTotal(), $this->getExtractSpeed()));

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

        $this->multipartRestore->setNextExtractedFile($this->jobDataDto, $this->logger);

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

    protected function prepareExtraction()
    {
        $this->metadata = $this->jobDataDto->getBackupMetadata();
        $this->totalFiles = $this->metadata->getTotalFiles();
        if (!$this->metadata->getIsMultipartBackup()) {
            $this->stepsDto->setTotal($this->totalFiles);
            $this->extractorService->inject($this->jobDataDto, $this->logger);
            $this->extractorService->setFileToExtract($this->jobDataDto->getFile());
            return;
        }

        $this->multipartRestore->prepareExtraction($this->jobDataDto, $this->logger, $this->stepsDto, $this->extractorService);
    }
}
