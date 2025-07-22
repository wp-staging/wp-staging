<?php

namespace WPStaging\Framework\Job\Exception;

use WPStaging\Framework\Exceptions\WPStagingException;

class ProcessLockedException extends WPStagingException
{
    public static function processAlreadyLocked()
    {
        return new self(sprintf(esc_html__('A backup or restore process is already running.%sPlease wait for it to complete before starting a new one.%sIf this message keeps appearing, %s.', 'wp-staging'), '<br>', '<br><br>', '<a href="https://wp-staging.com/support/" target="_blank">contact support</a>'), 423);
    }
}
