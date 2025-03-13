<?php

namespace WPStaging\Staging\Jobs;

use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Staging\Dto\Job\StagingSiteCreateDataDto;
use WPStaging\Staging\Tasks\StagingSite\CreateDatabaseTablesTask;
use WPStaging\Staging\Tasks\StagingSite\DatabaseRowsCopyTask;
use WPStaging\Staging\Tasks\StagingSiteCreate\CreateRequirementsCheckTask;
use WPStaging\Staging\Tasks\StagingSiteCreate\FinishStagingSiteCreateTask;

class StagingSiteCreate extends AbstractJob
{
    /** @var StagingSiteCreateDataDto $jobDataDto */
    protected $jobDataDto;

    /** @var array The array of tasks to execute for this job. Populated at init(). */
    private $tasks = [];

    public static function getJobName()
    {
        return 'staging_site_create';
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
        $this->tasks[] = CreateRequirementsCheckTask::class;
        $this->tasks[] = CreateDatabaseTablesTask::class;
        $this->tasks[] = DatabaseRowsCopyTask::class;
        $this->tasks[] = FinishStagingSiteCreateTask::class;
    }
}
