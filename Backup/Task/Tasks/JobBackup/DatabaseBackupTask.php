<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use Exception;
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
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use wpdb;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Filesystem\PartIdentifier;

class DatabaseBackupTask extends BackupTask
{
    /** @var Directory */
    protected $directory;

    /** @var int */
    private $currentPartIndex = 0;

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

        do {
            $rowsExporter->setTableIndex($this->stepsDto->getCurrent());

            if ($rowsExporter->isTableExcluded()) {
                $this->logger->info(sprintf(
                    'Backup database: Skipped Table %s by exclusion rule',
                    $rowsExporter->getTableBeingBackup()
                ));

                $this->jobDataDto->setTotalRowsBackup(0);
                $this->jobDataDto->setTableRowsOffset(0);
                $this->jobDataDto->setTableAverageRowLength(0);
                $this->stepsDto->incrementCurrentStep();

                /*
                 * Persist the steps dto, so that if memory blows while processing
                 * the next table, the next request will continue from there.
                 */
                $this->persistStepsDto();
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

            $this->stepsDto->setCurrent($rowsExporter->getTableIndex());
            if (!$useMemoryExhaustFix) {
                $this->jobDataDto->setTotalRowsBackup($rowsExporter->getTotalRowsExported());
                $this->jobDataDto->setTableRowsOffset($rowsExporter->getTableRowsOffset());
            }

            $this->logger->info(sprintf(
                'Backup database: Table %s. Rows: %s/%s',
                $rowsExporter->getTableBeingBackup(),
                number_format_i18n($rowsExporter->getTotalRowsExported()),
                number_format_i18n($this->jobDataDto->getTotalRowsOfTableBeingBackup())
            ));

            $this->logger->debug(sprintf(
                'Backup database: Table %s. Query time: %s Batch Size: %s last query json: %s',
                $rowsExporter->getTableBeingBackup(),
                $this->jobDataDto->getDbRequestTime(),
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
                 */
                $this->persistStepsDto();
            }

            if ($rowsExporter->doExceedSplitSize()) {
                $this->jobDataDto->setMaxDbPartIndex($this->currentPartIndex + 1);
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
     * @return bool
     */
    private function isMemoryExhaustFixEnabled(): bool
    {
        return defined('WPSTG_MEMORY_EXHAUST_FIX') && (constant('WPSTG_MEMORY_EXHAUST_FIX') === true);
    }
}
