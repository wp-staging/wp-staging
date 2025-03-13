<?php

namespace WPStaging\Staging\Jobs;

use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Staging\Dto\Job\StagingSiteDeleteDataDto;
use WPStaging\Staging\Tasks\StagingSite\CleanupStagingFilesTask;
use WPStaging\Staging\Tasks\StagingSite\CleanupStagingTablesTask;
use WPStaging\Staging\Tasks\StagingSiteDelete\FinishStagingSiteDeleteTask;

class StagingSiteDelete extends AbstractJob
{
    /** @var StagingSiteDeleteDataDto $jobDataDto */
    protected $jobDataDto;

    /** @var array The array of tasks to execute for this job. Populated at init(). */
    private $tasks = [];

    public static function getJobName()
    {
        return 'staging_site_delete';
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
        if ($this->jobDataDto->getIsDeletingTables()) {
            $this->tasks[] = CleanupStagingTablesTask::class;
        }

        if ($this->jobDataDto->getIsDeletingFiles()) {
            $this->tasks[] = CleanupStagingFilesTask::class;
        }

        $this->tasks[] = FinishStagingSiteDeleteTask::class;
    }
}
