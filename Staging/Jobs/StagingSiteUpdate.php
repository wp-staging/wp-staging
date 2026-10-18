<?php

namespace WPStaging\Staging\Jobs;

use WPStaging\Staging\Dto\Job\StagingSiteJobsDataDto;
use WPStaging\Staging\Tasks\StagingSite\Database\CleanupExistingPreservedTablesTask;
use WPStaging\Staging\Tasks\StagingSite\Database\CleanupPreservedTablesTask;
use WPStaging\Staging\Tasks\StagingSite\Database\ImportDatabaseRowsTask;
use WPStaging\Staging\Tasks\StagingSite\Database\PrepareDatabaseRowsTask;
use WPStaging\Staging\Tasks\StagingSite\Database\PrepareStagingSiteTablesTask;
use WPStaging\Staging\Tasks\StagingSite\DatabaseAdjustment\PreserveOptionsOnUpdateTask;
use WPStaging\Staging\Tasks\StagingSiteUpdate\FinishStagingSiteUpdateTask;
use WPStaging\Staging\Tasks\StagingSiteUpdate\UpdateRequirementsCheckTask;
use WPStaging\Staging\Traits\WithDataAdjustmentTasks;

 
class StagingSiteUpdate extends AbstractStagingSiteJob
{
    use WithDataAdjustmentTasks;

 
    const ACTION_CLONING_COMPLETE = 'wpstg_cloning_complete';

 
    protected $jobDataDto;

    public static function getJobName()
    {
        return 'staging_site_update';
    }

    protected function isRewritingExistingStagingSite(): bool
    {
        return true;
    }

    protected function init()
    {
        $this->addRequirementsCheckTask();
        $this->addDatabaseTasks();
        $this->addFilesystemTasks();
        $this->addAdvanceTasks();
        $this->addDataAdjustmentTasks();
        $this->addPostUpdateCleanupTask();
        $this->addFinishStagingSiteUpdateTask();
    }

    protected function addRequirementsCheckTask()
    {
        $this->tasks[] = UpdateRequirementsCheckTask::class;
    }

    protected function addDatabaseTasks()
    {
 
        if ($this->jobDataDto->getAllTablesExcluded() && empty($this->jobDataDto->getNonSiteTables())) {
            return;
        }

        $this->tasks[] = CleanupExistingPreservedTablesTask::class;
        $this->tasks[] = PrepareStagingSiteTablesTask::class;
        $this->tasks[] = PrepareDatabaseRowsTask::class;
        $this->tasks[] = ImportDatabaseRowsTask::class;
    }

    protected function addFinishStagingSiteUpdateTask()
    {
        $this->tasks[] = FinishStagingSiteUpdateTask::class;
    }

    protected function addPostUpdateCleanupTask()
    {
        if ($this->jobDataDto->getAllTablesExcluded() && empty($this->jobDataDto->getNonSiteTables())) {
            return;
        }

        $this->tasks[] = CleanupPreservedTablesTask::class;
    }

    protected function addAdvanceTasks()
    {
        if ($this->jobDataDto->getAllTablesExcluded() && empty($this->jobDataDto->getNonSiteTables())) {
            return;
        }

        $this->tasks[] = PreserveOptionsOnUpdateTask::class;
    }
}
