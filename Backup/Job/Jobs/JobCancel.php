<?php

namespace WPStaging\Backup\Job\Jobs;

use WPStaging\Backup\Task\Tasks\CleanupTmpFilesTask;
use WPStaging\Backup\Task\Tasks\CleanupTmpTablesTask;
use WPStaging\Framework\Job\AbstractJob;

class JobCancel extends AbstractJob
{
    /** @var array The array of tasks to execute for this job. Populated at init(). */
    private $tasks = [];

    public static function getJobName()
    {
        return 'backup_cancel';
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
            $this->currentTask->getLogger()->critical($e->getMessage());
            $response = $this->getResponse($this->currentTask->generateResponse(false));
        }

        //$this->finishBenchmark(get_class($this->currentTask));

        return $response;
    }

    protected function init()
    {
        $this->tasks[] = CleanupTmpFilesTask::class;
        $this->tasks[] = CleanupTmpTablesTask::class;
    }
}
