<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use Exception;
use RuntimeException;
use WPStaging\Framework\Database\Exporter\AbstractExporter;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Service\Database\Exporter\DDLExporter;
use WPStaging\Backup\Exceptions\TableLockGivenUpException;
use WPStaging\Backup\Service\Database\Exporter\RowsExporter;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Backup\Transfer\TransferSessionTable;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Backup\Service\Database\Exporter\DDLExporterProvider;
use WPStaging\Backup\Service\Database\Exporter\RowsExporterProvider;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Utils\Times;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use wpdb;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Filesystem\PartIdentifier;

class DatabaseBackupTask extends BackupTask
{
 
    const TABLES_EXCLUDED_FROM_BACKUP = [
        'wpstg_queue',
        'wpstg_settings',
        TransferSessionTable::TABLE_NAME,
        'wpr_rucss_used_css',
    ];

 
    const FILTER_EXCLUDE_TABLES_DATA = 'wpstg.backup.database.exclude_tables_data';

 
    const TABLE_LOCK_ATTEMPTS = 5;

 
    const TABLE_LOCK_RETRY_SECONDS = 30;

 
    protected $directory;

    public function __construct(Directory $directory, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->directory = $directory;
    }

    public static function getTaskName(): string
    {
        return 'backup_database';
    }

    public static function getTaskTitle(): string
    {
        return 'Backup Database';
    }





