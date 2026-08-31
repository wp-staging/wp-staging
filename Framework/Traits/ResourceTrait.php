<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\JobDataDto;

trait ResourceTrait
{
 
    protected $timeLimit;

    protected $resourceTraitSettings;
    protected $executionTimeLimit;
    protected $memoryLimit;
    protected $scriptMemoryLimit;

    public static $defaultMaxExecutionTimeInSeconds = 30;
    public static $executionTimeGapInSeconds        = 5;

 
    public static $backupRestoreMaxExecutionTimeInSeconds = 10;

 
    public static $waitTaskMaxExecutionTimeInSeconds = 5;





    public static $fileAppendMaxExecutionTimeInSeconds = 10;

 
    protected $isUnitTest;

 
    protected $allowResourceCheckOnUnitTests;


 
    protected $extractionLimit;




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




    public function isFileAppendThreshold()
    {
        if ($this->isUnitTest() && !Hooks::applyFilters('wpstg.tests.resources.allow_check', $this->allowResourceCheckOnUnitTests)) {
            return false;
        }

        return $this->isMemoryLimit() || $this->isFileAppendTimeLimit();
    }




    public function isDatabaseRestoreThreshold()
    {
        return $this->isMemoryLimit() || $this->isDatabaseRestoreTimeLimit();
    }




    public function isWaitTaskThreshold()
    {
        return $this->isMemoryLimit() || $this->isWaitTaskTimeLimit();
    }




    public function isMaxExecutionThreshold()
    {
        return $this->isMemoryLimit() || $this->isMaxExecutionTimeoutLimit();
    }






    protected function isUnitTest()
    {
        if (isset($this->isUnitTest)) {
            return $this->isUnitTest;
        }

        $this->isUnitTest = defined('WPCEPT_ISOLATED_INSTALL');

        return $this->isUnitTest;
    }




    protected function getRunningTime()
    {
        return microtime(true) - WPStaging::$startTime;
    }




    public function isMemoryLimit()
    {
        return $this->getScriptMemoryLimit() <= $this->getMemoryUsage();
    }




    public function isTimeLimit()
    {
        $timeLimit = $this->findExecutionTimeLimit();

        if (isset($this->timeLimit)) {
            $timeLimit = $this->timeLimit;
        }

        return $this->getRunningTime() > $timeLimit;
    }




    public function isDatabaseRestoreTimeLimit()
    {
        $timeLimit = (int)Hooks::applyFilters(JobDataDto::FILTER_RESOURCES_BACKUP_RESTORE_MAX_EXECUTION_TIME_IN_SECONDS, static::$backupRestoreMaxExecutionTimeInSeconds);
        return $this->getRunningTime() > $timeLimit;
    }




    public function isFileAppendTimeLimit(): bool
    {
        $timeLimit = (int)Hooks::applyFilters(JobDataDto::FILTER_RESOURCES_FILE_APPEND_TIME_LIMIT, static::$fileAppendMaxExecutionTimeInSeconds);
        return $this->getRunningTime() > $timeLimit;
    }




    public function isWaitTaskTimeLimit(): bool
    {
        $phpTimeLimit = $this->findExecutionTimeLimit();
        $timeLimit    = min($phpTimeLimit, self::$waitTaskMaxExecutionTimeInSeconds);

        return $this->getRunningTime() > $timeLimit;
    }




    public function isMaxExecutionTimeoutLimit()
    {
        return $this->getRunningTime() > $this->findExecutionTimeLimit(true);
    }










    public function findExecutionTimeLimit($useMaxTimeout = false)
    {
 
        if (isset($this->executionTimeLimit)) {
            return $this->executionTimeLimit;
        }

        $phpMaxExecutionTime      = $this->getPhpMaxExecutionTime();
        $cpuBoundMaxExecutionTime = $this->getCpuBoundMaxExecutionTime();

 
        if ($useMaxTimeout) {
            $this->executionTimeLimit = max(min($phpMaxExecutionTime - static::$executionTimeGapInSeconds, $phpMaxExecutionTime * 0.8), 10);

 
            $this->executionTimeLimit = (int)Hooks::applyFilters('wpstg.tests.databaseRenameTaskExecutionTime', $this->executionTimeLimit);

            return $this->executionTimeLimit;
        }

 
        if (!$cpuBoundMaxExecutionTime || $cpuBoundMaxExecutionTime > static::$defaultMaxExecutionTimeInSeconds) {
            $cpuBoundMaxExecutionTime = static::$defaultMaxExecutionTimeInSeconds;
        }

 
        if ($phpMaxExecutionTime > 0) {
            $cpuBoundMaxExecutionTime = min($phpMaxExecutionTime, $cpuBoundMaxExecutionTime);
        }

 
        $this->executionTimeLimit = max(min($cpuBoundMaxExecutionTime - static::$executionTimeGapInSeconds, 30), 10);

 
 
        $this->executionTimeLimit = (int)Hooks::applyFilters(JobDataDto::FILTER_RESOURCES_EXECUTION_TIME_LIMIT, $this->executionTimeLimit);

 
        if ((bool)Hooks::applyFilters(JobDataDto::FILTER_RESOURCES_IGNORE_TIME_LIMIT, false)) {
            $this->executionTimeLimit = PHP_INT_MAX;
        }

        return $this->executionTimeLimit;
    }

