<?php

namespace WPStaging\Framework\Job\Exception;

use RuntimeException;




class JobDataLostException extends RuntimeException
{
    public static function jobDataLost(): self
    {
        return new self(__('The saved progress of this process was lost before it could finish, so it cannot continue. Please start it again.', 'wp-staging'));
    }
}
