<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Dto\Task\Restore\ExtractFilesTaskDto;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\Extractor;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;

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
            $this->currentTaskDto->totalFilesExtracted       = 0;
            $this->currentTaskDto->currentIndexOffset        = 0;
            $this->currentTaskDto->currentHeaderBytesRemoved = 0;
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
            $this->extractionFinishedLog();
            // Force the completion to avoid a loop in case not all file extracted i.e. due to filter.
            $this->currentTaskDto->totalFilesExtracted = $this->stepsDto->getTotal();
        }

        $this->logger->info(sprintf('Extracted %d/%d files (%s)', $this->currentTaskDto->totalFilesExtracted, $this->stepsDto->getTotal(), $this->getExtractSpeed()));
        $this->stepsDto->setCurrent($this->currentTaskDto->totalFilesExtracted);

        $this->setCurrentTaskDto($this->currentTaskDto);
        if (!$this->stepsDto->isFinished()) {
            return $this->generateResponse(false);
        }

        if (!$this->metadata->getIsMultipartBackup() && $this->metadata->getIsExportingUploads() && !$this->isBackupPartSkipped(PartIdentifier::UPLOAD_PART_IDENTIFIER)) {
            $this->logger->info(__('Restored Media Library', 'wp-staging'));
            return $this->generateResponse(false);
        }

        if ($this->isBackupPartSkipped(PartIdentifier::UPLOAD_PART_IDENTIFIER)) {
            $this->logger->warning(__('Restoring Media Skipped by filter!', 'wp-staging'));
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
        $this->extractorService->setIsBackupFormatV1($this->metadata->getIsBackupFormatV1(false));
        $this->extractorService->setLogger($this->logger);
        $this->extractorService->setExcludedIdentifiers($this->getExcludedIdentifiers());
        $this->extractorService->setIsRepairMultipleHeadersIssue($this->canHaveMultipleHeadersIssue());
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

    /**
     * We have a bug in v6.0.0 - v6.1.2 and v4.0.0 - v4.1.2 where the backup can have multiple file headers for big files.
     * @return bool
     */
    protected function canHaveMultipleHeadersIssue(): bool
    {
        // Early bail: v1 backup doesn't have this issue
        if ($this->metadata->getIsBackupFormatV1()) {
            return false;
        }

        $wpstgVersion = $this->metadata->getWpstgVersion();
        $createdOnPro = $this->metadata->getCreatedOnPro();
        if ($createdOnPro && version_compare($wpstgVersion, '6.0.0', '>=') && version_compare($wpstgVersion, '6.1.2', '<=')) {
            return true;
        }

        if (!$createdOnPro && version_compare($wpstgVersion, '4.0.0', '>=') && version_compare($wpstgVersion, '4.1.2', '<=')) {
            return true;
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function getExcludedIdentifiers(): array
    {
        $excludedParts = Hooks::applyFilters(RestoreTask::FILTER_EXCLUDE_BACKUP_PARTS, []);
        if (empty($excludedParts)) {
            return [];
        }

        /** @var PathIdentifier */
        $pathIdentifier      = WPStaging::make(PathIdentifier::class);
        $excludedIdentifiers = [];
        foreach ($excludedParts as $part) {
            // we need to handle the database part separately, as it's not a part of the PathIdentifier
            if ($part === PartIdentifier::DATABASE_PART_IDENTIFIER) {
                $excludedIdentifiers[] = PartIdentifier::DATABASE_PART_IDENTIFIER;
                continue;
            }

            $excludedIdentifiers[] = $pathIdentifier->getIdentifierByPartName($part);
        }

        $excludedIdentifiers = array_filter($excludedIdentifiers, function ($identifier) {
            return !empty($identifier);
        });

        return $excludedIdentifiers;
    }

    /**
     * @return void
     */
    private function extractionFinishedLog()
    {
        if ($this->currentTaskDto->totalFilesExtracted === $this->stepsDto->getTotal()) {
            return;
        }

        // No-filter Unexpected finish. Log the difference and continue.
        if (empty($this->getExcludedIdentifiers())) {
            $this->logger->warning(sprintf('Expected to find %d files in Backup, but found %d files instead.', $this->stepsDto->getTotal(), $this->currentTaskDto->totalFilesExtracted));
            return;
        }

        // Filter involved
        $this->logger->warning(sprintf('Total %d files in Backup, extracted %d files, skipped %d files', $this->stepsDto->getTotal(), $this->currentTaskDto->totalFilesExtracted, $this->currentTaskDto->totalFilesSkipped));
    }
}
