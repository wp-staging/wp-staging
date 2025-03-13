<?php

namespace WPStaging\Framework\Job\Exception;

use WPStaging\Framework\Exceptions\WPStagingException;

class ProcessLockedException extends WPStagingException
{
    public static function processAlreadyLocked()
    {
        return new self(sprintf(
            esc_html__('Another backup/restore is already running. Please wait a moment and try again. If you continue to see this error, please contact the %s.', 'wp-staging'),
            '<a href="https://wp-staging.com/support/" target="_blank">' . esc_html__('WP STAGING support', 'wp-staging') . '</a>'
        ), 423);
    }
}
