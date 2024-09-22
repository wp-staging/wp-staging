<?php

namespace WPStaging\Framework\Traits;

/**
 * Provide a wrapper method for debug_log function to not log message when the function is not available
 * Useful in standalone tool
 * Trait DebugLogTrait
 * @package WPStaging\Framework\Traits
 */
trait DebugLogTrait
{
    /**
     * @param string $message
     * @param string $type
     * @param bool $addInErrorLog
     * @return void
     */
    protected function debugLog(string $message, string $type = 'info', bool $addInErrorLog = false)
    {
        if (function_exists('\WPStaging\functions\debug_log')) {
            \WPStaging\functions\debug_log($message, $type, $addInErrorLog);
        }
    }
}
