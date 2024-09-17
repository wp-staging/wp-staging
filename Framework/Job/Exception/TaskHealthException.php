<?php

namespace WPStaging\Framework\Job\Exception;

use WPStaging\Framework\Exceptions\WPStagingException;

class TaskHealthException extends WPStagingException
{
    const CODE_TASK_FAILED_TOO_MANY_TIMES = 200;

    public static function retryingTask($retries, $maxRetries)
    {
        return new self(sprintf(__('PHP failed to process this task. We will lower the memory usage and try again... (%d/%d)', 'wp-staging'), $retries, $maxRetries), 100);
    }

    public static function taskFailedTooManyTimes()
    {
        return new self(__('Sorry, a task failed too many times and the process cannot proceed.', 'wp-staging'), self::CODE_TASK_FAILED_TOO_MANY_TIMES);
    }
}
