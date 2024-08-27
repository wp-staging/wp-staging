<?php

namespace WPStaging\Backup\Job\Jobs;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupMuPluginsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupOtherFilesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupPluginsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupRequirementsCheckTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupThemesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupUploadsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\DatabaseBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\FilesystemScannerTask;
use WPStaging\Backup\Task\Tasks\JobBackup\FinalizeBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\IncludeDatabaseTask;
use WPStaging\Backup\Task\Tasks\JobBackup\ScheduleBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\SignBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\ValidateBackupTask;
use WPStaging\Framework\Job\AbstractJob;

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

        if ($this->jobDataDto->getIsCreateBackupInBackground()) {
            if ($this->jobDataDto->getRepeatBackupOnSchedule()) {
                $this->addSchedulerTask();
            }

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

        $this->addBackupOtherWpRootFilesTasks();

        $this->addDatabaseTasks();

        $this->addCompressionTask();
        $this->addFinalizeTask();
        if ($this->jobDataDto->getRepeatBackupOnSchedule()) {
            $this->addSchedulerTask();
        }

        /**
         * Validation is a must to ensure the backup is valid.
         * But it cannot be done during backup listing otherwise it will consume a lot of memory and may result in timeout.
         * So validation should be done before signing of backup. So we can stop the process and keep backup invalid state in case if validation fails.
         */
        if ($this->jobDataDto->getIsValidateBackupFiles()) {
            $this->addValidationTasks();
        }

        $this->tasks[] = SignBackupTask::class;

        $this->addStoragesTasks();
        $this->addFinishBackupTask();
    }

    protected function addDatabaseTasks()
    {
        if (!$this->jobDataDto->getIsExportingDatabase()) {
            return;
        }

        $this->tasks[] = DatabaseBackupTask::class;
        $this->tasks[] = IncludeDatabaseTask::class;
    }

    protected function addCompressionTask()
    {
        // Used in PRO version
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
    protected function addBackupOtherWpRootFilesTasks()
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

    protected function addValidationTasks()
    {
        $this->tasks[] = ValidateBackupTask::class;
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
