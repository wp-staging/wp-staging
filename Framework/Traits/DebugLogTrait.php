<?php

namespace WPStaging\Framework\Traits;







trait DebugLogTrait
{






    protected function debugLog(string $message, string $type = 'info', bool $addInErrorLog = false)
    {
        if (function_exists('\WPStaging\functions\debug_log')) {
            \WPStaging\functions\debug_log($message, $type, $addInErrorLog);
        }
    }
}
