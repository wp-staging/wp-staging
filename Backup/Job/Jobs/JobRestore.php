<?php

namespace WPStaging\Backup\Job\Jobs;

use RuntimeException;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Task\Tasks\JobRestore\StartRestoreTask;
use WPStaging\Backup\Task\Tasks\JobRestore\CleanExistingMediaTask;
use WPStaging\Backup\Task\Tasks\JobRestore\ExtractFilesTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreLanguageFilesTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreOtherFilesInWpContentTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreOtherFilesInWpRootTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RenameDatabaseTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreRequirementsCheckTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreDatabaseTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreMuPluginsTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestorePluginsTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreThemesTask;
use WPStaging\Backup\Task\Tasks\JobRestore\UpdateBackupsScheduleTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreFinishTask;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsBackupRestore;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Task\Tasks\CleanupBakTablesTask;
use WPStaging\Framework\Job\Task\Tasks\CleanupTmpFilesTask;
use WPStaging\Framework\Job\Task\Tasks\CleanupTmpTablesTask;

class JobRestore extends AbstractJob
{
 
    const TMP_DIRECTORY = 'tmp/restore/';

 
    protected $jobDataDto;

 
    protected $backupMetadata;

 
    protected $tasks = [];




    public static function getJobName(): string
    {
        return 'backup_restore';
    }




    protected function getJobTasks(): array
    {
        return $this->tasks;
    }




    public function onWpShutdown()
    {
        if ($this->jobDataDto->isFinished() && !$this->jobDataDto->getIsSyncRequest()) {
            WPStaging::make(AnalyticsBackupRestore::class)->enqueueFinishEvent($this->jobDataDto->getId(), $this->jobDataDto);
        }

        parent::onWpShutdown();
    }




    protected function execute(): TaskResponseDto
    {
 

        try {
            $response = $this->getResponse($this->currentTask->execute());
            $this->jobDataDto->setCaughtExceptionRetries(0);
        } catch (\Exception $e) {
            $this->currentTask->getLogger()->critical($e->getMessage());

            $retries = $this->jobDataDto->getCaughtExceptionRetries() + 1;
            $this->jobDataDto->setCaughtExceptionRetries($retries);

            if ($retries >= $this->maxRetries) {
                $this->deleteJobDataCache();

                return $this->getJobFailResponse($e->getMessage());
            }

            $response = $this->getResponse($this->currentTask->generateResponse(false));
        }

 

        return $response;
    }





    protected function init()
    {
        $this->tasks = [];

        $this->setupBackupMetadata();

        $this->tasks[] = StartRestoreTask::class;
        $this->tasks[] = CleanupTmpFilesTask::class;
        $this->tasks[] = CleanupTmpTablesTask::class;
        if ($this->backupMetadata->getIsExportingDatabase()) {
            $this->tasks[] = CleanupBakTablesTask::class;
        }

        $this->setRequirementTask();

        if ($this->backupMetadata->getIsExportingUploads()) {
            $this->tasks[] = CleanExistingMediaTask::class;
        }

        $this->addExtractFilesTasks();

        if ($this->backupMetadata->getIsExportingThemes()) {
            $this->tasks[] = RestoreThemesTask::class;
        }

        if ($this->backupMetadata->getIsExportingPlugins()) {
            $this->tasks[] = RestorePluginsTask::class;
        }

        if (
            $this->backupMetadata->getIsExportingThemes()
            || $this->backupMetadata->getIsExportingPlugins()
            || $this->backupMetadata->getIsExportingMuPlugins()
            || $this->backupMetadata->getIsExportingOtherWpContentFiles()
        ) {
            $this->tasks[] = RestoreLanguageFilesTask::class;
        }

        if ($this->backupMetadata->getIsExportingOtherWpContentFiles()) {
            $this->tasks[] = RestoreOtherFilesInWpContentTask::class;
        }

        $this->addRestoreOtherFilesInWpRootTasks();

        if ($this->backupMetadata->getIsExportingDatabase()) {
            $this->addDatabaseTasks();
        }

        if ($this->backupMetadata->getIsExportingMuPlugins()) {
            $this->tasks[] = RestoreMuPluginsTask::class;
        }

        $this->tasks[] = CleanupTmpFilesTask::class;

        $this->addFinishTask();
    }




    protected function setRequirementTask()
    {
        $this->tasks[] = RestoreRequirementsCheckTask::class;
    }




    protected function addRestoreOtherFilesInWpRootTasks()
    {
        if ($this->backupMetadata->getIsExportingOtherWpRootFiles()) {
            $this->tasks[] = RestoreOtherFilesInWpRootTask::class;
        }
    }




    protected function setupBackupMetadata()
    {
        if ($this->jobDataDto->getBackupMetadata()) {
            return;
        }

        $this->backupMetadata = (new BackupMetadata())->hydrateByFilePath($this->jobDataDto->getFile());

        if (!$this->isValidMetadata($this->backupMetadata)) {
            throw new RuntimeException('Failed to get backup metadata.');
        }

        $this->jobDataDto->setBackupMetadata($this->backupMetadata);
        $this->jobDataDto->setTmpDirectory($this->getJobTmpDirectory());
        $this->jobDataDto->determineIsSameSiteRestore();
    }




    protected function getJobTmpDirectory(): string
    {
        $dir = $this->directory->getTmpDirectory() . $this->jobDataDto->getId();
        $this->filesystem->mkdir($dir);

        return trailingslashit($dir);
    }




    protected function addDatabaseTasks()
    {
        $this->tasks[] = RestoreDatabaseTask::class;
        $this->tasks[] = UpdateBackupsScheduleTask::class;
        $this->tasks[] = RenameDatabaseTask::class;
        $this->tasks[] = CleanupTmpTablesTask::class;
    }




    protected function addExtractFilesTasks()
    {
        $this->tasks[] = ExtractFilesTask::class;
    }




    protected function addFinishTask()
    {
        $this->tasks[] = RestoreFinishTask::class;
    }






    protected function isValidMetadata(BackupMetadata $metadata): bool
    {
        $extension = pathinfo($this->jobDataDto->getFile(), PATHINFO_EXTENSION);
        if ($extension !== 'sql') {
            return !empty($metadata->getHeaderStart());
        }

        return !empty($metadata->getMaxTableLength()) && !empty($metadata->getMultipartMetadata()->getDatabaseParts());
    }
}