    public function isWithinMemoryExtractionLimit(int $fileSize): bool
    {
        return $fileSize <= $this->getInMemoryExtractionLimit();
    }











    public function getInMemoryExtractionLimit(): int
    {
 
        if ($this->extractionLimit !== null) {
            return $this->extractionLimit;
        }

        $memoryLimit = $this->getMaxMemoryLimit();
        $limit       = $this->calculateInMemoryExtractionLimit($memoryLimit);






        $this->extractionLimit = (int)Hooks::applyFilters(JobDataDto::FILTER_BACKUP_INMEMORY_EXTRACTION_LIMIT, $limit, $memoryLimit);

 
        $this->extractionLimit = max(524288, min($this->extractionLimit, (16 * MB_IN_BYTES)));

        return $this->extractionLimit;
    }






    protected function getMemoryUsage($realUsage = true)
    {
        return memory_get_usage($realUsage);
    }






    protected function getMemoryPeakUsage($realUsage = true)
    {
        return memory_get_peak_usage($realUsage);
    }




    public function getTimeLimit()
    {
        if (!isset($this->timeLimit)) {
            $this->timeLimit = $this->findExecutionTimeLimit();
        }

        return $this->timeLimit;
    }




    public function setTimeLimit($timeLimit)
    {
        $this->timeLimit = $timeLimit;
    }




    public function resourceCheckOnUnitTests($isAllowed)
    {
        $this->allowResourceCheckOnUnitTests = $isAllowed;
    }






    protected function getMaxMemoryLimit()
    {
 
        if (isset($this->memoryLimit)) {
            return $this->memoryLimit;
        }

        $memoryLimit = wp_convert_hr_to_bytes(ini_get('memory_limit'));

 
        if ($memoryLimit == -1 || $memoryLimit < 0) {
            $memoryLimit = 256 * MB_IN_BYTES;
        }

 
        $this->memoryLimit = Hooks::applyFilters(JobDataDto::FILTER_RESOURCES_MEMORY_LIMIT, $memoryLimit);

 
        if (!is_int($this->memoryLimit) || $this->memoryLimit < (64 * MB_IN_BYTES)) {
            $this->memoryLimit = 64 * MB_IN_BYTES;
        }

 
        $this->memoryLimit = (min($this->memoryLimit, 256 * MB_IN_BYTES));

 
        if ((bool)Hooks::applyFilters(JobDataDto::FILTER_RESOURCES_IGNORE_MEMORY_LIMIT, false)) {
            $this->memoryLimit = PHP_INT_MAX;
        }

        return $this->memoryLimit;
    }






    protected function getScriptMemoryLimit()
    {
 
        $this->scriptMemoryLimit = Hooks::applyFilters('wpstg.tests.resources.script_memory_limit', $this->scriptMemoryLimit);

 
        if (isset($this->scriptMemoryLimit)) {
            return $this->scriptMemoryLimit;
        }

 
        return $this->scriptMemoryLimit = $this->getMaxMemoryLimit() * 0.8;
    }











    protected function getCpuBoundMaxExecutionTime($cpuLoadSetting = null)
    {
 
        if (!isset($this->resourceTraitSettings)) {
            $this->resourceTraitSettings = (object)get_option('wpstg_settings', []);
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







    private function getPhpMaxExecutionTime()
    {
        return (int)ini_get('max_execution_time');
    }

    private function calculateInMemoryExtractionLimit(int $memoryLimit): int
    {
        if ($memoryLimit <= (256 * MB_IN_BYTES)) {
            return MB_IN_BYTES;
        }

        if ($memoryLimit <= (512 * MB_IN_BYTES)) {
            return 2 * MB_IN_BYTES;
        }

        if ($memoryLimit <= (1024 * MB_IN_BYTES)) {
            return 4 * MB_IN_BYTES;
        }

        return 8 * MB_IN_BYTES;
    }
}
