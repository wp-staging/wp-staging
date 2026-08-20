<?php

namespace WPStaging\Framework\Job;

use WPStaging\Framework\Job\AbstractJob;




abstract class JobProvider
{
 
    private $job;

    public function __construct(AbstractJob $job)
    {
        $this->job = $job;
    }




    public function getJob()
    {
        return $this->job;
    }
}
