<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use Exception;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Service\Archiver;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Job\Exception\ThresholdException;

class IncludeDatabaseTask extends BackupTask
{
    /** @var Archiver */
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
        $this->prepareDatabaseTask();

        if (!$this->jobDataDto->getDatabaseFile() && !$this->stepsDto->isFinished()) {
            return $this->generateResponse();
        }

        $archiverDto = $this->archiver->getDto();
        $archiverDto->setWrittenBytesTotal($this->stepsDto->getCurrent());
        $archiverDto->setFileHeaderSizeInBytes($this->jobDataDto->getCurrentWrittenFileHeaderBytes());
        $archiverDto->setStartOffset($this->jobDataDto->getCurrentFileStartOffset());

        if ($this->archiver->getDto()->getWrittenBytesTotal() !== 0) {
            $this->archiver->getDto()->setIndexPositionCreated(true);
        }

        $included = false;
        try {
            $this->archiver->setFileAppendTimeLimit($this->jobDataDto->getFileAppendTimeLimit());
            $included = $this->archiver->appendFileToBackup($this->jobDataDto->getDatabaseFile());
        } catch (ThresholdException $e) {
            $this->logger->warning(sprintf(
                'PHP time limit reached while adding database to the backup. Will try again with increasing the time limit. New time limit %s.',
                $this->jobDataDto->getFileAppendTimeLimit()
            ));
        } catch (Exception $e) {
            $this->logger->critical(sprintf(
                'Failed to include database in the backup: %s (%s).',
                $this->archiver->getDto()->getFilePath(),
                $e->getMessage()
            ));
        }

        $archiverDto = $this->archiver->getDto();
        $this->stepsDto->setCurrent($archiverDto->getWrittenBytesTotal());
        $this->jobDataDto->setCurrentWrittenFileHeaderBytes(0);
        $this->jobDataDto->setCurrentFileStartOffset($archiverDto->getStartOffset());
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

        $this->logger->info(sprintf('Included %s/%s of Database Backup.', size_format($this->stepsDto->getCurrent(), 2), size_format($this->stepsDto->getTotal(), 2)));
        return $this->generateResponse(false);
    }

    public function prepareDatabaseTask()
    {
        $filePath = $this->jobDataDto->getDatabaseFile();
        if (!$filePath || !file_exists($filePath)) {
            $this->logger->warning(sprintf('Database Backup file not found: %s.', $filePath));
            $this->stepsDto->finish();
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
