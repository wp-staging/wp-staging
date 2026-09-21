<?php

namespace WPStaging\Framework\Job\Traits;

use Exception;
use OutOfBoundsException;
use RuntimeException;
use WPStaging\Backup\Service\Database\DatabaseImporter;





trait DatabaseImportTaskTrait
{





    protected function stopWhenDatabaseDumpHasNoQueries($totalQueries)
    {
        if ($totalQueries !== 0) {
            return;
        }

        $this->logger->critical('Total number of queries is 0. Stop restoring backup. Contact support@wp-staging.com.');
        throw new Exception('Total number of queries is 0. Stop restoring backup');
    }






    protected function requireNonEmptyDatabaseFile($databaseFile)
    {
        $fileSize = filesize($databaseFile);

        if ($fileSize === false || $fileSize === 0) {
            throw new RuntimeException(sprintf('Could not get database file size for %s', $databaseFile));
        }

        if (!file_exists($databaseFile)) {
            throw new RuntimeException(sprintf('Can not find database file %s', $databaseFile));
        }
    }




    protected function setupExecutionTime()
    {
        static::$backupRestoreMaxExecutionTimeInSeconds = $this->jobDataDto->getCurrentExecutionTimeDatabaseImport();
    }







    protected function executeNextDatabaseImporterQuery()
    {
        try {
            $this->databaseImporter->execute();
        } catch (OutOfBoundsException $exception) {
            $this->logger->debug($exception->getMessage());
        }
    }





    protected function handleDatabaseImporterStop(Exception $exception)
    {
        $code = $exception->getCode();
        if ($code === DatabaseImporter::FINISHED_QUEUE_EXCEPTION_CODE) {
            $this->databaseImporter->finish();
            return;
        }

        if ($code === DatabaseImporter::THRESHOLD_EXCEPTION_CODE) {
            return;
        }

        if ($code === DatabaseImporter::RETRY_EXCEPTION_CODE) {
            $this->databaseImporter->retryQuery();
            return;
        }

        if ($code !== DatabaseImporter::SHORT_NAME_MISSING_EXCEPTION_CODE) {
            $this->databaseImporter->updateIndex();
        }

        $this->logger->critical(substr($exception->getMessage(), 0, 1000));
    }










    protected function logImportSpeedAndAdjustExecutionTime($queriesExecutedBefore, $totalQueries, float $startedAt)
    {
        $queriesExecuted = $this->stepsDto->getCurrent();
        if ($queriesExecuted > $totalQueries) {
            $queriesExecuted = $totalQueries;
        }

        $queriesPerSecond = (int)(($queriesExecuted - $queriesExecutedBefore) / (microtime(true) - $startedAt));
        if ($queriesPerSecond > 0) {
            $queriesPerSecond = number_format_i18n($queriesPerSecond);
        }

        $this->logger->info(sprintf('Executed %s/%s queries (%s queries per second)', number_format_i18n($queriesExecuted), number_format_i18n($totalQueries), $queriesPerSecond));

        if ($queriesPerSecond === 0) {
            $this->maybeUpdateExecutionTime();
            return;
        }

        $this->jobDataDto->resetNumberOfRetries();
    }





    protected function maybeUpdateExecutionTime()
    {
        $this->jobDataDto->incrementNumberOfRetries();
        if ($this->jobDataDto->getNumberOfRetries() < static::MAX_RETRIES) {
            return;
        }

        $this->jobDataDto->incrementCurrentExecutionTimeDatabaseImport();
        $this->jobDataDto->resetNumberOfRetries();

        $currentExecutionTimeDatabaseImport = $this->jobDataDto->getCurrentExecutionTimeDatabaseImport();
        if ($currentExecutionTimeDatabaseImport > static::MAX_EXECUTION_TIME_ALLOWED) {
            throw new RuntimeException(sprintf(esc_html__('Cannot increase execution time. Max allowed execution time of %s seconds exceeded.', 'wp-staging'), static::MAX_EXECUTION_TIME_ALLOWED));
        }

        $this->logger->warning(sprintf(esc_html__('Repeat database restore after increasing execution time to %s seconds', 'wp-staging'), $currentExecutionTimeDatabaseImport));
    }
}
