<?php

namespace WPStaging\Staging\Jobs;

use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Staging\Dto\Job\StagingSiteJobsDataDto;
use WPStaging\Staging\Tasks\StagingSite\CleanupStagingFilesTask;
use WPStaging\Staging\Tasks\StagingSite\CleanupStagingTablesTask;
use WPStaging\Staging\Tasks\StagingSite\Database\CreateDatabaseTablesTask;
use WPStaging\Staging\Tasks\StagingSite\Database\ImportDatabaseRowsTask;
use WPStaging\Staging\Tasks\StagingSite\Database\PrepareDatabaseRowsTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyMuPluginsTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyPluginsTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyThemesTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyUploadsTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyWpAdminTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyWpContentTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyWpIncludesTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyWpRootDirectoriesTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\CopyWpRootFilesTask;
use WPStaging\Staging\Tasks\StagingSite\Filesystem\FilesystemScannerTask;
use WPStaging\Staging\Tasks\StagingSiteReset\FinishStagingSiteResetTask;
use WPStaging\Staging\Tasks\StagingSiteReset\ResetRequirementsCheckTask;
use WPStaging\Staging\Traits\WithDataAdjustmentTasks;

class StagingSiteReset extends AbstractJob
{
    use WithDataAdjustmentTasks;

    /** @var string */
    const ACTION_CLONING_COMPLETE = 'wpstg_cloning_complete';

    /** @var StagingSiteJobsDataDto $jobDataDto */
    protected $jobDataDto;

    /** @var array The array of tasks to execute for this job. Populated at init(). */
    protected $tasks = [];

    public static function getJobName()
    {
        return 'staging_site_reset';
    }

    protected function getJobTasks()
    {
        return $this->tasks;
    }

    protected function execute()
    {
        try {
            $response = $this->getResponse($this->currentTask->execute());
        } catch (\Exception $e) {
            $this->currentTask->getLogger()->critical($e->getMessage());
            $response = $this->getResponse($this->currentTask->generateResponse(false));
        }

        return $response;
    }

    protected function init()
    {
        $this->addRequirementsCheckTask();
        $this->addDatabaseTasks();
        $this->addFilesystemTasks();
        $this->addAdvanceTasks();
        $this->addDataAdjustmentTasks();
        $this->addFinishStagingSiteResetTask();
    }

    protected function addRequirementsCheckTask()
    {
        $this->tasks[] = ResetRequirementsCheckTask::class;
    }

    protected function addDatabaseTasks()
    {
        // Early return if all tables are excluded
        if ($this->jobDataDto->getAllTablesExcluded() && empty($this->jobDataDto->getNonSiteTables())) {
            return;
        }

        $this->tasks[] = CleanupStagingTablesTask::class;
        $this->tasks[] = CreateDatabaseTablesTask::class;
        $this->tasks[] = PrepareDatabaseRowsTask::class;
        $this->tasks[] = ImportDatabaseRowsTask::class;
    }

    protected function addFilesystemTasks()
    {
        $this->tasks[] = CleanupStagingFilesTask::class;
        $this->tasks[] = FilesystemScannerTask::class;
        $this->tasks[] = CopyWpRootFilesTask::class;
        $this->tasks[] = CopyWpAdminTask::class;
        $this->tasks[] = CopyWpIncludesTask::class;
        $this->tasks[] = CopyPluginsTask::class;
        $this->tasks[] = CopyMuPluginsTask::class;
        $this->tasks[] = CopyThemesTask::class;
        $this->tasks[] = CopyUploadsTask::class;
        $this->tasks[] = CopyWpContentTask::class;
        $this->tasks[] = CopyWpRootDirectoriesTask::class;
    }

    protected function addFinishStagingSiteResetTask()
    {
        $this->tasks[] = FinishStagingSiteResetTask::class;
    }

    protected function addAdvanceTasks()
    {
        // no-op, used in PRO
    }
}
