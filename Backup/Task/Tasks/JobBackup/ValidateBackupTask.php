<?php

/**
 * Validates the integrity and completeness of created backup archives
 *
 * Performs validation checks on backup files by extracting and verifying file entries,
 * ensuring the backup is complete and can be successfully restored.
 */

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use RuntimeException;
use WPStaging\Backup\BackupValidator;
use WPStaging\Backup\Dto\File\ExtractorDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Dto\Task\Restore\ExtractFilesTaskDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Interfaces\ExtractorTaskInterface;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Backup\Service\Extractor;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class ValidateBackupTask extends BackupTask implements ExtractorTaskInterface
{
    /**
     * Whether if the file backup task gracefully shuts down
     */
    const TRANSIENT_GRACEFUL_SHUTDOWN = 'wpstg_backup_validation_task';

    /** @var Extractor */
    protected $backupExtractor;

    /** @var BackupValidator */
    protected $backupValidator;

    /** @var Directory */
    protected $directory;

    /** @var ExtractFilesTaskDto */
    protected $currentTaskDto;

    /** @var BackupMetadata */
    protected $metadata;

    /** @var string */
    protected $currentBackupFile;

    public function __construct(LoggerInterface $logger, Directory $directory, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Extractor $backupExtractor, BackupValidator $backupValidator)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->directory       = $directory;
        $this->backupExtractor = $backupExtractor;
        $this->backupValidator = $backupValidator;
    }

    public static function getTaskName(): string
    {
        return 'backup_validate';
    }

    public static function getTaskTitle(): string
    {
        return 'Validating Backup';
    }

    public function execute(): TaskResponseDto
    {
        try {
            $this->prepareTask();
        } catch (MissingFileException $ex) {
            // throw error
        }

        set_transient(self::TRANSIENT_GRACEFUL_SHUTDOWN, '1', 60);
        // If the backup is in old format, we don't need to validate it.
        if ($this->jobDataDto->getIsBackupFormatV1()) {
            $this->validateOldBackup();
            $this->stepsDto->finish();
            return $this->generateResponse(false);
        }

        try {
            $this->backupExtractor->execute();
            $this->currentTaskDto->fromExtractorDto($this->backupExtractor->getExtractorDto());
        } catch (DiskNotWritableException $e) {
            $this->logger->warning($e->getMessage() . '.');
            // No-op, just stop execution
            throw $e;
        } catch (FinishedQueueException $e) {
            $this->currentTaskDto->fromExtractorDto($this->backupExtractor->getExtractorDto());
            if ($this->currentTaskDto->totalFilesExtracted !== $this->stepsDto->getTotal()) {
                // Unexpected finish.
                $this->logger->error(sprintf('Expected to find %d files in Backup, but found %d files instead.', $this->stepsDto->getTotal(), $this->currentTaskDto->totalFilesExtracted));
                $this->setCurrentTaskDto($this->currentTaskDto);
                return $this->generateResponse(false);
            }
        }

        $this->stepsDto->setCurrent($this->currentTaskDto->totalFilesExtracted);

        $this->logger->info(sprintf('Validated %d/%d files...', $this->stepsDto->getCurrent(), $this->stepsDto->getTotal()));

        $this->setCurrentTaskDto($this->currentTaskDto);

        delete_transient(self::TRANSIENT_GRACEFUL_SHUTDOWN);
        if (!$this->stepsDto->isFinished()) {
            return $this->generateResponse(false);
        }

        if (!$this->metadata->getIsMultipartBackup()) {
            return $this->generateResponse(false);
        }

        $this->setNextBackupToValidate();

        return $this->generateResponse(false);
    }

    /**
     * @return void
     */
    public function persistDto(ExtractorDto $extractorDto)
    {
        $this->currentTaskDto->fromExtractorDto($extractorDto);
        $this->setCurrentTaskDto($this->currentTaskDto);
        $this->persistJobDataDto();
    }

    /**
     * @return void
     */
    protected function prepareTask()
    {
        if ($this->stepsDto->getTotal() > 0) {
            $this->checkIfLastRequestGracefulShutdown();
        }

        $this->backupExtractor->setIsFastPerformanceMode($this->jobDataDto->getIsFastPerformanceMode());
        $this->backupExtractor->setIsBackupFormatV1($this->jobDataDto->getIsBackupFormatV1());
        $this->backupExtractor->inject($this, $this->logger);
        $this->backupExtractor->setIsValidateOnly(true);

        $this->metadata = new BackupMetadata();
        $this->metadata = $this->metadata->hydrateByFilePath($this->jobDataDto->getBackupFilePath());

        $this->prepareCurrentBackupFileValidation();
    }

    /**
     * @return void
     */
    protected function prepareCurrentBackupFileValidation()
    {
        $this->currentBackupFile = $this->jobDataDto->getBackupFilePath();
        $this->stepsDto->setTotal($this->jobDataDto->getTotalFiles());
        $this->backupExtractor->setup($this->currentTaskDto->toExtractorDto(), $this->currentBackupFile, '');
    }

    /**
     * @return void
     */
    protected function setNextBackupToValidate()
    {
        // no-op
    }

    /** @return string */
    protected function getCurrentTaskType(): string
    {
        return ExtractFilesTaskDto::class;
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function validateOldBackup()
    {
        $file     = new FileObject($this->currentBackupFile, FileObject::MODE_APPEND_AND_READ);
        $metadata = new BackupMetadata();
        $metadata = $metadata->hydrateByFile($file);

        clearstatcache();

        if ($this->backupValidator->validateFileIndex($file, $metadata)) {
            return;
        }

        throw new RuntimeException($this->backupValidator->getErrorMessage());
    }

    protected function checkIfLastRequestGracefulShutdown()
    {
        $transient = get_transient(self::TRANSIENT_GRACEFUL_SHUTDOWN);
        // empty that mean it was graceful shutdown
        if (empty($transient)) {
            return;
        }

        $this->logger->debug('Resuming validation after a non-graceful shutdown.');
        $this->backupExtractor->setIsLastRequestGracefulShutdown(false);
    }
}