    public function execute()
    {
        $this->setupDatabaseFilePathName();

        $tablesToExclude = self::TABLES_EXCLUDED_FROM_BACKUP;

 
        if (is_multisite() && $this->jobDataDto->getIsNetworkSiteBackup() && is_main_site($this->jobDataDto->getSubsiteBlogId())) {
            $tablesToExclude[] = 'blogmeta';
            $tablesToExclude[] = 'blogs';
            $tablesToExclude[] = 'blog_versions';
            $tablesToExclude[] = 'registration_log';
            $tablesToExclude[] = 'signups';
            $tablesToExclude[] = 'site';
            $tablesToExclude[] = 'sitemeta';
        }

        $subsites = [];
        if (is_multisite() && !$this->jobDataDto->getIsNetworkSiteBackup()) {
            $subsites = $this->jobDataDto->getSitesToBackup();
        }

 
        if (!$this->stepsDto->getTotal()) {
 
            $ddlExporter = WPStaging::make(DDLExporterProvider::class)->getExporter();
            $ddlExporter->setIsNetworkSiteBackup($this->jobDataDto->getIsNetworkSiteBackup());
            $ddlExporter->setSubsiteBlogId($this->jobDataDto->getSubsiteBlogId());
            $ddlExporter->setFileName($this->jobDataDto->getDatabaseFile());
            $ddlExporter->setSubsites($subsites);
            $ddlExporter->setTablesToExclude($tablesToExclude);
            $ddlExporter->backupDDLTablesAndViews();
            $allTables = array_merge($ddlExporter->getTables(), $ddlExporter->getNonWPTables());
            $this->jobDataDto->setTablesToBackup($allTables);
            $this->jobDataDto->setNonWpTables($ddlExporter->getNonWPTables());
            $this->jobDataDto->setLastInsertId(-PHP_INT_MAX);
            $this->jobDataDto->setDatabaseTablesInfo([]);

            $this->stepsDto->setTotal(count($this->jobDataDto->getTablesToBackup()));

 
            return $this->generateResponse(false);
        }

 
        if (empty($this->jobDataDto->getTablesToBackup())) {
            $this->logger->critical('Could not create the tables DDL.');
            throw new Exception('Could not create the tables DDL.');
        }

 
        Hooks::doAction('wpstg.tests.backup.export_database.before_rows_export');

        $useMemoryExhaustFix = $this->isMemoryExhaustFixEnabled();
 
 
        $rowsExporter = WPStaging::make(RowsExporterProvider::class)->getExporter();
        $rowsExporter->setSubsites($subsites);
        $rowsExporter->setIsNetworkSiteBackup($this->jobDataDto->getIsNetworkSiteBackup());
        $rowsExporter->setSubsiteBlogId($this->jobDataDto->getSubsiteBlogId());
        $rowsExporter->prefixSpecialFields();
        $rowsExporter->setupPrefixedValuesForSubsites();
        $rowsExporter->setFileName($this->jobDataDto->getDatabaseFile());
        $rowsExporter->setTables($this->jobDataDto->getTablesToBackup());
        $rowsExporter->setIsBackupSplit($this->jobDataDto->getIsMultipartBackup());
        $rowsExporter->setMaxSplitSize($this->jobDataDto->getMaxMultipartBackupSize());
        $rowsExporter->setTablesToExclude(array_merge($tablesToExclude, $this->getTablesExcludedFromRowsExport()));
        $rowsExporter->setNonWpTables($this->jobDataDto->getNonWpTables());
        $rowsExporter->setUseMemoryExhaustFix($useMemoryExhaustFix);

        $this->discardRowsWrittenAfterLastCheckpoint($rowsExporter);

        do {
            $rowsExporter->setTableIndex($this->stepsDto->getCurrent());

            if ($rowsExporter->isTableExcluded()) {
                $this->logger->info(sprintf(
                    'Backup database: Skipped Table %s by exclusion rule.',
                    $rowsExporter->getTableBeingBackup()
                ));

                $this->jobDataDto->setTotalRowsBackup(0);
                $this->jobDataDto->setTableRowsOffset(0);
                $this->jobDataDto->setTableAverageRowLength(0);
                $this->stepsDto->incrementCurrentStep();






                $this->persistStepsDto();
                $this->persistJobDataDto();
                continue;
            }

            $rowsExporter->setTableRowsOffset($this->jobDataDto->getTableRowsOffset());
            $rowsExporter->setTotalRowsExported($this->jobDataDto->getTotalRowsBackup());

            $hasNumericIncrementalPk = false;
            $tableLocked             = false;

            try {
                $rowsExporter->getPrimaryKey();
                $hasNumericIncrementalPk = true;
            } catch (Exception $e) {
                $retryLater = $this->lockTableOrRetryLater($rowsExporter);
                if ($retryLater !== null) {
                    return $retryLater;
                }

                $tableLocked = true;
            }

            try {
 
                if ($this->jobDataDto->getTableRowsOffset() === 0) {
                    $this->jobDataDto->setTotalRowsOfTableBeingBackup($rowsExporter->countTotalRows());
                    $this->upsertDatabaseTableInfo(
                        $rowsExporter,
                        $rowsExporter->getTableBeingBackup(),
                        $this->jobDataDto->getTotalRowsOfTableBeingBackup()
                    );

                    if ($hasNumericIncrementalPk) {




                        $rowsExporter->setTableRowsOffset(-PHP_INT_MAX);
                    }
                }

                $rowsExporter->setTotalRowsInCurrentTable($this->jobDataDto->getTotalRowsOfTableBeingBackup());

                $rowsLeftToBackup = $rowsExporter->backup($this->jobDataDto->getId(), $this->logger);
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage());
                throw $e;
            } finally {
                if ($tableLocked) {
                    $this->unlockTable($rowsExporter);
                }
            }

 
 
            $writtenBytes = $this->measureCheckpoint($rowsExporter);

            $this->stepsDto->setCurrent($rowsExporter->getTableIndex());
            if (!$useMemoryExhaustFix) {
                $this->jobDataDto->setTotalRowsBackup($rowsExporter->getTotalRowsExported());
                $this->jobDataDto->setTableRowsOffset($rowsExporter->getTableRowsOffset());
            }

            $this->commitCheckpoint($writtenBytes);

            $this->logger->info(sprintf(
                'Backup database: Table %s. Rows: %s/%s.',
                $rowsExporter->getTableBeingBackup(),
                number_format_i18n($rowsExporter->getTotalRowsExported()),
                number_format_i18n($this->jobDataDto->getTotalRowsOfTableBeingBackup())
            ));

            $this->logger->debug(sprintf(
                'Backup database: Table %s. Query time: %s. Batch Size: %s. Last query json: %s',
                $rowsExporter->getTableBeingBackup(),
                Times::formatQueryTime($this->jobDataDto->getDbRequestTime()),
                $this->jobDataDto->getBatchSize(),
                $this->jobDataDto->getLastQueryInfoJSON()
            ));

 
            if ($rowsLeftToBackup === 0) {
                $this->jobDataDto->setTotalRowsBackup(0);
                $this->jobDataDto->setTableRowsOffset(0);
                $this->jobDataDto->setTableAverageRowLength(0);
 
                $this->jobDataDto->setLastInsertId(-PHP_INT_MAX);
                $this->stepsDto->incrementCurrentStep();






                $this->persistStepsDto();
                $this->persistJobDataDto();
            }

            if ($rowsExporter->doExceedSplitSize()) {
                $this->jobDataDto->setMaxDbPartIndex($this->jobDataDto->getMaxDbPartIndex() + 1);
                return $this->generateResponse(false);
            }
        } while (!$this->isThreshold() && !$this->stepsDto->isFinished());

