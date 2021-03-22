<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Utils\Size;

trait ResourceTrait
{
    use TimerTrait;

    /** @var int|null */
    protected $timeLimit;

    private static $default_max_execution_time_in_seconds = 30;
    private static $execution_time_gap_in_seconds = 5;

    /**
     * @return bool
     */
    public function isThreshold()
    {
        return $this->isMemoryLimit() || $this->isTimeLimit();
    }

    /**
     * @return bool
     */
    public function isMemoryLimit()
    {
        /**
         * Overriding this filter to return true allows someone to ignore memory limits.
         */
        $ignoreTimeLimit = (bool)apply_filters('wpstg.resources.ignoreMemoryLimit', false);

        if ($ignoreTimeLimit) {
            return false;
        }

        $limit = (new Size)->toBytes(ini_get('memory_limit'));

        if (!is_int($limit) || $limit < 64000000){
            $limit = 64000000;
        }
        $allowed = $limit - 1024;
        return $allowed <= $this->getMemoryUsage();
    }

    /**
     * @return bool
     */
    public function isTimeLimit()
    {
        /**
         * Overriding this filter to return true allows someone to ignore time limits.
         * Useful for developers using xdebug, for instance.
         */
        $ignoreTimeLimit = (bool)apply_filters('wpstg.resources.ignoreTimeLimit', false);

        if ($ignoreTimeLimit) {
            return false;
        }

        $timeLimit = $this->findExecutionTimeLimit();

        if ($this->timeLimit !== null) {
            $timeLimit = $this->timeLimit;
        }
        return $timeLimit <= $this->getRunningTime();
    }
    // TODO Recursion for xDebug? Recursion is bad idea will cause more resource usage, need to avoid it.

    /**
     * @return float|int
     */
    public function findExecutionTimeLimit()
    {
        $executionTime = (int) ini_get('max_execution_time');
        // TODO don't overwrite when CLI / SAPI and / or add setting to not overwrite for devs
        if (!$executionTime || $executionTime > static::$default_max_execution_time_in_seconds) {
            $executionTime = static::$default_max_execution_time_in_seconds;
        }
        return $executionTime - static::$execution_time_gap_in_seconds;
    }

    /**
     * @param bool $realUsage
     * @return int
     */
    protected function getMemoryUsage($realUsage = true)
    {
        return memory_get_usage($realUsage);
    }

    /**
     * @param bool $realUsage
     * @return int
     */
    protected function getMemoryPeakUsage($realUsage = true)
    {
        return memory_get_peak_usage($realUsage);
    }

    /**
     * @return int|null
     */
    public function getTimeLimit()
    {
        return $this->timeLimit;
    }

    /**
     * @param int|null $timeLimit
     */
    public function setTimeLimit($timeLimit)
    {
        $this->timeLimit = $timeLimit;
    }
}
