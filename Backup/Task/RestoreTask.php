<?php

namespace WPStaging\Backup\Task;

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

    protected function isBackupPartSkipped(string $partName): bool
    {
        $excludedParts = Hooks::applyFilters(self::FILTER_EXCLUDE_BACKUP_PARTS, []);
        if (empty($excludedParts)) {
            return false;
        }

        return in_array($partName, $excludedParts);
    }
}
