<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Entity\Settings;
use WPStaging\Repository\SettingsRepository;

trait TimerTrait
{
    /** @var float */
    protected $startTime;

    protected function initiateStartTime()
    {
        $time = explode(' ', microtime());
        $this->startTime = (float) $time[1] + (float) $time[0];
    }

    /**
     * @return float
     */
    protected function getRunningTime()
    {
        return time() - $this->startTime;
    }
}