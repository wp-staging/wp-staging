<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use Exception;
use RuntimeException;
use WPStaging\Backup\Dto\Service\DatabaseImporterDto;
use WPStaging\Backup\Dto\Task\Restore\RestoreDatabaseTaskDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Backup\Service\Database\Importer\DatabaseSearchReplacerInterface;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class RestoreDatabaseTask extends RestoreTask
{




    const MAX_RETRIES = 2;





    const MAX_EXECUTION_TIME_ALLOWED = 60;

 
    protected $databaseImporter;

 
    protected $pathIdentifier;

 
    protected $databaseSearchReplacer;

 
    protected $databaseImporterDto;

 
    protected $currentTaskDto;

    public function __construct(DatabaseImporter $databaseImporter, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, PathIdentifier $pathIdentifier, DatabaseSearchReplacerInterface $databaseSearchReplacer)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->databaseImporter       = $databaseImporter;
        $this->databaseImporterDto    = new DatabaseImporterDto();
        $this->pathIdentifier         = $pathIdentifier;
        $this->databaseSearchReplacer = $databaseSearchReplacer;
    }




    public static function getTaskName(): string
    {
        return 'backup_restore_database';
    }




    public static function getTaskTitle(): string
    {
        return 'Restoring Database';
    }




    public function execute(): TaskResponseDto
    {
        if ($this->isBackupPartSkipped(PartIdentifier::DATABASE_PART_IDENTIFIER)) {
            $this->jobDataDto->setIsDatabaseRestoreSkipped(true);
            $this->logger->warning('Database restore skipped due to filter');
            return $this->generateResponse(false);
        }

        $this->jobDataDto->setIsDatabaseRestoreSkipped(false);
        if ($this->jobDataDto->getIsMissingDatabaseFile()) {
            $partIndex = $this->jobDataDto->getDatabasePartIndex();
            $this->jobDataDto->setDatabasePartIndex($partIndex + 1);
            $this->logger->warning(sprintf('Skip restoring rest of database part: %d.', $partIndex));
            return $this->generateResponse(false);
        }

        try {
            $this->prepare();
        } catch (MissingFileException $e) {
            return $this->generateResponse(false);
        }

        $start           = microtime(true);
        $queriesExecuted = $this->stepsDto->getCurrent();
        $totalQueries    = $this->stepsDto->getTotal();

        if ($totalQueries === 0) {
            $this->logger->critical('Total number of queries is 0. Stop restoring backup. Contact support@wp-staging.com.');
            throw new Exception('Total number of queries is 0. Stop restoring backup');
        }

        $this->setupExecutionTime();
        $this->restoreDatabase();
        $this->updateTaskDtos();

        $newQueriesExecuted = $this->stepsDto->getCurrent();

        if ($newQueriesExecuted > $totalQueries) {
            $newQueriesExecuted = $totalQueries;
        }

        $queriesPerSecond = ($newQueriesExecuted - $queriesExecuted) / (microtime(true) - $start);
        $queriesPerSecond = (int)$queriesPerSecond;

        if ($queriesPerSecond > 0) {
            $queriesPerSecond = number_format_i18n($queriesPerSecond);
        }

        $queriesLog = sprintf('Executed %s/%s queries (%s queries per second)', number_format_i18n($newQueriesExecuted), number_format_i18n($totalQueries), $queriesPerSecond);
        $this->logger->info($queriesLog);

        if ($queriesPerSecond === 0) {
            $this->maybeUpdateExecutionTime();
        } else {
            $this->jobDataDto->resetNumberOfRetries();
        }

        if ($this->stepsDto->isFinished() && $this->jobDataDto->getBackupMetadata()->getIsMultipartBackup()) {
            $this->jobDataDto->setDatabasePartIndex($this->jobDataDto->getDatabasePartIndex() + 1);
            $this->stepsDto->setCurrent(0);

 
            $this->stepsDto->setTotal(0);

            $this->resetRestoreCheckpoint();
        }

        return $this->generateResponse(false);
    }





    public function prepare()
    {
        $metadata = $this->jobDataDto->getBackupMetadata();

        $this->databaseImporterDto->setTmpPrefix($this->jobDataDto->getTmpDatabasePrefix());
        $this->databaseImporterDto->setShortTables($this->jobDataDto->getShortNamesTablesToRestore(), $this->jobDataDto->getTmpDatabasePrefix());
        $this->databaseImporterDto->setShortTables($this->jobDataDto->getShortNamesTablesToDrop(), DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP);

        $this->databaseImporter->setup($this->databaseImporterDto, $this->jobDataDto->getIsSameSiteBackupRestore(), $metadata->getSqlServerVersion());
        if ($metadata->getIsMultipartBackup()) {
            $this->setupMultipartDatabaseRestore();
            return;
        }

        $databaseFile = $this->pathIdentifier->transformIdentifiableToPath($metadata->getDatabaseFile());
        $fileSize = filesize($databaseFile);

        if ($fileSize === false || $fileSize === 0) {
            throw new RuntimeException(sprintf('Could not get database file size for %s', $databaseFile));
        }

        if (!file_exists($databaseFile)) {
            throw new RuntimeException(sprintf('Can not find database file %s', $databaseFile));
        }

        $this->setupDatabaseImporterFile($databaseFile);

        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setTotal($this->databaseImporter->getTotalLines());
        }

        $this->databaseImporterDto->setTotalLines($this->databaseImporter->getTotalLines());
        $this->setupSearchReplace();
    }










    protected function setupDatabaseImporterFile(string $databaseFile)
    {
        $this->databaseImporter->setWarningLogCallable([$this->logger, 'warning']);
        $this->databaseImporter->setNoticeLogCallable([$this->logger, 'notice']);
        $this->databaseImporter->setFile($databaseFile, $this->stepsDto->getTotal());
        $this->seekToRestorePosition();
    }












    protected function seekToRestorePosition()
    {
        $currentLine = $this->stepsDto->getCurrent();

        if ($this->hasCheckpointForLine($currentLine) && $this->databaseImporter->seekToOffset($this->jobDataDto->getDatabaseFileOffset(), $currentLine)) {
            return;
        }

 
        $this->databaseImporter->seekLine($currentLine);
    }










    protected function hasCheckpointForLine(int $currentLine): bool
    {
        return $this->jobDataDto->getDatabaseFileOffsetLine() === $currentLine;
    }







    protected function resetRestoreCheckpoint()
    {
        $this->jobDataDto->setDatabaseFileOffset(0);
        $this->jobDataDto->setDatabaseFileOffsetLine(0);
    }

 
    protected function getCurrentTaskType(): string
    {
        return RestoreDatabaseTaskDto::class;
    }




    protected function setupExecutionTime()
    {
        static::$backupRestoreMaxExecutionTimeInSeconds = $this->jobDataDto->getCurrentExecutionTimeDatabaseImport();
    }




    protected function setupSearchReplace()
    {
        $this->databaseImporter->setSearchReplace($this->databaseSearchReplacer->getSearchAndReplace(get_site_url(), get_home_url()));
    }





    protected function maybeUpdateExecutionTime()
    {
        $this->jobDataDto->incrementNumberOfRetries();
        if ($this->jobDataDto->getNumberOfRetries() < self::MAX_RETRIES) {
            return;
        }

        $this->jobDataDto->incrementCurrentExecutionTimeDatabaseImport();
        $this->jobDataDto->resetNumberOfRetries();

        $currentExecutionTimeDatabaseImport = $this->jobDataDto->getCurrentExecutionTimeDatabaseImport();
        if ($currentExecutionTimeDatabaseImport > self::MAX_EXECUTION_TIME_ALLOWED) {
            throw new RuntimeException(sprintf(esc_html__('Cannot increase execution time. Max allowed execution time of %s seconds exceeded.', 'wp-staging'), self::MAX_EXECUTION_TIME_ALLOWED));
        }

        $this->logger->warning(sprintf(esc_html__('Repeat database restore after increasing execution time to %s seconds', 'wp-staging'), $currentExecutionTimeDatabaseImport));
    }




    protected function restoreDatabase()
    {
        $this->databaseImporter->init($this->jobDataDto->getTmpDatabasePrefix());

        $persistedIndex = $this->databaseImporterDto->getCurrentIndex();

        try {
            while (!$this->isDatabaseRestoreThreshold()) {
                try {
                    $this->databaseImporter->execute();
                } catch (\OutOfBoundsException $e) {
 
                    $this->logger->debug($e->getMessage());
                }

 
 
 
 
 
                $currentIndex = $this->databaseImporterDto->getCurrentIndex();
                if ($currentIndex > $persistedIndex) {
                    $persistedIndex = $currentIndex;
                    $this->persistRestoreProgress();
                }
            }
        } catch (Exception $e) {
            if ($e->getCode() === DatabaseImporter::FINISHED_QUEUE_EXCEPTION_CODE) {
                $this->databaseImporter->finish();
            } elseif ($e->getCode() === DatabaseImporter::THRESHOLD_EXCEPTION_CODE) {
 
            } elseif ($e->getCode() === DatabaseImporter::RETRY_EXCEPTION_CODE) {
                $this->databaseImporter->retryQuery();
            } else {
                $this->databaseImporter->updateIndex();
                $this->logger->critical(substr($e->getMessage(), 0, 1000));
            }

            return;
        }

        $this->databaseImporter->updateIndex();
    }















    protected function persistRestoreProgress()
    {
        $this->updateTaskDtos();
        $this->setCurrentTaskDto($this->currentTaskDto);
        $this->persistJobDataDto();
        $this->persistStepsDto();
    }





    protected function updateTaskDtos()
    {
 
 
 
 
        $currentIndex = $this->databaseImporterDto->getCurrentIndex();
        if ($currentIndex > $this->stepsDto->getCurrent()) {
            $this->stepsDto->setCurrent($currentIndex);
            $this->jobDataDto->setDatabaseFileOffset($this->databaseImporterDto->getFileOffset());
            $this->jobDataDto->setDatabaseFileOffsetLine($currentIndex);
        }

        $this->currentTaskDto->fromDatabaseImporterDto($this->databaseImporterDto);

        $this->jobDataDto->setShortNamesTablesToDrop($this->databaseImporterDto->getShortTables(DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP));
        $this->jobDataDto->setShortNamesTablesToRestore($this->databaseImporterDto->getShortTables($this->jobDataDto->getTmpDatabasePrefix()));
    }




    protected function setupMultipartDatabaseRestore()
    {
 
    }
}
