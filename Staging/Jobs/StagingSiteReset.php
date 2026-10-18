<?php

namespace WPStaging\Staging\Jobs;

use WPStaging\Staging\Dto\Job\StagingSiteJobsDataDto;
use WPStaging\Staging\Tasks\StagingSite\CleanupStagingFilesTask;
use WPStaging\Staging\Tasks\StagingSite\Database\CreateDatabaseTablesTask;
use WPStaging\Staging\Tasks\StagingSite\Database\ImportDatabaseRowsTask;
use WPStaging\Staging\Tasks\StagingSite\Database\PrepareDatabaseRowsTask;
use WPStaging\Staging\Tasks\StagingSiteReset\FinishStagingSiteResetTask;
use WPStaging\Staging\Tasks\StagingSiteReset\ResetRequirementsCheckTask;
use WPStaging\Staging\Tasks\StagingSiteReset\ResetStagingTablesTask;
use WPStaging\Staging\Traits\WithDataAdjustmentTasks;

 
class StagingSiteReset extends AbstractStagingSiteJob
{
    use WithDataAdjustmentTasks;

 
    const ACTION_CLONING_COMPLETE = 'wpstg_cloning_complete';

 
    protected $jobDataDto;

    public static function getJobName()
    {
        return 'staging_site_reset';
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
        $this->addFinishStagingSiteResetTask();
    }

    protected function addRequirementsCheckTask()
    {
        $this->tasks[] = ResetRequirementsCheckTask::class;
    }

    protected function addDatabaseTasks()
    {
 
        $this->tasks[] = ResetStagingTablesTask::class;

        if ($this->jobDataDto->getAllTablesExcluded() && empty($this->jobDataDto->getNonSiteTables())) {
            return;
        }

        $this->tasks[] = CreateDatabaseTablesTask::class;
        $this->tasks[] = PrepareDatabaseRowsTask::class;
        $this->tasks[] = ImportDatabaseRowsTask::class;
    }

    protected function addFilesystemTasks()
    {
        $this->tasks[] = CleanupStagingFilesTask::class;
        parent::addFilesystemTasks();
    }

    protected function addFinishStagingSiteResetTask()
    {
        $this->tasks[] = FinishStagingSiteResetTask::class;
    }

    protected function addAdvanceTasks()
    {
 
    }
}
