<?php

namespace WPStaging\Backup\Job\Jobs;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Job\AbstractJob;
use WPStaging\Backup\Task\Tasks\JobBackup\DatabaseBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupMuPluginsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupOtherFilesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupPluginsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupRequirementsCheckTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupThemesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupUploadsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\FilesystemScannerTask;
use WPStaging\Backup\Task\Tasks\JobBackup\FinalizeBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\ValidateBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\IncludeDatabaseTask;
use WPStaging\Backup\Task\Tasks\JobBackup\ScheduleBackupTask;

class JobBackup extends AbstractJob
{
    /** @var JobBackupDataDto $jobDataDto */
    protected $jobDataDto;

    /** @var array The array of tasks to execute for this job. Populated at init(). */
    protected $tasks = [];

    public static function getJobName()
    {
        return 'backup_job';
    }

    protected function getJobTasks()
    {
        return $this->tasks;
    }

    protected function execute()
    {
        //$this->startBenchmark();

        try {
            $response = $this->getResponse($this->currentTask->execute());
        } catch (\Exception $e) {
            $this->currentTask->getLogger()->critical('Backup job failed! Error: ' . $e->getMessage());
            $response = $this->getResponse($this->currentTask->generateResponse(false));
        }

        //$this->finishBenchmark(get_class($this->currentTask));

        return $response;
    }

    /**
     * @return void
     */
    protected function init()
    {
        $this->setRequirementTask();

        if ($this->jobDataDto->getRepeatBackupOnSchedule() && !$this->jobDataDto->getIsCreateScheduleBackupNow()) {
            $this->addSchedulerTask();
            $this->addFinishBackupTask();
            return;
        }

        $this->setScannerTask();
        if ($this->jobDataDto->getIsExportingOtherWpContentFiles()) {
            $this->tasks[] = BackupOtherFilesTask::class;
        }

        if ($this->jobDataDto->getIsExportingPlugins()) {
            $this->tasks[] = BackupPluginsTask::class;
        }

        if ($this->jobDataDto->getIsExportingMuPlugins()) {
            $this->tasks[] = BackupMuPluginsTask::class;
        }

        if ($this->jobDataDto->getIsExportingThemes()) {
            $this->tasks[] = BackupThemesTask::class;
        }

        if ($this->jobDataDto->getIsExportingUploads()) {
            $this->tasks[] = BackupUploadsTask::class;
        }

        if ($this->jobDataDto->getIsExportingDatabase()) {
            $this->tasks[] = DatabaseBackupTask::class;
        }

        if ($this->jobDataDto->getIsExportingDatabase() && !$this->jobDataDto->getIsMultipartBackup()) {
            $this->tasks[] = IncludeDatabaseTask::class;
        }

        $this->addFinalizeTask();
        if ($this->jobDataDto->getRepeatBackupOnSchedule()) {
            $this->addSchedulerTask();
        }

        $this->tasks[] = ValidateBackupTask::class;

        $this->addStoragesTasks();

        $this->addFinishBackupTask();
    }

    /**
     * @return void
     */
    protected function addStoragesTasks()
    {
        // Used in PRO version
    }

    /**
     * @return void
     */
    protected function addFinalizeTask()
    {
        $this->tasks[] = FinalizeBackupTask::class;
    }

    /**
     * @return void
     */
    protected function addFinishBackupTask()
    {
        $this->tasks[] = FinishBackupTask::class;
    }

    /**
     * @return void
     */
    protected function addSchedulerTask()
    {
        $this->tasks[] = ScheduleBackupTask::class;
    }

    /**
     * @return void
     */
    protected function setRequirementTask()
    {
        $this->tasks[] = BackupRequirementsCheckTask::class;
    }

    /**
     * @return void
     */
    protected function setScannerTask()
    {
        $this->tasks[] = FilesystemScannerTask::class;
    }
}
