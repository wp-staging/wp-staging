<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use Exception;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Service\Compressor;
use WPStaging\Framework\Filesystem\Filesystem;

class IncludeDatabaseTask extends BackupTask
{
    /** @var Compressor */
    private $compressor;

    public function __construct(Compressor $compressor, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->compressor = $compressor;
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

        $this->compressor->getDto()->setWrittenBytesTotal($this->stepsDto->getCurrent());

        if ($this->compressor->getDto()->getWrittenBytesTotal() !== 0) {
            $this->compressor->getDto()->setIndexPositionCreated(true);
        }

        try {
            $this->compressor->appendFileToBackup($this->jobDataDto->getDatabaseFile());
        } catch (Exception $e) {
            $this->logger->critical(sprintf(
                'Failed to include database backup to backup: %s (%s)',
                $this->compressor->getDto()->getFilePath(),
                $e->getMessage()
            ));
        }

        $this->stepsDto->setCurrent($this->compressor->getDto()->getWrittenBytesTotal());

        if ($this->compressor->getDto()->isFinished()) {
            clearstatcache();
            $this->jobDataDto->setDatabaseFileSize(filesize($this->jobDataDto->getDatabaseFile()));

            $this->stepsDto->finish();
            (new Filesystem())->delete($this->jobDataDto->getDatabaseFile());
        }

        $this->logger->info(sprintf('Included %s/%s of Database Backup.', size_format($this->stepsDto->getCurrent()), size_format($this->stepsDto->getTotal())));
        return $this->generateResponse(false);
    }

    public function prepareDatabaseTask()
    {
        $filePath = $this->jobDataDto->getDatabaseFile();
        if (!$filePath || !file_exists($filePath)) {
            $this->logger->warning(sprintf('Database Backup file not found: %s', $filePath));
            $this->stepsDto->finish();
        }

        if ($this->stepsDto->getTotal() > 0) {
            return;
        }

        $this->compressor->getDto()->reset();
        $this->compressor->getDto()->setFilePath($this->jobDataDto->getDatabaseFile());
        $this->stepsDto->setTotal(filesize($this->compressor->getDto()->getFilePath()));
    }
}
