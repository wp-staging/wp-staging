<?php

namespace WPStaging\Staging\Tasks\StagingSite\Database;

use Exception;
use RuntimeException;
use WPStaging\Backup\Dto\Service\DatabaseImporterDto;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Database\SearchReplace;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Staging\Interfaces\StagingDatabaseDtoInterface;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;
use WPStaging\Staging\Interfaces\StagingSiteDtoInterface;
use WPStaging\Staging\Tasks\StagingTask;
use WPStaging\Staging\Traits\WithStagingDatabase;
use WPStaging\Vendor\Psr\Log\LoggerInterface;





class ImportDatabaseRowsTask extends StagingTask
{
    use WithStagingDatabase;





    const MAX_RETRIES = 3;





    const MAX_EXECUTION_TIME_ALLOWED = 60;

 
    protected $jobDataDto; // @phpstan-ignore-line

 
    protected $databaseImporter;

 
    protected $databaseImporterDto;

 
    protected $directory;

    public function __construct(Directory $directory, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, DatabaseImporter $databaseImporter)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->databaseImporter       = $databaseImporter;
        $this->directory              = $directory;
        $this->databaseImporterDto    = new DatabaseImporterDto();
    }

    public static function getTaskName()
    {
        return 'staging_import_rows';
    }

    public static function getTaskTitle()
    {
        return 'Importing Database Records into Staging Site';
    }





    public function execute()
    {
        $this->setup();

        $start           = microtime(true);
        $queriesExecuted = $this->stepsDto->getCurrent();
        $totalQueries    = $this->stepsDto->getTotal();

        if ($totalQueries === 0) {
            $this->logger->critical('Total number of queries is 0. Stop restoring backup. Contact support@wp-staging.com.');
            throw new Exception('Total number of queries is 0. Stop restoring backup');
        }

        $this->setupExecutionTime();
        $this->importDatabase();
        $this->stepsDto->setCurrent($this->databaseImporterDto->getCurrentIndex());

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

        return $this->generateResponse(false);
    }




    protected function importDatabase()
    {
        $this->databaseImporter->init($this->jobDataDto->getDatabasePrefix());

        try {
            while (!$this->isThreshold()) {
                try {
                    $this->databaseImporter->execute();
                } catch (\OutOfBoundsException $e) {
 
                    $this->logger->debug($e->getMessage());
                }
            }
        } catch (Exception $e) {
            if ($e->getCode() === DatabaseImporter::FINISHED_QUEUE_EXCEPTION_CODE) {
                $this->databaseImporter->finish();
            } elseif ($e->getCode() === DatabaseImporter::THRESHOLD_EXCEPTION_CODE) {
 
            } elseif ($e->getCode() === DatabaseImporter::RETRY_EXCEPTION_CODE) {
                $this->databaseImporter->retryQuery();
            } elseif ($e->getCode() === DatabaseImporter::SHORT_NAME_MISSING_EXCEPTION_CODE) {
                $this->logger->critical(substr($e->getMessage(), 0, 1000));
            } else {
                $this->databaseImporter->updateIndex();
                $this->logger->critical(substr($e->getMessage(), 0, 1000));
            }

            return;
        }

        $this->databaseImporter->updateIndex();
    }




    protected function setup()
    {
        $this->initStagingDatabase($this->jobDataDto->getStagingSite());
        $this->databaseImporter->setDatabase($this->stagingDb);

        $this->databaseImporterDto->setTmpPrefix($this->jobDataDto->getDatabasePrefix());

        $this->databaseImporter->setup($this->databaseImporterDto, true, "");
        $databaseFile = $this->directory->getCacheDirectory() . $this->jobDataDto->getId() . '.wpstgdbtmp.sql';
        $fileSize = filesize($databaseFile);

        if ($fileSize === false || $fileSize === 0) {
            throw new RuntimeException(sprintf('Could not get database file size for %s', $databaseFile));
        }

        if (!file_exists($databaseFile)) {
            throw new RuntimeException(sprintf('Can not find database file %s', $databaseFile));
        }

        $this->databaseImporter->setWarningLogCallable([$this->logger, 'warning']);
        $this->databaseImporter->setNoticeLogCallable([$this->logger, 'notice']);
        $this->databaseImporter->setFile($databaseFile);
        $this->databaseImporter->seekLine($this->stepsDto->getCurrent());

        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setTotal($this->databaseImporter->getTotalLines());
        }

        $this->databaseImporterDto->setTotalLines($this->databaseImporter->getTotalLines());

        $this->databaseImporter->setSearchReplace(new SearchReplace());
    }




    protected function setupExecutionTime()
    {
        static::$backupRestoreMaxExecutionTimeInSeconds = $this->jobDataDto->getCurrentExecutionTimeDatabaseImport();
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
}
