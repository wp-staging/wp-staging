<?php

namespace WPStaging\Framework\Job;

use WPStaging\Framework\Job\AbstractJob;

/**
 * This class is used to get Lazy initialized Job which can be dynamically changed by dependency injection for Pro or Basic Version
 */
abstract class JobProvider
{
    /** @var AbstractJob */
    private $job;

    public function __construct(AbstractJob $job)
    {
        $this->job = $job;
    }

    /**
     * @return AbstractJob
     */
    public function getJob()
    {
        return $this->job;
    }
}
