<?php

namespace WPStaging\Framework\Job\Exception;

use WPStaging\Framework\Exceptions\WPStagingException;

class ProcessLockedException extends WPStagingException
{
    public static function processAlreadyLocked()
    {
        return new self('Another backup/restore is already running. Please wait a moment and try again. If you continue to see this error, please contact the %s. <a href="https://wp-staging.com/support/" target="_blank"> WP STAGING support </a>', 423);
    }
}
