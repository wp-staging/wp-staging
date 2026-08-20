<?php

namespace WPStaging\Framework\Job\Task\Tasks;

use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Task\AbstractTask;

class FinishCancelTask extends AbstractTask
{



    public static function getTaskName()
    {
        return "finish_cancel_task";
    }




    public static function getTaskTitle()
    {
        return esc_html__('Finishing...', 'wp-staging');
    }




    public function execute()
    {
        $this->getJobTransientCache()->completeJob();
        $this->stepsDto->finish();
        return $this->generateResponse(false);
    }
}
