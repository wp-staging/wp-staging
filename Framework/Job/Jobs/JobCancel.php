<?php

namespace WPStaging\Framework\Job\Jobs;

use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Job\Task\Tasks\CleanupTmpFilesTask;
use WPStaging\Framework\Job\Task\Tasks\CleanupTmpTablesTask;

class JobCancel extends AbstractJob
{
    /** @var array The array of tasks to execute for this job. Populated at init(). */
    private $tasks = [];

    /**
     * @var bool
     */
    protected $isCancelJob = true;

    public static function getJobName()
    {
        return 'cancel';
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
        $this->tasks[] = CleanupTmpFilesTask::class;
        $this->tasks[] = CleanupTmpTablesTask::class;
    }
}
