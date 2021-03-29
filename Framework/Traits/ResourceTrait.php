<?php

namespace WPStaging\Framework\Traits;

trait ResourceTrait
{
    use TimerTrait;

    /** @var int|null */
    protected $timeLimit;

    public static $defaultMaxExecutionTimeInSeconds = 30;
    public static $executionTimeGapInSeconds = 5;

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
        $ignoreMemoryLimit = (bool)apply_filters('wpstg.resources.ignoreMemoryLimit', false);

        if ($ignoreMemoryLimit) {
            return false;
        }

        $allowed = $this->getScriptMemoryLimit();

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
        $phpMaxExecutionTime = $this->getPhpMaxExecutionTime();
        $cpuBoundMaxExecutionTime = $this->getCpuBoundMaxExecutionTime();

        // TODO don't overwrite when CLI / SAPI and / or add setting to not overwrite for devs
        if (!$cpuBoundMaxExecutionTime || $cpuBoundMaxExecutionTime > static::$defaultMaxExecutionTimeInSeconds) {
            $cpuBoundMaxExecutionTime = static::$defaultMaxExecutionTimeInSeconds;
        }

        if ($phpMaxExecutionTime > 0) {
            // Never go over PHP own execution time limit, if set.
            $cpuBoundMaxExecutionTime = min($phpMaxExecutionTime, $cpuBoundMaxExecutionTime);
        }

        return $cpuBoundMaxExecutionTime - static::$executionTimeGapInSeconds;
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

    /**
     * Returns the current PHP memory limit in bytes..
     *
     * @return int The current memory limit in bytes.
     */
    private function getMaxMemoryLimit()
    {
        $limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));

        if (!is_int($limit) || $limit < 64000000) {
            $limit = 64000000;
        }

        return $limit;
    }

    /**
     * Returns the actual script memory limit.
     *
     * @return int The script memory limit, by definition less then
     *             the maximum memory limit.
     */
    private function getScriptMemoryLimit()
    {
        $limit = $this->getMaxMemoryLimit();

        return $limit - 1024;
    }

    /**
     * Returns the max execution time value as bound by the "CPU Load" setting.
     *
     * @param string|null $cpuLoadSetting Either a specific CPU Load setting to
     *                                    return the max execution time for, or
     *                                    `null` to read the current CPU Load
     *                                    value from the Settings.
     * @return int The max execution time as bound by the CPU Load setting.
     */
    protected function getCpuBoundMaxExecutionTime($cpuLoadSetting = null)
    {
        $settings = json_decode(json_encode(get_option('wpstg_settings', [])));
        if ($cpuLoadSetting === null) {
            $cpuLoadSetting = isset($settings->cpuLoad) ? $settings->cpuLoad : 'medium';
        }
        $execution_gap = static::$executionTimeGapInSeconds;

        switch ($cpuLoadSetting) {
            case 'low':
                $cpuBoundMaxExecutionTime = 10 + $execution_gap;
                break;
            case 'medium':
            default:
                $cpuBoundMaxExecutionTime = 20 + $execution_gap;
                break;
            case 'high':
                $cpuBoundMaxExecutionTime = 25 + $execution_gap;
                break;
        }

        return $cpuBoundMaxExecutionTime;
    }

    /**
     * Returns the max execution time as set in PHP ini settings.
     *
     * @return int The PHP max execution time in seconds. Note that `0` and `-1` would
     *             both indicate there is no time limit set.
     */
    private function getPhpMaxExecutionTime()
    {
        return (int)ini_get('max_execution_time');
    }
}
