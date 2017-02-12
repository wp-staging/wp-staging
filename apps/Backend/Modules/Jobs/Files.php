<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

/**
 * Class Files
 * @package WPStaging\Backend\Modules\Jobs
 */
class Files extends Job
{

    /**
     * Start Module
     * @return mixed
     */
    public function start()
    {
        // TODO: Implement start() method.

        // TODO: check if we can use EXEC or not
        // TODO: if we can use exec; WIN: exec("copy {$sourceFile} {$targetFile}"), LIN: exec("cp {$sourceFile} {$targetFile}")
    }

    /**
     * Check OS
     * @return string
     */
    private function checkOS()
    {
        return strtoupper(substr(PHP_OS, 0, 3)); // WIN, LIN
    }

    /**
     * Checks whether we can use exec() or not
     * @return bool
     */
    private function canUseExec()
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