<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}


/**
 * Class JobExecutable
 * I'm sorry for such mess, we need to support PHP 5.3
 * @package WPStaging\Backend\Modules\Jobs
 */
abstract class JobExecutable extends Job
{

    /**
     * @var array
     */
    protected $response = [
        "status"        => false,
        "percentage"    => 0,
        "total"         => 0,
        "step"          => 0,
        "last_msg"      => '',
    ];

    /**
     * JobExecutable constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // Calculate total steps
        $this->calculateTotalSteps();

        // Set server settings (Do not set this globally. HS Ticket #9061)
        wpstg_setup_environment();
    }

    /**
     * Prepare Response Array
     * @param bool $status false if not finished
     * @param bool $incrementCurrentStep
     * @return array
     */
    protected function prepareResponse($status = false, $incrementCurrentStep = true)
    {
        if ($incrementCurrentStep)
        {
            $this->options->currentStep++;
        }

        $percentage = 0;
        if (isset($this->options->currentStep) && isset($this->options->totalSteps) && $this->options->totalSteps > 0){
            $percentage = round(($this->options->currentStep / $this->options->totalSteps) * 100);
            $percentage = ($percentage > 100) ? 100 : $percentage;
        }

        return $this->response = [
            "status"        => $status,
            "percentage"    => $percentage,
            "total"         => $this->options->totalSteps,
            "step"          => $this->options->currentStep,
            "job"           => $this->options->currentJob,
            "last_msg"      => $this->logger->getLastLogMsg(),
            "running_time"  => $this->time() - time(),
            "job_done"      => $status
        ];
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
            // Return after every step to create lower batches
            // This also gets a smoother progress bar and to a less consumptive php cpu load
            // This decrease performance tremendous but also lowers memory consumption
            if ($this->settings->cpuLoad === 'low'){
               return (object) $this->response;
            }
        }
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