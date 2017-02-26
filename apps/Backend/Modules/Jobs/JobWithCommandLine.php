<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

/**
 * Class JobWithCommandLine
 * I'm sorry for such mess, we need to support PHP 5.3
 * @package WPStaging\Backend\Modules\Job
 */
abstract class JobWithCommandLine extends Job
{

    /**
     * @var bool
     */
    protected $canUseExec;

    /**
     * @var bool
     */
    protected $canUsePopen;

    /**
     * Operating System
     * @var string
     */
    protected $OS;

    /**
     * JobWithCommandLine constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->OS           = $this->getOS();
        $this->canUseExec   = $this->canUse("exec");
        $this->canUsePopen  = $this->canUsePopen();
    }

    /**
     * Get OS
     * @return string
     */
    protected function getOS()
    {
        return strtoupper(substr(PHP_OS, 0, 3)); // WIN, LIN..
    }

    /**
     * Checks whether we can use given function or not
     * @param string $functionName
     * @return bool
     */
    protected function canUse($functionName)
    {
        // Exec doesn't exist
        if (!function_exists($functionName))
        {
            return false;
        }

        // Check if it is disabled from INI
        $disabledFunctions = explode(',', ini_get("disable_functions"));

        return (!in_array($functionName, $disabledFunctions));
    }

    /**
     * Checks whether we can use popen() / \COM class (for WIN) or not
     * @return bool
     */
    protected function canUsePopen()
    {
        // Windows
        if ("WIN" === $this->OS)
        {
            return class_exists("\\COM");
        }

        // This should cover rest OS for servers
        return $this->canUse("popen");
    }
}