<?php

namespace WPStaging\Framework\Job\Exception;

use WPStaging\Framework\Exceptions\WPStagingException;

class ThresholdException extends WPStagingException
{
    public static function thresholdHit($message = '')
    {
        return new self($message, 100);
    }
}
