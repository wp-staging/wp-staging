<?php

namespace WPStaging\Framework\Traits;

trait TimerTrait
{
    /** @var float */
    protected $startTime;

    protected function initiateStartTime()
    {
        $this->startTime = microtime(true);
    }

    /**
     * @return float
     */
    protected function getRunningTime()
    {
        if ($this->startTime === null) {
            throw new \LogicException(
                sprintf(
                    'You must call the "%s::initiateStartTime" method before trying to get the current run time.',
                    __TRAIT__
                )
            );
        }
        return microtime(true) - $this->startTime;
    }
}
