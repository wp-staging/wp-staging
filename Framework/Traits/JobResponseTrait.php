<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Job\Exception\ProcessLockedException;





trait JobResponseTrait
{










    protected function sendJobResponse(AbstractJob $job)
    {
        try {
            $response = $job->prepareAndExecute();
        } catch (ProcessLockedException $e) {
            wp_send_json_error($e->getMessage(), $e->getCode());

            return;
        }

        wp_send_json($response);
    }
}
