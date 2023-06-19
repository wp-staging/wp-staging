<?php

namespace WPStaging\Backup\Task;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Dto\JobDataDto;

abstract class BackupTask extends AbstractTask
{
    /** @var JobBackupDataDto */
    protected $jobDataDto;

    public function setJobDataDto(JobDataDto $jobDataDto)
    {
        /** @var JobBackupDataDto $jobDataDto */
        if (
            $jobDataDto->getIsExportingDatabase()
            && !$jobDataDto->getIsExportingMuPlugins()
            && !$jobDataDto->getIsExportingOtherWpContentFiles()
            && !$jobDataDto->getIsExportingPlugins()
            && !$jobDataDto->getIsExportingThemes()
            && !$jobDataDto->getIsExportingUploads()
        ) {
            $jobDataDto->setDatabaseOnlyBackup(true);
        }

        parent::setJobDataDto($jobDataDto);
    }
}
