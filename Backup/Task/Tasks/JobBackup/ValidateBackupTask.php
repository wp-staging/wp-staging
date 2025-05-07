<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use Exception;
use RuntimeException;
use WPStaging\Backup\BackupGlitchReason;
use WPStaging\Backup\BackupValidator;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Dto\Task\Restore\ExtractFilesTaskDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupMetadataEditor;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Backup\Service\Extractor;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

use function WPStaging\functions\debug_log;

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
            $this->logger->warning($e->getMessage());
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
        $this->recalibrateTotalFilesCount();
        $this->stepsDto->setTotal($this->jobDataDto->getTotalFiles());
        $this->backupExtractor->setup($this->currentTaskDto->toExtractorDto(), $this->currentBackupFile, '');
    }

    /**
     * This is not called in multipart backups.
     * @throws Exception
     * @return void
     */
    protected function recalibrateTotalFilesCount()
    {
        $totalFilesInMetadata = $this->metadata->getTotalFiles();
        $totalFilesInBackup   = 0;
        $lastFileInFilesIndex = $this->metadata->getHeaderEnd();

        $backupFile = new FileObject($this->currentBackupFile, FileObject::MODE_APPEND_AND_READ);

        $backupFile->fseek($this->metadata->getHeaderStart());
        while ($backupFile->valid() && $backupFile->ftell() < $lastFileInFilesIndex) {
            $line = $backupFile->readAndMoveNext();
            if (empty($line) || in_array($line, BackupValidator::LINE_BREAKS)) {
                continue;
            }

            $totalFilesInBackup++;
        }

        if ($totalFilesInMetadata === $totalFilesInBackup) {
            return;
        } else {
            debug_log("Mismatch in total files. Metadata: $totalFilesInMetadata, Backup: $totalFilesInBackup");
        }

        $filesCountInParts = $this->jobDataDto->getMaxDbPartIndex();
        $filesInParts      = $this->jobDataDto->getFilesInParts();
        foreach ($filesInParts as $counts) {
            foreach ($counts as $count) {
                $filesCountInParts += $count;
            }
        }

        if ($filesCountInParts === $totalFilesInBackup) {
            debug_log("Recalibrated total files count from parts. Total files in backup: $totalFilesInBackup, Total files in metadata: $totalFilesInMetadata");
            $this->adjustTotalFilesCount($totalFilesInBackup, $backupFile);
            return;
        } else {
            debug_log("Files count in parts does not match total files in backup. Files in parts: $filesCountInParts, Backup: $totalFilesInBackup");
        }

        $totalFilesDiscovered = $this->jobDataDto->getDiscoveredFiles();

        // Let add database files into it as well
        $totalFilesDiscovered += $this->jobDataDto->getMaxDbPartIndex();

        // Let remove invalid files from discovered files
        $totalFilesDiscovered -= $this->jobDataDto->getInvalidFiles();

        if ($totalFilesDiscovered === $totalFilesInBackup) {
            debug_log("Recalibrated total files count from discovered files. Total files in backup: $totalFilesInBackup, Total files in metadata: $totalFilesInMetadata");
            $this->adjustTotalFilesCount($totalFilesInBackup, $backupFile);
            return;
        } else {
            debug_log("Discovered files count does not match total files in backup. Discovered files: $totalFilesDiscovered, Backup: $totalFilesInBackup");
        }

        // Close file descriptor
        $backupFile = null;

        throw new Exception(sprintf("Found %d files in metadata, but found %d files in backup file.", $totalFilesInMetadata, $totalFilesInBackup));
    }

    /**
     * @param int $totalFiles
     * @param FileObject $backupFile
     * @return void
     */
    protected function adjustTotalFilesCount(int $totalFiles, FileObject $backupFile)
    {
        $this->jobDataDto->setTotalFiles($totalFiles);

        // Lazy loading, as for most cases it might not be needed at all.
        /** @var BackupMetadataEditor $backupMetadataEditor */
        $backupMetadataEditor = WPStaging::make(BackupMetadataEditor::class);
        $metadata = new BackupMetadata();
        $metadata = $metadata->hydrateByFile($backupFile);

        $metadata->setTotalFiles($totalFiles);
        $metadata->revertBackupSizeToDefault(); // Important for BackupSigner to recalculate the backup size
        $backupMetadataEditor->setBackupMetadata($backupFile, $metadata);
        $backupFile = null;
        $this->jobDataDto->setIsGlitchInBackup(true);
        $this->jobDataDto->setGlitchReason(BackupGlitchReason::WRONG_TOTAL_FILES_COUNT);
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
