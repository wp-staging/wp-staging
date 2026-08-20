<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Traits\MemoryExhaustTrait;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Utils\ServerVars;

 
if (!defined("WPINC")) {
    die;
}






abstract class JobExecutable extends Job
{
    use MemoryExhaustTrait;




    protected $strUtil;




    protected $response = [
        "status"     => false,
        "percentage" => 0,
        "total"      => 0,
        "step"       => 0,
        "last_msg"   => '',
    ];




    public function __construct()
    {
        parent::__construct();

 
        $this->calculateTotalSteps();

 
 
        @ignore_user_abort(true);

 
        WPStaging::make(ServerVars::class)->setTimeLimit(0);

 
        @ini_set('max_input_time', '-1');

 
        @ini_set('pcre.backtrack_limit', (string)PHP_INT_MAX);

        $this->strUtil = WPStaging::make(Strings::class);
    }







    protected function prepareResponse($status = false, $incrementCurrentStep = true)
    {
        if ($incrementCurrentStep) {
            $this->options->currentStep++;
        }

        $percentage = 0;
        if (isset($this->options->currentStep) && isset($this->options->totalSteps) && $this->options->totalSteps > 0) {
            $percentage = round(($this->options->currentStep / $this->options->totalSteps) * 100);
            $percentage = ($percentage > 100) ? 100 : $percentage;
        }

        $this->removeMemoryExhaustErrorTmpFile();
        return $this->response = [
            "status"     => $status,
            "percentage" => $percentage,
            "total"      => $this->options->totalSteps,
            "step"       => $this->options->currentStep,
            "job"        => $this->options->currentJob,
            "last_msg"   => $this->logger->getLastLogMsg(),
            "job_done"   => $status,
        ];
    }





    public function start()
    {
 
        $this->run();

 
        $this->saveOptions();

        return (object) $this->response;
    }




    protected function run()
    {
 
        for ($i = 0; $i < $this->options->totalSteps; $i++) {
 
            if (!$this->execute()) {
                break;
            }

 
 
 
            if ($this->settings->cpuLoad === 'low') {
                return (object) $this->response;
            }
        }
    }





    abstract protected function calculateTotalSteps();






    abstract protected function execute();
}
