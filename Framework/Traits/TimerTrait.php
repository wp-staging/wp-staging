<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Core\WPStaging;

trait TimerTrait
{
    /**
     * @return float
     */
    protected function getRunningTime()
    {
        return microtime(true) - WPStaging::getInstance()->getStartTime();
    }
}
