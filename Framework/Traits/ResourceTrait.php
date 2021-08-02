<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Core\WPStaging;

trait ResourceTrait
{
    /** @var int|null */
    protected $timeLimit;

    protected $resourceTraitSettings;
    protected $executionTimeLimit;
    protected $memoryLimit;
    protected $scriptMemoryLimit;

    public static $defaultMaxExecutionTimeInSeconds = 30;
    public static $executionTimeGapInSeconds        = 5;

    /** @var bool Whether this request is taking place in the context of a unit test. */
    protected $isUnitTest;

    /** @var bool If it is a unit test, whether to allow resource checks. */
    protected $allowResourceCheckOnUnitTests;

    /**
     * @return bool
     */
    public function isThreshold()
    {
        if ($this->isUnitTest() && !$this->allowResourceCheckOnUnitTests) {
            return false;
        }

        $isMemoryLimit = $this->isMemoryLimit();
        $isTimeLimit = $this->isTimeLimit();

        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            if ($isTimeLimit || $isMemoryLimit) {
                error_log(wp_json_encode(['class', __CLASS__, 'isTimeLimit' => $isTimeLimit, 'isMemoryLimit' => $isMemoryLimit]));
            }
        }

        return $isMemoryLimit || $isTimeLimit;
    }

    /**
     * @see \Codeception\Module\WPLoader::_getConstants
     *
     * @return bool Whether this request is in the context of a unit test.
     */
    protected function isUnitTest()
    {
        if (isset($this->isUnitTest)) {
            return $this->isUnitTest;
        }

        $this->isUnitTest = defined('WPCEPT_ISOLATED_INSTALL');

        return $this->isUnitTest;
    }

    /**
     * @return float
     */
    protected function getRunningTime()
    {
        return microtime(true) - WPStaging::$startTime;
    }

    /**
     * @return bool
     */
    public function isMemoryLimit()
    {
        return $this->getScriptMemoryLimit() <= $this->getMemoryUsage();
    }

    /**
     * @return bool
     */
    public function isTimeLimit()
    {
        $timeLimit = $this->findExecutionTimeLimit();

        if (isset($this->timeLimit)) {
            $timeLimit = $this->timeLimit;
        }

        return $this->getRunningTime() > $timeLimit;
    }
    // TODO Recursion for xDebug? Recursion is bad idea will cause more resource usage, need to avoid it.

    /**
     * @return float|int
     */
    public function findExecutionTimeLimit()
    {
        // Early bail: Cache
        if (isset($this->executionTimeLimit)) {
            return $this->executionTimeLimit;
        }

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

        // Set a max of 30 seconds to avoid NGINX 504 timeouts that are beyond PHP's control, with a minimum of 5 seconds
        $this->executionTimeLimit = max(min($cpuBoundMaxExecutionTime - static::$executionTimeGapInSeconds, 30), 5);

        if ((bool)apply_filters('wpstg.resources.ignoreTimeLimit', false)) {
            $this->executionTimeLimit = PHP_INT_MAX;
        }

        return $this->executionTimeLimit;
    }

    /**
     * @param bool $realUsage
     *
     * @return int
     */
    protected function getMemoryUsage($realUsage = true)
    {
        return memory_get_usage($realUsage);
    }

    /**
     * @param bool $realUsage
     *
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
        if (!isset($this->timeLimit)) {
            $this->timeLimit = $this->findExecutionTimeLimit();
        }

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
     * @param bool $isAllowed True to check resources on unit tests. False to not check.
     */
    public function resourceCheckOnUnitTests($isAllowed)
    {
        $this->allowResourceCheckOnUnitTests = $isAllowed;
    }

    /**
     * Returns the current PHP memory limit in bytes..
     *
     * @return int The current memory limit in bytes.
     */
    protected function getMaxMemoryLimit()
    {
        // Early bail: Cache
        if (isset($this->memoryLimit)) {
            return $this->memoryLimit;
        }

        $limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));

        // No memory limit
        if ($limit == -1) {
            // 512MB
            $limit = 512 * 1000000;
        } else {
            // Unexpected memory limit
            if (!is_int($limit) || $limit < 64000000) {
                // 64MB
                $limit = 64000000;
            }
        }

        if ((bool)apply_filters('wpstg.resources.ignoreMemoryLimit', false)) {
            $limit = PHP_INT_MAX;
        }

        $this->memoryLimit = $limit;

        return $this->memoryLimit;
    }

    /**
     * Returns the actual script memory limit.
     *
     * @return int The script memory limit, by definition less then
     *             the maximum memory limit.
     */
    protected function getScriptMemoryLimit()
    {
        // Early bail: Cache
        if (isset($this->scriptMemoryLimit)) {
            return $this->scriptMemoryLimit;
        }

        // 80% of max memory limit
        return $this->scriptMemoryLimit = $this->getMaxMemoryLimit() * 0.8;
    }

    /**
     * Returns the max execution time value as bound by the "CPU Load" setting.
     *
     * @param string|null $cpuLoadSetting Either a specific CPU Load setting to
     *                                    return the max execution time for, or
     *                                    `null` to read the current CPU Load
     *                                    value from the Settings.
     *
     * @return int The max execution time as bound by the CPU Load setting.
     */
    protected function getCpuBoundMaxExecutionTime($cpuLoadSetting = null)
    {
        // Early bail: Cache
        if (!isset($this->resourceTraitSettings)) {
            $this->resourceTraitSettings = json_decode(json_encode(get_option('wpstg_settings', [])));
        }
        if ($cpuLoadSetting === null) {
            $cpuLoadSetting = isset($this->resourceTraitSettings->cpuLoad) ? $this->resourceTraitSettings->cpuLoad : 'medium';
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
