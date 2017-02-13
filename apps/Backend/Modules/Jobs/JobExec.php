<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

use WPStaging\Backend\Modules\Jobs\Interfaces\JobInterface;
use WPStaging\WPStaging;
use WPStaging\Utils\Cache;

/**
 * Class JobExec
 * @package WPStaging\Backend\Modules\Jobs
 */
abstract class JobExec extends Job
{
    /**
     * Check OS
     * @return string
     */
    protected function checkOS()
    {
        return strtoupper(substr(PHP_OS, 0, 3)); // WIN, LIN
    }

    /**
     * Checks whether we can use exec() or not
     * @return bool
     */
    protected function canUseExec()
    {
        // Exec doesn't exist
        if (!function_exists("exec"))
        {
            return false;
        }

        // Check if it is disabled from INI
        $disabledFunctions = explode(',', ini_get("disable_functions"));

        return (!in_array("exec", $disabledFunctions));
    }
}