<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use RuntimeException;
use WPStaging\Backup\BackupValidator;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Dto\Task\Restore\ExtractFilesTaskDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Backup\Service\Extractor;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class ValidateBackupTask extends BackupTask
{
    /** @var Extractor */
    protected $backupExtractor;

    /** @var BackupValidator */
    protected $backupValidator;

    /** @var ExtractFilesTaskDto */
    protected $currentTaskDto;

    /** @var BackupMetadata */
    protected $metadata;

    /** @var string */
    protected $currentBackupFile;

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Extractor $backupExtractor, BackupValidator $backupValidator)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

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

        try {
            $this->backupExtractor->execute();
            $this->currentTaskDto->fromExtractorDto($this->backupExtractor->getExtractorDto());
        } catch (DiskNotWritableException $e) {
            $this->logger->warning($e->getMessage());
            // No-op, just stop execution
            throw $e;
        } catch (FinishedQueueException $e) {
            $this->currentTaskDto->fromExtractorDto($this->backupExtractor->getExtractorDto());
            if ($this->currentTaskDto->totalFilesExtracted !== $this->stepsDto->getTotal()) {
                // Unexpected finish.
                $this->logger->error(sprintf('Expected to find %d files in Backup, but found %d files instead.', $this->stepsDto->getTotal(), $this->currentTaskDto->totalFilesExtracted));
                return $this->generateResponse(false);
            }
        }

        $this->stepsDto->setCurrent($this->currentTaskDto->totalFilesExtracted);

        $this->logger->info(sprintf('Validated %d/%d files...', $this->stepsDto->getCurrent(), $this->stepsDto->getTotal()));

        if (!$this->stepsDto->isFinished()) {
            return $this->generateResponse(false);
        }

        if ($this->jobDataDto->getIsBackupFormatV1()) {
            $this->validateOldBackup();
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
    protected function prepareTask()
    {
        $this->backupExtractor->setIsBackupFormatV1($this->jobDataDto->getIsBackupFormatV1());
        $this->backupExtractor->setLogger($this->logger);
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
        $this->stepsDto->setTotal($this->metadata->getTotalFiles());
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
}
