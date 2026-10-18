<?php

namespace WPStaging\Staging\Jobs;

use WPStaging\Staging\Dto\Job\StagingSiteDeleteDataDto;
use WPStaging\Staging\Tasks\StagingSite\CleanupStagingFilesTask;
use WPStaging\Staging\Tasks\StagingSite\CleanupStagingTablesTask;
use WPStaging\Staging\Tasks\StagingSiteDelete\FinishStagingSiteDeleteTask;

class StagingSiteDelete extends AbstractStagingSiteJob
{
 
    protected $jobDataDto;

    public static function getJobName()
    {
        return 'staging_site_delete';
    }

    protected function init()
    {
        if ($this->jobDataDto->getIsDeletingTables()) {
            $this->tasks[] = CleanupStagingTablesTask::class;
        }

        if ($this->jobDataDto->getIsDeletingFiles()) {
            $this->tasks[] = CleanupStagingFilesTask::class;
        }

        $this->tasks[] = FinishStagingSiteDeleteTask::class;
    }
}
