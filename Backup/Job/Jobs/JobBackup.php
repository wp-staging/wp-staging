<?php

/**
 * Orchestrates the complete backup creation workflow for WordPress sites
 *
 * Manages the multi-stage backup process including database export, file scanning,
 * archiving, validation, and cleanup across multiple HTTP requests.
 */

namespace WPStaging\Backup\Job\Jobs;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupMuPluginsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupOtherFilesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupPluginsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupRequirementsCheckTask;
use WPStaging\Backup\Task\Tasks\JobBackup\CleanupValidationFilesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupThemesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupUploadsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\DatabaseBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\FilesystemScannerTask;
use WPStaging\Backup\Task\Tasks\JobBackup\FinalizeBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\IncludeDatabaseTask;
use WPStaging\Backup\Task\Tasks\JobBackup\RecalibrateFilesCountTask;
use WPStaging\Backup\Task\Tasks\JobBackup\ScheduleBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\SignBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\ValidateBackupTask;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Job\Task\AbstractTask;

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
            $title = $this->currentTask->getTaskTitle();
            if (empty($title)) {
                $title = 'Backup job';
            }

            $this->currentTask->getLogger()->critical($title . ' failed! Error: ' . $e->getMessage());
            $response = $this->getResponse($this->currentTask->generateResponse(false));
        }

        //$this->finishBenchmark(get_class($this->currentTask));

        return $response;
    }

    /**
     * Persist the job DTO after every task step, not just when a task fully finishes.
     *
     * Parent `getResponse` only persists when the current task has completed
     * (moveToNextTask branch) or when the queue is finished. For any mid-task step
     * that returns `isRunning=true` — e.g. DatabaseBackupTask's DDL phase writing
     * the tables list into the DTO, RowsExporter advancing `lastInsertId`, or
     * FilesystemScannerTask updating its running totals — state persistence relies
     * on the WordPress `shutdown` hook firing for this request. On some hosts that
     * hook doesn't run reliably (aggressive request termination, Object Cache Pro
     * drop-in ordering, plugins that die() earlier in shutdown), and the next
     * request hydrates a stale DTO — which surfaces as cryptic "Could not create
     * the tables DDL" errors mid-backup.
     *
     * Before writing the job DTO we mirror what `AbstractJob::persist()` does for
     * the running-task bookkeeping: sync the task's current `queueOffset` onto
     * the DTO and persist the task's own steps DTO. `AbstractTask::setJobDataDto()`
     * seeks the task queue back to this offset on the next request, so leaving it
     * stale would make tasks like `FilesystemScannerTask` rewind the queue and
     * reprocess items each resume. We do NOT call `AbstractJob::persist()` here
     * because its `isFinished && !isCleaned` branch runs `cleanup()`, which
     * removes the job cache file we just wrote — the shutdown hook is the
     * correct place for that cleanup.
     *
     * Writing a few extra hundred bytes per step is cheap compared to re-running
     * a multi-GB backup, so persist unconditionally here.
     *
     * @param TaskResponseDto $response
     * @return TaskResponseDto
     */
    protected function getResponse(TaskResponseDto $response)
    {
        $response = parent::getResponse($response);

        if ($this->currentTask instanceof AbstractTask) {
            $this->jobDataDto->setQueueOffset($this->currentTask->getQueue()->getOffset());
            $this->currentTask->persistStepsDto();
        }

        $this->persistJobDataDto();

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

        $this->addFinalizeTask();
        if ($this->jobDataDto->getRepeatBackupOnSchedule()) {
            $this->addSchedulerTask();
        }

        if (!$this->jobDataDto->getIsMultipartBackup()) {
            $this->tasks[] = RecalibrateFilesCountTask::class;
        }

        /**
         * Validation is a must to ensure the backup is valid.
         * But it cannot be done during backup listing otherwise it will consume a lot of memory and may result in timeout.
         * So validation should be done before signing of backup. So we can stop the process and keep backup invalid state in case if validation fails.
         */
        $this->addValidationTasks();

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
        $this->tasks[] = CleanupValidationFilesTask::class;
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
