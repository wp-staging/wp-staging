<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use Exception;
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
use WPStaging\Framework\Job\Traits\DatabaseImportTaskTrait;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class RestoreDatabaseTask extends RestoreTask
{
    use DatabaseImportTaskTrait;





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

        $this->stopWhenDatabaseDumpHasNoQueries($totalQueries);
        $this->setupExecutionTime();
        $this->restoreDatabase();
        $this->updateTaskDtos();
        $this->setCurrentTaskDto($this->currentTaskDto);
        $this->logImportSpeedAndAdjustExecutionTime($queriesExecuted, $totalQueries, $start);

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

        $this->databaseImporterDto->setSubsiteId($this->currentTaskDto->subsiteId);
        $this->databaseImporterDto->setTmpPrefix($this->jobDataDto->getTmpDatabasePrefix());
        $this->databaseImporterDto->setShortTables($this->jobDataDto->getShortNamesTablesToRestore(), $this->jobDataDto->getTmpDatabasePrefix());
        $this->databaseImporterDto->setShortTables($this->jobDataDto->getShortNamesTablesToDrop(), DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP);

        $this->databaseImporter->setup($this->databaseImporterDto, $this->jobDataDto->getIsSameSiteBackupRestore(), $metadata->getSqlServerVersion());
        if ($metadata->getIsMultipartBackup()) {
            $this->setupMultipartDatabaseRestore();
            return;
        }

        $databaseFile = $this->pathIdentifier->transformIdentifiableToPath($metadata->getDatabaseFile());
        $this->requireNonEmptyDatabaseFile($databaseFile);
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




    protected function setupSearchReplace()
    {
        $this->databaseImporter->setSearchReplace($this->databaseSearchReplacer->getSearchAndReplace(get_site_url(), get_home_url()));
    }




    protected function restoreDatabase()
    {
        $this->databaseImporter->init($this->jobDataDto->getTmpDatabasePrefix());

        $persistedIndex = $this->databaseImporterDto->getCurrentIndex();

        try {
            while (!$this->isDatabaseRestoreThreshold()) {
                $this->executeNextDatabaseImporterQuery();

 
 
 
 
 
                $currentIndex = $this->databaseImporterDto->getCurrentIndex();
                if ($currentIndex > $persistedIndex) {
                    $persistedIndex = $currentIndex;
                    $this->persistRestoreProgress();
                }
            }
        } catch (Exception $e) {
            $this->handleDatabaseImporterStop($e);
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
