<?php

namespace WPStaging\Framework\Utils;

class ServerVars
{
    /**
     * @param $int int
     * @return void
     */
    public function setTimeLimit($int = 0)
    {
        // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
        if (!in_array("set_time_limit", explode(',', ini_get("disable_functions")))) {
            set_time_limit($int);
        }
    }
}
