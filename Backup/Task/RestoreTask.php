<?php

namespace WPStaging\Backup\Task;

use WPStaging\Backup\Ajax\Restore\PrepareRestore;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Task\Tasks\JobRestore\ExtractFilesTask;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Task\AbstractTask;

abstract class RestoreTask extends AbstractTask
{
    /** @var string */
    const FILTER_EXCLUDE_BACKUP_PARTS = 'wpstg.backup.restore.exclude_backup_parts';

    /** @var JobRestoreDataDto */
    protected $jobDataDto;

    /** @var string */
    protected $tmpDatabasePrefix;

    public function setJobDataDto(JobDataDto $jobDataDto)
    {
        /** @var JobRestoreDataDto $jobDataDto */
        if (
            $jobDataDto->getBackupMetadata()->getIsExportingDatabase()
            && !$jobDataDto->getBackupMetadata()->getIsExportingMuPlugins()
            && !$jobDataDto->getBackupMetadata()->getIsExportingOtherWpContentFiles()
            && !$jobDataDto->getBackupMetadata()->getIsExportingPlugins()
            && !$jobDataDto->getBackupMetadata()->getIsExportingThemes()
            && !$jobDataDto->getBackupMetadata()->getIsExportingUploads()
            && !$jobDataDto->getBackupMetadata()->getIsExportingOtherWpRootFiles()
        ) {
            $jobDataDto->setDatabaseOnlyBackup(true);
        }

        parent::setJobDataDto($jobDataDto);
    }

    protected function addLogMessageToResponse(TaskResponseDto $response)
    {
        /**
         * If this backup contains only a database, let's not display log entries
         * for file-related tasks, as they expose internal behavior of the backup
         * feature that are not relevant to the user.
         */
        if (!$this->jobDataDto->getDatabaseOnlyBackup()) {
            $response->addMessage($this->logger->getLastLogMsg());
            return;
        }

        if (
            !$this instanceof ExtractFilesTask
        ) {
            $response->addMessage($this->logger->getLastLogMsg());
        }
    }

    /**
     * @param string $tmpPrefix
     */
    public function setTmpPrefix($tmpPrefix)
    {
        $this->tmpDatabasePrefix = $tmpPrefix;
    }

    /**
     * @param string $table
     * @param string $prefix
     *
     * @return string
     */
    public function addShortNameTable($table, $prefix)
    {
        $shortName = uniqid($prefix) . str_pad(rand(0, 999999), 6, '0');
        if ($prefix === $this->tmpDatabasePrefix) {
            $this->jobDataDto->addShortNameTableToRestore($table, $shortName);
        } elseif ($prefix === PrepareRestore::TMP_DATABASE_PREFIX_TO_DROP) {
            $this->jobDataDto->addShortNameTableToDrop($table, $shortName);
        }

        return $shortName;
    }

    /**
     * @param string $table
     * @param string $prefix
     *
     * @return string
     */
    public function getShortNameTable($table, $prefix)
    {
        $shortTables = [];
        if ($prefix === $this->tmpDatabasePrefix) {
            $shortTables = $this->jobDataDto->getShortNamesTablesToRestore();
        } elseif ($prefix === PrepareRestore::TMP_DATABASE_PREFIX_TO_DROP) {
            $shortTables = $this->jobDataDto->getShortNamesTablesToDrop();
        }

        return array_search($table, $shortTables);
    }

    /**
     * @param string $table
     * @param string $prefix
     *
     * @return string
     */
    public function getFullNameTableFromShortName($table, $prefix)
    {
        $shortTables = [];
        if ($prefix === $this->tmpDatabasePrefix) {
            $shortTables = $this->jobDataDto->getShortNamesTablesToRestore();
        } elseif ($prefix === PrepareRestore::TMP_DATABASE_PREFIX_TO_DROP) {
            $shortTables = $this->jobDataDto->getShortNamesTablesToDrop();
        }

        if (!array_key_exists($table, $shortTables)) {
            return $table;
        }

        return $shortTables[$table];
    }

    protected function isBackupPartSkipped(string $partName): bool
    {
        $excludedParts = Hooks::applyFilters(self::FILTER_EXCLUDE_BACKUP_PARTS, []);
        if (empty($excludedParts)) {
            return false;
        }

        return in_array($partName, $excludedParts);
    }
}
