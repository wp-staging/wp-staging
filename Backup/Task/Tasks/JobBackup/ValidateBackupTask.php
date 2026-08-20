<?php








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



    const TRANSIENT_GRACEFUL_SHUTDOWN = 'wpstg_backup_validation_task';

 
    protected $backupExtractor;

 
    protected $backupValidator;

 
    protected $directory;

 
    protected $currentTaskDto;

 
    protected $metadata;

 
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
 
        }

        set_transient(self::TRANSIENT_GRACEFUL_SHUTDOWN, '1', 60);
 
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
 
            throw $e;
        } catch (FinishedQueueException $e) {
            $this->currentTaskDto->fromExtractorDto($this->backupExtractor->getExtractorDto());
            $totalFilesProcessed = $this->currentTaskDto->totalFilesExtracted + $this->currentTaskDto->totalFilesSkipped;
            if ($totalFilesProcessed !== $this->stepsDto->getTotal()) {
 
                $this->logger->error(sprintf(
                    'Expected to validate %d files in Backup, but processed %d instead (extracted: %d, skipped: %d).',
                    $this->stepsDto->getTotal(),
                    $totalFilesProcessed,
                    $this->currentTaskDto->totalFilesExtracted,
                    $this->currentTaskDto->totalFilesSkipped
                ));
                $this->setCurrentTaskDto($this->currentTaskDto);
                return $this->generateResponse(false);
            }

            if ($this->currentTaskDto->totalFilesSkipped > 0) {
                $this->logger->info(sprintf(
                    'Backup validation complete: %d files extracted, %d files skipped.',
                    $this->currentTaskDto->totalFilesExtracted,
                    $this->currentTaskDto->totalFilesSkipped
                ));
            }
        }

        $totalFilesProcessed = $this->currentTaskDto->totalFilesExtracted + $this->currentTaskDto->totalFilesSkipped;
        $this->stepsDto->setCurrent($totalFilesProcessed);

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




    public function persistDto(ExtractorDto $extractorDto)
    {
        $this->currentTaskDto->fromExtractorDto($extractorDto);
        $this->setCurrentTaskDto($this->currentTaskDto);
        $this->persistJobDataDto();
    }




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




    protected function prepareCurrentBackupFileValidation()
    {
        $this->currentBackupFile = $this->jobDataDto->getBackupFilePath();
        $this->stepsDto->setTotal($this->jobDataDto->getTotalFiles());
        $this->backupExtractor->setup($this->currentTaskDto->toExtractorDto(), $this->currentBackupFile, '');
    }




    protected function setNextBackupToValidate()
    {
 
    }

 
    protected function getCurrentTaskType(): string
    {
        return ExtractFilesTaskDto::class;
    }





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
 
        if (empty($transient)) {
            return;
        }

        $this->logger->debug('Resuming validation after a non-graceful shutdown.');
        $this->backupExtractor->setIsLastRequestGracefulShutdown(false);
    }
}
