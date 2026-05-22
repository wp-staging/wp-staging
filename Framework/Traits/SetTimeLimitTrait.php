<?php

namespace WPStaging\Framework\Traits;

/**
 * set_time_limit() helper that respects disable_functions, used on hot paths to
 * avoid the cost of resolving ServerVars through the DI container.
 */
trait SetTimeLimitTrait
{
    /**
     * @param int $seconds 0 = no limit (where the host allows it).
     */
    public function setTimeLimit(int $seconds = 0)
    {
        // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
        $disabled = explode(',', (string)ini_get('disable_functions'));
        $disabled = array_map('trim', $disabled);

        if (!in_array('set_time_limit', $disabled, true)) {
            set_time_limit($seconds);
        }
    }
}
