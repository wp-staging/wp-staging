<?php

namespace WPStaging\Staging\Jobs;

use WPStaging\Staging\Dto\Job\StagingSiteJobsDataDto;
use WPStaging\Staging\Tasks\StagingSite\Database\CreateDatabaseTablesTask;
use WPStaging\Staging\Tasks\StagingSite\Database\ImportDatabaseRowsTask;
use WPStaging\Staging\Tasks\StagingSite\Database\PrepareDatabaseRowsTask;
use WPStaging\Staging\Tasks\StagingSiteCreate\CreateRequirementsCheckTask;
use WPStaging\Staging\Tasks\StagingSiteCreate\FinishStagingSiteCreateTask;
use WPStaging\Staging\Traits\WithDataAdjustmentTasks;

class StagingSiteCreate extends AbstractStagingSiteJob
{
    use WithDataAdjustmentTasks;

 
    const ACTION_CLONING_COMPLETE = 'wpstg_cloning_complete';






    const ACTION_STAGING_SITE_CREATED = 'wpstg_staging_site_created';

 
    protected $jobDataDto;

    public static function getJobName()
    {
        return 'staging_site_create';
    }

    protected function init()
    {
        $this->addRequirementsCheckTask();
        $this->addDatabaseTasks();
        $this->addFilesystemTasks();
        $this->addAdvanceTasks();
        $this->addDataAdjustmentTasks();
        $this->addFinishStagingSiteCreateTask();
    }

    protected function addRequirementsCheckTask()
    {
        $this->tasks[] = CreateRequirementsCheckTask::class;
    }

    protected function addDatabaseTasks()
    {
 
        if ($this->jobDataDto->getAllTablesExcluded() && empty($this->jobDataDto->getNonSiteTables())) {
            return;
        }

        $this->tasks[] = CreateDatabaseTablesTask::class;
        $this->tasks[] = PrepareDatabaseRowsTask::class;
        $this->tasks[] = ImportDatabaseRowsTask::class;
    }

    protected function addFinishStagingSiteCreateTask()
    {
        $this->tasks[] = FinishStagingSiteCreateTask::class;
    }

    protected function addAdvanceTasks()
    {
 
    }
}
