<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use Exception;
use RuntimeException;
use WPStaging\Framework\Database\Exporter\AbstractExporter;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Service\Database\Exporter\DDLExporter;
use WPStaging\Backup\Service\Database\Exporter\RowsExporter;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Backup\Service\Database\Exporter\DDLExporterProvider;
use WPStaging\Backup\Service\Database\Exporter\RowsExporterProvider;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
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
    /** @var Directory */
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

    /**
     * @return object|TaskResponseDto
     * @throws Exception
     */
    public function execute()
    {
        $this->setupDatabaseFilePathName();

        // Tables to exclude without prefix
        $tablesToExclude = [
            'wpstg_queue',
            'wpstg_settings',
            'wpr_rucss_used_css',
        ];

        // Exclude these tables for main network site backups
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

        // First request: Create DDL
        if (!$this->stepsDto->getTotal()) {
            /** @var DDLExporter $ddlExporter */
            $ddlExporter = WPStaging::make(DDLExporterProvider::class)->getExporter();
            $ddlExporter->setIsNetworkSiteBackup($this->jobDataDto->getIsNetworkSiteBackup());
            $ddlExporter->setSubsiteBlogId($this->jobDataDto->getSubsiteBlogId());
            $ddlExporter->setFileName($this->jobDataDto->getDatabaseFile());
            $ddlExporter->setSubsites($subsites);
            $ddlExporter->setTablesToExclude($tablesToExclude);
            $ddlExporter->backupDDLTablesAndViews();
            $this->jobDataDto->setTablesToBackup(array_merge($ddlExporter->getTables(), $ddlExporter->getNonWPTables()));
            $this->jobDataDto->setNonWpTables($ddlExporter->getNonWPTables());
            $this->jobDataDto->setLastInsertId(-PHP_INT_MAX);

            $this->stepsDto->setTotal(count($this->jobDataDto->getTablesToBackup()));

            // Early bail: DDL created, do not increase step, so that the next request can start backing up rows from the first table.
            return $this->generateResponse(false);
        }

        // Safety check: Check that the DDL was successfully created
        if (empty($this->jobDataDto->getTablesToBackup())) {
            $this->logger->critical('Could not create the tables DDL.');
            throw new Exception('Could not create the tables DDL.');
        }

        // Action hook for internal use only: used during memory exhaust test
        Hooks::doAction('wpstg.tests.backup.export_database.before_rows_export');

        $useMemoryExhaustFix = $this->isMemoryExhaustFixEnabled();
        // Lazy instantiation
        /** @var RowsExporter $rowsExporter */
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
        $rowsExporter->setTablesToExclude($tablesToExclude);
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

                /*
                 * Persist the steps dto, so that if memory blows while processing
                 * the next table, the next request will continue from there.
                 * The job data goes with it, or the next table starts on this table's offset.
                 */
                $this->persistStepsDto();
                $this->persistJobDataDto();
                continue;
            }

            $rowsExporter->setTableRowsOffset($this->jobDataDto->getTableRowsOffset());
            $rowsExporter->setTotalRowsExported($this->jobDataDto->getTotalRowsBackup());

            // Maybe lock the table
            $tableLocked = false;
            $hasNumericIncrementalPk = false;

            try {
                $rowsExporter->getPrimaryKey();
                $hasNumericIncrementalPk = true;
            } catch (Exception $e) {
                $tableLocked = $rowsExporter->lockTable();
            }

            // Count rows once per table
            if ($this->jobDataDto->getTableRowsOffset() === 0) {
                $this->jobDataDto->setTotalRowsOfTableBeingBackup($rowsExporter->countTotalRows());

                if ($hasNumericIncrementalPk) {
                    /*
                     * We set the offset to the lowest number possible, so that we can start fetching
                     * rows even if their primary key values are a negative integer or zero.
                     */
                    $rowsExporter->setTableRowsOffset(-PHP_INT_MAX);
                }
            }

            $rowsExporter->setTotalRowsInCurrentTable($this->jobDataDto->getTotalRowsOfTableBeingBackup());

            try {
                $rowsLeftToBackup = $rowsExporter->backup($this->jobDataDto->getId(), $this->logger);

                if ($tableLocked) {
                    $rowsExporter->unLockTables();
                }
            } catch (Exception $e) {
                if ($tableLocked) {
                    $rowsExporter->unLockTables();
                }

                $this->logger->critical($e->getMessage());
                throw $e;
            }

            // Measured before the row progress moves, so a failure here cannot leave the job
            // holding an offset past the checkpoint it was persisted with.
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

            // Done with this table.
            if ($rowsLeftToBackup === 0) {
                $this->jobDataDto->setTotalRowsBackup(0);
                $this->jobDataDto->setTableRowsOffset(0);
                $this->jobDataDto->setTableAverageRowLength(0);
                // Reset for each table
                $this->jobDataDto->setLastInsertId(-PHP_INT_MAX);
                $this->stepsDto->incrementCurrentStep();

                /*
                 * Persist the steps dto, so that if memory blows while processing
                 * the next table, the next request will continue from there.
                 * The job data goes with it, or the next table starts on this table's offset.
                 */
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

    /**
     * @return void
     */
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

    /**
     * @param wpdb $wpdb
     * @param int $partIndex
     * @param bool $useCache If true, the filename will be retrieved from the job data dto if it exist,
     *                       otherwise a new name will be generated.
     *                       Use false to always generate a new name.
     * @return string
     */
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

    /**
     * @param int $partIndex
     * @return string
     */
    protected function getCachedDatabaseFilenameForPart(int $partIndex): string
    {
        return '';
    }

    protected function setupMultipartDatabaseFilePathName(wpdb $wpdb)
    {
        // no-op
    }

    /**
     * Drop rows a previous request wrote but never accounted for.
     *
     * Rows reach the sql file during the request, while the offset tracking them only
     * reaches the cache on `shutdown`. A request killed in between leaves rows no offset
     * covers, so the retry exports them twice and the restore fails with MySQL error 1062.
     *
     * @param RowsExporter $rowsExporter
     * @return void
     */
    private function discardRowsWrittenAfterLastCheckpoint(RowsExporter $rowsExporter)
    {
        $databaseFile = $this->jobDataDto->getDatabaseFile();

        // A rotated multipart file restarts the byte count, and the first request after the
        // DDL has no checkpoint yet. Neither may be truncated.
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
            // Carrying on would leave the rows the offset no longer accounts for in the export,
            // which is exactly the duplicate-key failure this checkpoint exists to prevent.
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

    /**
     * Measuring is the only step that can fail, so it runs before anything is written to the
     * dto. A failure after the row offset moved would be persisted by the job as an offset
     * that accounts for rows the checkpoint does not cover, and the retry would truncate them
     * away and resume past them.
     *
     * @param RowsExporter $rowsExporter
     * @return int
     * @throws RuntimeException
     */
    private function measureCheckpoint(RowsExporter $rowsExporter): int
    {
        $writtenBytes = $rowsExporter->getWrittenBytes();

        if ($writtenBytes === AbstractExporter::BYTES_UNKNOWN) {
            throw new RuntimeException('Backup database: Could not measure the export file to record a checkpoint.');
        }

        return $writtenBytes;
    }

    /**
     * Checkpoint and offset are only consistent when written together, and now rather than
     * on `shutdown`: the steps dto is persisted mid-request too, so a stale checkpoint would
     * truncate away a table that is already marked done.
     *
     * @param int $writtenBytes
     * @return void
     */
    private function commitCheckpoint(int $writtenBytes)
    {
        $this->jobDataDto->setSqlWrittenBytes($writtenBytes);
        $this->persistJobDataDto();
    }

    /**
     * @return bool
     */
    private function isMemoryExhaustFixEnabled(): bool
    {
        return defined('WPSTG_MEMORY_EXHAUST_FIX') && (constant('WPSTG_MEMORY_EXHAUST_FIX') === true);
    }
}
