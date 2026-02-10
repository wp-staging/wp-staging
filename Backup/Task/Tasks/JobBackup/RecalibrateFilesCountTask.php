<?php

/**
 * Recalibrates the total file count in backup metadata to match actual files archived
 *
 * Validates and corrects file count discrepancies between metadata and backup contents,
 * ensuring accurate progress tracking and backup integrity.
 */

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use Exception;
use WPStaging\Backup\BackupGlitchReason;
use WPStaging\Backup\BackupValidator;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupMetadataEditor;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

/**
 * This is not needed in multipart backups
 */
class RecalibrateFilesCountTask extends BackupTask
{
    /** @var Directory */
    protected $directory;

    /** @var BackupMetadata */
    protected $metadata;

    /** @var string */
    protected $currentBackupFile;

    public function __construct(LoggerInterface $logger, Directory $directory, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->directory = $directory;
    }

    public static function getTaskName(): string
    {
        return 'backup_recalibrate_files_count';
    }

    public static function getTaskTitle(): string
    {
        return 'Validating file count';
    }

    public function execute(): TaskResponseDto
    {
        try {
            $this->prepareTask();
        } catch (MissingFileException $ex) {
            // throw error
        }

        $this->recalibrateTotalFilesCount();

        return $this->generateResponse();
    }

    /**
     * @return void
     */
    protected function prepareTask()
    {
        $this->metadata = new BackupMetadata();
        $this->metadata = $this->metadata->hydrateByFilePath($this->jobDataDto->getBackupFilePath());
        $this->currentBackupFile = $this->jobDataDto->getBackupFilePath();
        $this->stepsDto->setTotal(1);
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
            $this->logger->debug("Mismatch in total files. Metadata: $totalFilesInMetadata, Backup: $totalFilesInBackup");
        }

        $filesCountInParts = $this->jobDataDto->getMaxDbPartIndex();
        $filesInParts      = $this->jobDataDto->getFilesInParts();
        foreach ($filesInParts as $counts) {
            foreach ($counts as $count) {
                $filesCountInParts += $count;
            }
        }

        if ($filesCountInParts === $totalFilesInBackup) {
            $this->logger->debug("Recalibrated total files count from parts. Total files in backup: $totalFilesInBackup, Total files in metadata: $totalFilesInMetadata");
            $this->adjustTotalFilesCount($totalFilesInBackup, $backupFile);
            return;
        } else {
            $this->logger->debug("Files count in parts does not match total files in backup. Files in parts: $filesCountInParts, Backup: $totalFilesInBackup");
        }

        $totalFilesDiscovered = $this->jobDataDto->getDiscoveredFiles();

        // Let add database files into it as well
        $totalFilesDiscovered += $this->jobDataDto->getMaxDbPartIndex();

        // Let remove invalid files from discovered files
        $totalFilesDiscovered -= $this->jobDataDto->getInvalidFiles();

        if ($totalFilesDiscovered === $totalFilesInBackup) {
            $this->logger->debug("Recalibrated total files count from discovered files. Total files in backup: $totalFilesInBackup, Total files in metadata: $totalFilesInMetadata");
            $this->adjustTotalFilesCount($totalFilesInBackup, $backupFile);
            return;
        } else {
            $this->logger->debug("Discovered files count does not match total files in backup. Discovered files: $totalFilesDiscovered, Backup: $totalFilesInBackup");
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
}
