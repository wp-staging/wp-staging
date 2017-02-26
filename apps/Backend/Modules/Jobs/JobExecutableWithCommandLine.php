<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

/**
 * Class JobExecutableWithCommandLine
 * I'm sorry for such mess, we need to support PHP 5.3
 * @package WPStaging\Backend\Modules\Job
 */
abstract class JobExecutableWithCommandLine extends Job
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
     * @var array
     */
    protected $response = array(
        "status"        => false,
        "percentage"    => 0,
        "total"         => 0,
        "step"          => 0
    );

    /**
     * JobWithCommandLine constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->OS           = $this->getOS();
        $this->canUseExec   = $this->canUse("exec");
        $this->canUsePopen  = $this->canUsePopen();

        // Calculate total steps
        $this->calculateTotalSteps();
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

    /**
     * Prepare Response Array
     * @param bool $status
     * @param bool $incrementCurrentStep
     * @return array
     */
    protected function prepareResponse($status = false, $incrementCurrentStep = true)
    {
        if ($incrementCurrentStep)
        {
            $this->options->currentStep++;
        }

        $percentage = round(($this->options->currentStep / $this->options->totalSteps) * 100);
        $percentage = (100 < $percentage) ? 100 : $percentage;

        return $this->response = array(
            "status"        => $status,
            "percentage"    => $percentage,
            "total"         => $this->options->totalSteps,
            "step"          => $this->options->currentStep
        );
    }

    /**
     * Run Steps
     */
    protected function run()
    {
        // Execute steps
        for ($i = 0; $i < $this->options->totalSteps; $i++)
        {
            // Job is finished or over threshold limits was hit
            if (!$this->execute())
            {
                break;
            }
        }
    }

    /**
     * Start Module
     * @return object
     */
    public function start()
    {
        // Execute steps
        $this->run();

        // Save option, progress
        $this->saveOptions();

        return (object) $this->response;
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    abstract protected function calculateTotalSteps();

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     */
    abstract protected function execute();
}