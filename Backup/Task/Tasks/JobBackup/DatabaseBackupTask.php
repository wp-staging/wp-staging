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

 
        $tablesToExclude = [
            'wpstg_queue',
            'wpstg_settings',
            'wpr_rucss_used_css',
        ];

 
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
            $this->jobDataDto->setTablesToBackup(array_merge($ddlExporter->getTables(), $ddlExporter->getNonWPTables()));
            $this->jobDataDto->setNonWpTables($ddlExporter->getNonWPTables());
            $this->jobDataDto->setLastInsertId(-PHP_INT_MAX);

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






                $this->persistStepsDto();
                $this->persistJobDataDto();
                continue;
            }

            $rowsExporter->setTableRowsOffset($this->jobDataDto->getTableRowsOffset());
            $rowsExporter->setTotalRowsExported($this->jobDataDto->getTotalRowsBackup());

 
            $tableLocked = false;
            $hasNumericIncrementalPk = false;

            try {
                $rowsExporter->getPrimaryKey();
                $hasNumericIncrementalPk = true;
            } catch (Exception $e) {
                $tableLocked = $rowsExporter->lockTable();
            }

 
            if ($this->jobDataDto->getTableRowsOffset() === 0) {
                $this->jobDataDto->setTotalRowsOfTableBeingBackup($rowsExporter->countTotalRows());

                if ($hasNumericIncrementalPk) {




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
}
