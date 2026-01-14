<?php

namespace WPStaging\Staging\Jobs;

use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Job\Task\Tasks\CleanupBakTablesTask;
use WPStaging\Staging\Dto\Job\StagingSiteJobsDataDto;
use WPStaging\Staging\Tasks\StagingSite\Database\ImportDatabaseRowsTask;
use WPStaging\Staging\Tasks\StagingSite\Database\PrepareDatabaseRowsTask;
use WPStaging\Staging\Tasks\StagingSite\Database\PrepareStagingSiteTablesTask;
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
use WPStaging\Staging\Tasks\StagingSiteUpdate\FinishStagingSiteUpdateTask;
use WPStaging\Staging\Tasks\StagingSiteUpdate\UpdateRequirementsCheckTask;
use WPStaging\Staging\Traits\WithDataAdjustmentTasks;

class StagingSiteUpdate extends AbstractJob
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
        return 'staging_site_update';
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
        $this->addFinishStagingSiteUpdateTask();
    }

    protected function addRequirementsCheckTask()
    {
        $this->tasks[] = UpdateRequirementsCheckTask::class;
    }

    protected function addDatabaseTasks()
    {
        // Early return if all tables are excluded
        if ($this->jobDataDto->getAllTablesExcluded() && empty($this->jobDataDto->getNonSiteTables())) {
            return;
        }

        $this->tasks[] = CleanupBakTablesTask::class;
        $this->tasks[] = PrepareStagingSiteTablesTask::class;
        $this->tasks[] = PrepareDatabaseRowsTask::class;
        $this->tasks[] = ImportDatabaseRowsTask::class;
    }

    protected function addFilesystemTasks()
    {
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

    protected function addFinishStagingSiteUpdateTask()
    {
        $this->tasks[] = FinishStagingSiteUpdateTask::class;
    }

    protected function addAdvanceTasks()
    {
        // no-op, used in PRO
    }
}
