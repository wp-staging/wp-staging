<?php

namespace WPStaging\Framework\Traits;





trait SetTimeLimitTrait
{



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
