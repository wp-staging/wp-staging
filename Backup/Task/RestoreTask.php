<?php

namespace WPStaging\Backup\Task;

use WPStaging\Backup\Ajax\Restore\PrepareRestore;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Dto\JobDataDto;

abstract class RestoreTask extends AbstractTask
{
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
        ) {
            $jobDataDto->setDatabaseOnlyBackup(true);
        }

        parent::setJobDataDto($jobDataDto);
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
}
