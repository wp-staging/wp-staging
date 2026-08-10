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
    /**
     * After this time, we will increase the execution by 5s for database restore.
     * @var int
     */
    const MAX_RETRIES = 2;

    /**
     * After this time (in seconds), we will stop the database restore.
     * @var int
     */
    const MAX_EXECUTION_TIME_ALLOWED = 60;

    /** @var DatabaseImporter */
    protected $databaseImporter;

    /** @var PathIdentifier */
    protected $pathIdentifier;

    /** @var DatabaseSearchReplacerInterface */
    protected $databaseSearchReplacer;

    /** @var DatabaseImporterDto */
    protected $databaseImporterDto;

    /** @var RestoreDatabaseTaskDto */
    protected $currentTaskDto;

    public function __construct(DatabaseImporter $databaseImporter, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, PathIdentifier $pathIdentifier, DatabaseSearchReplacerInterface $databaseSearchReplacer)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->databaseImporter       = $databaseImporter;
        $this->databaseImporterDto    = new DatabaseImporterDto();
        $this->pathIdentifier         = $pathIdentifier;
        $this->databaseSearchReplacer = $databaseSearchReplacer;
    }

    /**
     * @return string
     */
    public static function getTaskName(): string
    {
        return 'backup_restore_database';
    }

    /**
     * @return string
     */
    public static function getTaskTitle(): string
    {
        return 'Restoring Database';
    }

    /**
     * @return TaskResponseDto
     */
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

            // To make sure finish condition work.
            $this->stepsDto->setTotal(0);

            $this->resetRestoreCheckpoint();
        }

        return $this->generateResponse(false);
    }

    /**
     * @see \WPStaging\Backup\Service\Database\Exporter\RowsExporter::setupBackupSearchReplace For Backup Search/Replace.
     * @return void
     */
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

    /**
     * Point the importer at a database file and resume where the previous request stopped.
     *
     * Shared with the multipart restore, which reaches its own file through a different route
     * but must resume the same way.
     *
     * @param string $databaseFile
     * @return void
     */
    protected function setupDatabaseImporterFile(string $databaseFile)
    {
        $this->databaseImporter->setWarningLogCallable([$this->logger, 'warning']);
        $this->databaseImporter->setNoticeLogCallable([$this->logger, 'notice']);
        $this->databaseImporter->setFile($databaseFile, $this->stepsDto->getTotal());
        $this->seekToRestorePosition();
    }

    /**
     * Resume where the previous request stopped.
     *
     * seekLine() counts newlines from the start of the file, so resuming at line N re-reads
     * everything before it. On a large dump that grows until it exceeds the whole request
     * budget: the request then executes no queries, reports 0 queries per second, and
     * maybeUpdateExecutionTime() ratchets the limit up until it throws. Resuming at the byte
     * offset recorded last time is O(1) and avoids that entirely.
     *
     * @return void
     */
    protected function seekToRestorePosition()
    {
        $currentLine = $this->stepsDto->getCurrent();

        if ($this->hasCheckpointForLine($currentLine) && $this->databaseImporter->seekToOffset($this->jobDataDto->getDatabaseFileOffset(), $currentLine)) {
            return;
        }

        // No offset recorded yet, or it no longer lands on a statement boundary.
        $this->databaseImporter->seekLine($currentLine);
    }

    /**
     * The line is persisted to the steps cache and the offset to the job cache, in two
     * separate writes. If the second one never lands, a new line is left paired with an old
     * offset, which would replay or skip statements, so the offset carries the line it was
     * taken at and is only trusted when the two still agree.
     *
     * @param int $currentLine
     * @return bool
     */
    protected function hasCheckpointForLine(int $currentLine): bool
    {
        return $this->jobDataDto->getDatabaseFileOffsetLine() === $currentLine;
    }

    /**
     * Each database part is seeked independently, so an offset from the part that just
     * finished must not survive into the next one.
     *
     * @return void
     */
    protected function resetRestoreCheckpoint()
    {
        $this->jobDataDto->setDatabaseFileOffset(0);
        $this->jobDataDto->setDatabaseFileOffsetLine(0);
    }

    /** @return string */
    protected function getCurrentTaskType(): string
    {
        return RestoreDatabaseTaskDto::class;
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
     */
    protected function setupSearchReplace()
    {
        $this->databaseImporter->setSearchReplace($this->databaseSearchReplacer->getSearchAndReplace(get_site_url(), get_home_url()));
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

    /**
     * @return void
     */
    protected function restoreDatabase()
    {
        $this->databaseImporter->init($this->jobDataDto->getTmpDatabasePrefix());

        $persistedIndex = $this->databaseImporterDto->getCurrentIndex();

        try {
            while (!$this->isDatabaseRestoreThreshold()) {
                try {
                    $this->databaseImporter->execute();
                } catch (\OutOfBoundsException $e) {
                    // Skipping INSERT query due to unexpected format...
                    $this->logger->debug($e->getMessage());
                }

                // The importer only moves this on once a batch has landed in the database,
                // so writing it out on every move ties the resume point to what is committed.
                // Waiting instead — for the shutdown hook, or for a time window to pass —
                // leaves committed rows past the resume point, and the retry replays them
                // into a duplicate key.
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
     * Write the resume point out together with everything it depends on.
     *
     * The seek position is only usable with the short table-name mappings that were created
     * on the way there: a name over 64 characters is shortened once, and the INSERTs and the
     * final rename that follow resolve the table through that mapping. Persisting the
     * position alone would let a hard kill keep the position and lose the mapping, leaving
     * the next request to resume past the CREATE with no name to insert into.
     *
     * Job data goes first, so a kill between the two writes costs a replay rather than a
     * mapping.
     *
     * @return void
     */
    protected function persistRestoreProgress()
    {
        $this->updateTaskDtos();
        $this->setCurrentTaskDto($this->currentTaskDto);
        $this->persistJobDataDto();
        $this->persistStepsDto();
    }

    /**
     * Responsible for updating steps dto and current task dto from database importer dto.
     * @return void
     */
    protected function updateTaskDtos()
    {
        // Never backwards. A fresh importer dto starts at zero and stays there when its first
        // flush fails, and writing that back would send the next request to the top of the
        // file to replay every row this restore has already committed. The byte offset
        // describes that same position, so it moves with the line or not at all.
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

    /**
     * @return void
     */
    protected function setupMultipartDatabaseRestore()
    {
        // no-op
    }
}
