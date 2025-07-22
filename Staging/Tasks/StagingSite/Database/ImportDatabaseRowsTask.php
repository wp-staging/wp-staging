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

    /**
     * After this time, we will increase the execution by 5s for database restore.
     * @var int
     */
    const MAX_RETRIES = 3;

    /**
     * After this time (in seconds), we will stop the database restore.
     * @var int
     */
    const MAX_EXECUTION_TIME_ALLOWED = 60;

    /** @var JobDataDto|StagingOperationDtoInterface|StagingDatabaseDtoInterface|StagingSiteDtoInterface $jobDataDto */
    protected $jobDataDto; // @phpstan-ignore-line

    /** @var DatabaseImporter */
    protected $databaseImporter;

    /** @var DatabaseImporterDto */
    protected $databaseImporterDto;

    /** @var Directory */
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

    /**
     * @return TaskResponseDto
     * @throws Exception
     */
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

    /**
     * @return void
     */
    protected function importDatabase()
    {
        $this->databaseImporter->init($this->jobDataDto->getDatabasePrefix());

        try {
            while (!$this->isThreshold()) {
                try {
                    $this->databaseImporter->execute();
                } catch (\OutOfBoundsException $e) {
                    // Skipping INSERT query due to unexpected format...
                    $this->logger->debug($e->getMessage());
                }
            }
        } catch (Exception $e) {
            if ($e->getCode() === DatabaseImporter::FINISHED_QUEUE_EXCEPTION_CODE) {
                $this->databaseImporter->finish();
            } elseif ($e->getCode() === DatabaseImporter::THRESHOLD_EXCEPTION_CODE) {
                // no-op
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

    /**
     * @return void
     */
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
        $this->databaseImporter->setFile($databaseFile);
        $this->databaseImporter->seekLine($this->stepsDto->getCurrent());

        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setTotal($this->databaseImporter->getTotalLines());
        }

        $this->databaseImporterDto->setTotalLines($this->databaseImporter->getTotalLines());

        $this->databaseImporter->setSearchReplace(new SearchReplace());
    }

    /**
     * @return void
     */
    protected function setupExecutionTime()
    {
        static::$backupRestoreMaxExecutionTimeInSeconds = $this->jobDataDto->getCurrentExecutionTimeDatabaseImport();
    }

    /**
     * @return void
     * @throws RuntimeException
     */
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
