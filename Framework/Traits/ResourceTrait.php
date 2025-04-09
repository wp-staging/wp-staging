<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;

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

    // Set lower maximum execution time for backup restore to avoid 504 errors in large database
    public static $backupRestoreMaxExecutionTimeInSeconds = 10;

    /**
     * Let start with a lower value we can try increasing it later when neeeded
     * @var int
     */
    public static $fileAppendMaxExecutionTimeInSeconds = 10;

    /** @var bool Whether this request is taking place in the context of a unit test. */
    protected $isUnitTest;

    /** @var bool If it is a unit test, whether to allow resource checks. */
    protected $allowResourceCheckOnUnitTests;

    /**
     * @return bool
     */
    public function isThreshold()
    {
        if ($this->isUnitTest() && !Hooks::applyFilters('wpstg.tests.resources.allow_check', $this->allowResourceCheckOnUnitTests)) {
            return false;
        }

        $isMemoryLimit = $this->isMemoryLimit();
        $isTimeLimit   = $this->isTimeLimit();

        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            if ($isTimeLimit || $isMemoryLimit) {
                \WPStaging\functions\debug_log('isThreshold: ' . wp_json_encode(['class' => __CLASS__, 'isTimeLimit' => $isTimeLimit, 'isMemoryLimit' => $isMemoryLimit], JSON_UNESCAPED_SLASHES));
            }
        }

        if ($isMemoryLimit) {
            return true;
        }

        if ($isTimeLimit) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isFileAppendThreshold()
    {
        if ($this->isUnitTest() && !Hooks::applyFilters('wpstg.tests.resources.allow_check', $this->allowResourceCheckOnUnitTests)) {
            return false;
        }

        return $this->isMemoryLimit() || $this->isFileAppendTimeLimit();
    }

    /**
     * @return bool
     */
    public function isDatabaseRestoreThreshold()
    {
        return $this->isMemoryLimit() || $this->isDatabaseRestoreTimeLimit();
    }

    /**
     * @return bool
     */
    public function isMaxExecutionThreshold()
    {
        return $this->isMemoryLimit() || $this->isMaxExecutionTimeoutLimit();
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

    /**
     * @return bool
     */
    public function isDatabaseRestoreTimeLimit()
    {
        $timeLimit = (int)Hooks::applyFilters('wpstg.resourceTrait.backupRestoreMaxExecutionTimeInSeconds', static::$backupRestoreMaxExecutionTimeInSeconds);
        return $this->getRunningTime() > $timeLimit;
    }

    /**
     * @return bool
     */
    public function isFileAppendTimeLimit(): bool
    {
        $timeLimit = (int)Hooks::applyFilters('wpstg.resource.file_append_time_limit', static::$fileAppendMaxExecutionTimeInSeconds);
        return $this->getRunningTime() > $timeLimit;
    }

    /**
     * @return bool
     */
    public function isMaxExecutionTimeoutLimit()
    {
        return $this->getRunningTime() > $this->findExecutionTimeLimit(true);
    }

    /**
     * Returns the maximum allowed execution time limit.
     * The minimum needs to be 10seconds!
     * @see https://github.com/wp-staging/wp-staging-pro/pull/1492
     *
     * @param bool $useMaxTimeout
     *
     * @return float|int
     */
    public function findExecutionTimeLimit($useMaxTimeout = false)
    {
        // Early bail: Cache
        if (isset($this->executionTimeLimit)) {
            return $this->executionTimeLimit;
        }

        $phpMaxExecutionTime      = $this->getPhpMaxExecutionTime();
        $cpuBoundMaxExecutionTime = $this->getCpuBoundMaxExecutionTime();

        // Use the max execution time limit for CPU bound tasks like tables renaming
        if ($useMaxTimeout) {
            $this->executionTimeLimit = max(min($phpMaxExecutionTime - static::$executionTimeGapInSeconds, $phpMaxExecutionTime * 0.8), 10);

            // Internal Use only. Allow overwriting of the max execution time limit for testing database rename task.
            $this->executionTimeLimit = (int)Hooks::applyFilters('wpstg.tests.databaseRenameTaskExecutionTime', $this->executionTimeLimit);

            return $this->executionTimeLimit;
        }

        // TODO don't overwrite when CLI / SAPI and / or add setting to not overwrite for devs
        if (!$cpuBoundMaxExecutionTime || $cpuBoundMaxExecutionTime > static::$defaultMaxExecutionTimeInSeconds) {
            $cpuBoundMaxExecutionTime = static::$defaultMaxExecutionTimeInSeconds;
        }

        // Never go over PHP own execution time limit, if set.
        if ($phpMaxExecutionTime > 0) {
            $cpuBoundMaxExecutionTime = min($phpMaxExecutionTime, $cpuBoundMaxExecutionTime);
        }

        // Set a max of 30 seconds to avoid NGINX 504 timeouts that are beyond PHP's control, with a minimum of 10 seconds
        $this->executionTimeLimit = max(min($cpuBoundMaxExecutionTime - static::$executionTimeGapInSeconds, 30), 10);

        // Allow overwriting of the max execution time limit.
        // Important: Use a value lower than the actual PHP limit. (reduce it by 10seconds or more). Also adjust the nginx/php timeout limit
        $this->executionTimeLimit = (int)Hooks::applyFilters('wpstg.resources.executionTimeLimit', $this->executionTimeLimit);

        // Allow disabling of the execution time limit
        if ((bool)Hooks::applyFilters('wpstg.resources.ignoreTimeLimit', false)) {
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
     * Returns the current PHP memory limit in bytes.
     *
     * @return int
     */
    protected function getMaxMemoryLimit()
    {
        // Early bail: Cache
        if (isset($this->memoryLimit)) {
            return $this->memoryLimit;
        }

        $memoryLimit = wp_convert_hr_to_bytes(ini_get('memory_limit'));

        // No memory limit
        if ($memoryLimit == -1 || $memoryLimit < 0) {
            $memoryLimit = 256 * MB_IN_BYTES;
        }

        // Allow custom overwriting
        $this->memoryLimit = Hooks::applyFilters('wpstg.resources.memoryLimit', $memoryLimit);

        // Unexpected memory limit after filter and also make sure it is never below 64MB
        if (!is_int($this->memoryLimit) || $this->memoryLimit < (64 * MB_IN_BYTES)) {
            $this->memoryLimit = 64 * MB_IN_BYTES;
        }

        // Make sure it never exceeds 256MB
        $this->memoryLimit = (min($this->memoryLimit, 256 * MB_IN_BYTES));

        // Allow disabling the memory limit
        if ((bool)Hooks::applyFilters('wpstg.resources.ignoreMemoryLimit', false)) {
            $this->memoryLimit = PHP_INT_MAX;
        }

        return $this->memoryLimit;
    }

    /**
     * Returns the actual script memory limit.
     *
     * @return int|float The script memory limit, by definition less then the maximum memory limit.
     */
    protected function getScriptMemoryLimit()
    {
        // For testing purpose only
        $this->scriptMemoryLimit = Hooks::applyFilters('wpstg.tests.resources.script_memory_limit', $this->scriptMemoryLimit);

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
