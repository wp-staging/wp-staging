<?php

 
 
 

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use Exception;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Service\Archiver;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Job\Exception\FileSizeLimitException;
use WPStaging\Framework\Job\Exception\ThresholdException;

class IncludeDatabaseTask extends BackupTask
{
 
    private $archiver;

    public function __construct(Archiver $archiver, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->archiver = $archiver;
    }

    public static function getTaskName()
    {
        return 'backup_include_database';
    }

    public static function getTaskTitle()
    {
        return 'Including Database to Site Backup';
    }

    public function execute()
    {
 
 
        if ($this->stepsDto->getTotal() > 0 && $this->stepsDto->isFinished()) {
            return $this->generateResponse();
        }

        $this->prepareDatabaseTask();

        $databaseFile = $this->jobDataDto->getDatabaseFile();
        if (!$databaseFile || !file_exists($databaseFile)) {
            return $this->generateResponse();
        }

        $this->rewindTempBackupToCheckpointOrRecordBaseline();

        $archiverDto = $this->archiver->getDto();
        $archiverDto->setWrittenBytesTotal($this->jobDataDto->getDatabaseCheckpointWrittenBytes());
        $archiverDto->setFileHeaderSizeInBytes($this->jobDataDto->getCurrentWrittenFileHeaderBytes());
        $archiverDto->setStartOffset($this->jobDataDto->getCurrentFileStartOffset());
        $archiverDto->setFileCrc32Stamp($this->jobDataDto->getCurrentFileCrc32Stamp());

        if ($this->archiver->getDto()->getWrittenBytesTotal() !== 0) {
            $this->archiver->getDto()->setIndexPositionCreated(true);
        }

        $writtenBytesBefore = $archiverDto->getWrittenBytesTotal();
        $included           = false;
        try {
            $this->archiver->setFileAppendTimeLimit($this->jobDataDto->getFileAppendTimeLimit());
            $included = $this->archiver->appendFileToBackup($this->jobDataDto->getDatabaseFile());
        } catch (ThresholdException $e) {
        } catch (FileSizeLimitException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->logger->critical(sprintf(
                'Failed to include database in the backup: %s (%s).',
                $this->archiver->getDto()->getFilePath(),
                $e->getMessage()
            ));

            return $this->generateResponse(false);
        }

        $this->recordCheckpoint($archiverDto->getWrittenBytesTotal());
        $this->stepsDto->setCurrent($archiverDto->getWrittenBytesTotal());
        $this->jobDataDto->setCurrentWrittenFileHeaderBytes(0);
        $this->jobDataDto->setCurrentFileStartOffset($archiverDto->getStartOffset());
        $this->jobDataDto->setCurrentFileCrc32Stamp($archiverDto->getFileCrc32Stamp());
        if ($included) {
            clearstatcache();
            $this->jobDataDto->setDatabaseFileSize(filesize($this->jobDataDto->getDatabaseFile()));
            $this->jobDataDto->setMaxDbPartIndex(1);
            $this->stepsDto->finish();
            (new Filesystem())->delete($this->jobDataDto->getDatabaseFile());
        }

        if ($archiverDto->getFileHeaderSizeInBytes() > 0) {
            $this->jobDataDto->setCurrentWrittenFileHeaderBytes($archiverDto->getFileHeaderSizeInBytes());
        }

        if (!$included && $this->stepsDto->getCurrent() === $writtenBytesBefore) {
            $response = $this->generateResponse(false);
            $response->setEndsRequest(true);
            return $response;
        }

        $this->logger->info(sprintf('Included %s/%s of Database Backup.', size_format($this->stepsDto->getCurrent(), 2), size_format($this->stepsDto->getTotal(), 2)));
        return $this->generateResponse(false);
    }






    private function rewindTempBackupToCheckpointOrRecordBaseline()
    {
        if ($this->jobDataDto->getFileCheckpointKey() === $this->getCheckpointKey()) {
            $discardedBytes = $this->archiver->rewindTempBackupToCheckpoint($this->jobDataDto);
            if ($discardedBytes > 0) {
                $this->logger->info(sprintf('Discarded %s of the database added by a request that did not finish.', size_format($discardedBytes)));
            }

            return;
        }

        $this->recordCheckpoint($this->stepsDto->getCurrent());
        $this->persistJobDataDto();
    }







    private function recordCheckpoint(int $writtenBytes)
    {
        $this->archiver->recordTempBackupCheckpoint($this->jobDataDto, $this->getCheckpointKey());
        $this->jobDataDto->setDatabaseCheckpointWrittenBytes($writtenBytes);
    }

    private function getCheckpointKey(): string
    {
        return self::getTaskName() . '|' . basename($this->archiver->getTempBackup()->getFilePath());
    }

    public function prepareDatabaseTask()
    {
        $filePath = $this->jobDataDto->getDatabaseFile();
        if (!$filePath || !file_exists($filePath)) {
            $this->logger->warning(sprintf('Database Backup file not found: %s.', $filePath));
            $this->stepsDto->finish();
            return;
        }

        if ($this->jobDataDto->getDatabaseOnlyBackup()) {
            $this->archiver->createArchiveFile(true);
        } else {
            $this->archiver->setupTmpBackupFile();
        }

        if ($this->stepsDto->getTotal() > 0) {
            $this->archiver->getDto()->setFileHeaderSizeInBytes($this->jobDataDto->getCurrentWrittenFileHeaderBytes());
            return;
        }

        $this->archiver->getDto()->reset();
        $this->archiver->getDto()->setFilePath($this->jobDataDto->getDatabaseFile());
        $this->stepsDto->setTotal(filesize($this->archiver->getDto()->getFilePath()));
    }
}
