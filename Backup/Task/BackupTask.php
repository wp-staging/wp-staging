<?php

namespace WPStaging\Backup\Task;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Task\Tasks\JobBackup\FilesystemScannerTask;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Task\AbstractTask;

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
            && !$jobDataDto->getIsExportingOtherWpRootFiles()
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
            !$this instanceof FilesystemScannerTask
            && !$this instanceof FileBackupTask
        ) {
            $response->addMessage($this->logger->getLastLogMsg());
        }
    }
}