        return $this->generateResponse(false);
    }








    private function lockTableOrRetryLater(RowsExporter $rowsExporter)
    {
        $table = $rowsExporter->getTableBeingBackup();
        $this->stopWhenTableLockWasGivenUp($table);

        $retryAt = $this->jobDataDto->getTableLockRetryAt();
        if ($retryAt > time()) {
            return $this->retryLaterResponse($table, $retryAt);
        }

        $this->logger->info(sprintf('Backup database: Requesting a write lock on table %s, waiting up to %d seconds.', $table, TableService::LOCK_WAIT_SECONDS));

        try {
            $rowsExporter->lockTable();
        } catch (RuntimeException $refusal) {
            if ($refusal->getCode() === TableService::LOCK_WAIT_TIMEOUT_ERRNO) {
                return $this->retryLaterResponse($table, $this->recordRefusedTableLock($table));
            }

            throw TableLockGivenUpException::forRefusedLock($table, $refusal->getMessage());
        }

        $this->jobDataDto->setTableLockAttempts(0);
        $this->jobDataDto->setTableLockRetryAt(0);
        $this->logger->info(sprintf('Backup database: Locked table %s.', $table));

        return null;
    }








    private function stopWhenTableLockWasGivenUp(string $table)
    {
        $attempts = $this->jobDataDto->getTableLockAttempts();
        if ($attempts < self::TABLE_LOCK_ATTEMPTS) {
            return;
        }

        throw TableLockGivenUpException::forTable($table, $attempts);
    }






    private function recordRefusedTableLock(string $table): int
    {
        $attempts = $this->jobDataDto->getTableLockAttempts() + 1;
        $this->jobDataDto->setTableLockAttempts($attempts);
        $this->stopWhenTableLockWasGivenUp($table);

        $retryDelay = self::TABLE_LOCK_RETRY_SECONDS * $attempts;
        $retryAt    = time() + $retryDelay;
        $this->jobDataDto->setTableLockRetryAt($retryAt);
        $this->logger->warning(sprintf(
            'Backup database: Table %s is held by another query and the lock timed out after %d seconds. Retrying in %d seconds (%d/%d).',
            $table,
            TableService::LOCK_WAIT_SECONDS,
            $retryDelay,
            $attempts,
            self::TABLE_LOCK_ATTEMPTS
        ));

        return $retryAt;
    }









    private function retryLaterResponse(string $table, int $retryAt): TaskResponseDto
    {
        $retryDelay = $retryAt - time();
        $response   = $this->generateResponse(false);
        $this->job->getTransientCache()->deferUpload($retryAt, $retryAt);
        /* translators: 1: database table name, 2: UTC time of the next lock request */
        $response->setStatusTitle(sprintf(__('Waiting for table %1$s to be released. Next lock request: %2$s UTC.', 'wp-staging'), $table, gmdate('Y-m-d H:i:s', $retryAt)));
        $response->setRetryAt($retryAt);
        $response->setRetryDelay($retryDelay);
        $this->updateTaskProgress($response);

        return $response;
    }





    private function unlockTable(RowsExporter $rowsExporter)
    {
        $table = $rowsExporter->getTableBeingBackup();
        if ($rowsExporter->unLockTables()) {
            $this->logger->info(sprintf('Backup database: Released the lock on table %s.', $table));
            return;
        }

        $this->logger->warning(sprintf('Backup database: Could not release the lock on table %s; the connection releases it when this request ends.', $table));
    }




    protected function setupDatabaseFilePathName()
    {
        global $wpdb;
        if ($this->jobDataDto->getIsMultipartBackup()) {
            $this->setupMultipartDatabaseFilePathName($wpdb);
            return;
        }

        if ($this->jobDataDto->getDatabaseFile()) {
            return;
        }

        $basename = $this->getDatabaseFilename($wpdb);
        $this->jobDataDto->setDatabaseFile($this->directory->getCacheDirectory() . $basename);
    }









    protected function getDatabaseFilename(wpdb $wpdb, int $partIndex = 0, bool $useCache = false): string
    {
        if ($useCache) {
            $databaseFilename = $this->getCachedDatabaseFilenameForPart($partIndex);
            if (!empty($databaseFilename)) {
                return $databaseFilename;
            }
        }

        $identifier = PartIdentifier::DATABASE_PART_IDENTIFIER;
        if ($partIndex > 0) {
            $identifier .= '.' . $partIndex;
        }

        return sprintf(
            '%s_%s_%s.%s.%s.%s',
            parse_url(get_home_url())['host'],
            current_time('Ymd-His'),
            $this->getJobId(),
            rtrim($wpdb->base_prefix, '_-'),
            $identifier,
            DatabaseImporter::FILE_FORMAT
        );
    }





    protected function getCachedDatabaseFilenameForPart(int $partIndex): string
    {
        return '';
    }

    protected function setupMultipartDatabaseFilePathName(wpdb $wpdb)
    {
 
    }









    protected function upsertDatabaseTableInfo(RowsExporter $rowsExporter, string $tableName, int $rows)
    {
        $this->jobDataDto->setDatabaseTableInfo(
            $rowsExporter->getTableNameForExport($tableName),
            $rows
        );
    }











    private function discardRowsWrittenAfterLastCheckpoint(RowsExporter $rowsExporter)
    {
        $databaseFile = $this->jobDataDto->getDatabaseFile();

 
 
        if ($this->jobDataDto->getSqlCheckpointFile() !== $databaseFile) {
            $writtenBytes = $this->measureCheckpoint($rowsExporter);

            $this->jobDataDto->setSqlCheckpointFile($databaseFile);
            $this->commitCheckpoint($writtenBytes);

            return;
        }

        $checkpoint   = $this->jobDataDto->getSqlWrittenBytes();
        $writtenBytes = $rowsExporter->getWrittenBytes();

        if ($writtenBytes === AbstractExporter::BYTES_UNKNOWN) {
            throw new RuntimeException('Backup database: Could not measure the export file, so the unaccounted rows of the previous request cannot be discarded safely.');
        }

        $result = $rowsExporter->truncateTo($checkpoint);

        if ($result === AbstractExporter::TRUNCATE_FAILED) {
 
 
            throw new RuntimeException(sprintf('Backup database: Could not discard %s of unaccounted rows from a request that did not finish.', size_format($writtenBytes - $checkpoint)));
        }

        if ($result === AbstractExporter::TRUNCATE_NOT_NEEDED) {
            return;
        }

        $this->logger->info(sprintf(
            'Backup database: Discarded %s of unaccounted rows from a request that did not finish, and will export them again.',
            size_format($writtenBytes - $checkpoint)
        ));
    }











    private function measureCheckpoint(RowsExporter $rowsExporter): int
    {
        $writtenBytes = $rowsExporter->getWrittenBytes();

        if ($writtenBytes === AbstractExporter::BYTES_UNKNOWN) {
            throw new RuntimeException('Backup database: Could not measure the export file to record a checkpoint.');
        }

        return $writtenBytes;
    }









    private function commitCheckpoint(int $writtenBytes)
    {
        $this->jobDataDto->setSqlWrittenBytes($writtenBytes);
        $this->persistJobDataDto();
    }




    private function isMemoryExhaustFixEnabled(): bool
    {
        return defined('WPSTG_MEMORY_EXHAUST_FIX') && (constant('WPSTG_MEMORY_EXHAUST_FIX') === true);
    }






    private function getTablesExcludedFromRowsExport(): array
    {
        $tables = (array)Hooks::applyFilters(self::FILTER_EXCLUDE_TABLES_DATA, []);
        $blogId = $this->jobDataDto->getSubsiteBlogId();
        if (!is_multisite() || !$this->jobDataDto->getIsNetworkSiteBackup() || $blogId === 1) {
            return $tables;
        }

        return array_merge($tables, array_map(function ($table) use ($blogId) {
            return $blogId . '_' . $table;
        }, $tables));
    }
}
