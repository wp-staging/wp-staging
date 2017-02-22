<?php
namespace WPStaging\Backend\Modules\Jobs;

/**
 * Class Cloning
 * @package WPStaging\Backend\Modules\Jobs
 */
class Cloning extends Job
{

    const EXECUTION_TIME_RATIO = 0.8;

    const MAX_MEMORY_RATIO = 0.8;

    /**
     * @var int
     */
    private $maxMemoryLimit;

    /**
     * @var int
     */
    private $maxExecutionTime;

    /**
     * @var int
     */
    private $memoryLimit;

    /**
     * @var int
     */
    private $executionLimit;

    /**
     * Initialization
     */
    protected function initialize()
    {
        // Get max limits
        $this->maxMemoryLimit   = $this->getMemoryInBytes(@ini_get("memory_limit"));
        $this->maxExecutionTime = (int) ini_get("max_execution_time");

        // Calculate threshold limits
        $this->memoryLimit      = $this->maxMemoryLimit * self::MAX_MEMORY_RATIO;
        $this->executionLimit   = $this->maxExecutionTime * self::EXECUTION_TIME_RATIO;
    }

    /**
     * Start the cloning job
     */
    public function start()
    {
        // TODO: Implement start() method.
    }

    /**
     * @param string $memory
     * @return int
     */
    private function getMemoryInBytes($memory)
    {
        // Handle unlimited ones
        if (1 > (int) $memory)
        {
            return (int) $memory;
        }

        $bytes  = (int) $memory; // grab only the number
        $size   = trim(str_replace($bytes, null, strtolower($memory))); // strip away number and lower-case it

        // Actual calculation
        switch($size)
        {
            case 'k':
                $bytes *= 1024;
                break;
            case 'm':
                $bytes *= (1024 * 1024);
                break;
            case 'g':
                $bytes *= (1024 * 1024 * 1024);
                break;
        }

        return $bytes;
    }

    /**
     * Format bytes into ini_set favorable form
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        if ((int) $bytes < 1)
        {
            return '';
        }

        $units  = array('B', 'K', 'M', 'G'); // G since PHP 5.1.x so we are good!

        $bytes  = (int) $bytes;
        $base   = log($bytes) / log(1000);
        $pow    = pow(1000, $base - floor($base));

        return round($pow, 0) . $units[(int) floor($base)];
    }

    /**
     * Get current time in seconds
     * @return float
     */
    private function time()
    {
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        return round($time, 4);
    }

    /**
     * @return bool
     */
    private function isOverThreshold()
    {
        // Check if the memory is over threshold
        $usedMemory = (int) @memory_get_usage(true);

        if ($usedMemory >= $this->memoryLimit)
        {
            return (!$this->resetMemory());
        }

        // Check if execution time is over threshold
        if ($this->time() >= $this->executionLimit)
        {
            return (!$this->resetTime());
        }

        return false;
    }

    /**
     * Attempt to reset memory
     * @return bool
     */
    private function resetMemory()
    {
        $newMemoryLimit = $this->maxMemoryLimit * 2;

        // Failed to set
        if (false === ini_set("memory_limit", $this->formatBytes($newMemoryLimit)))
        {
            return false;
        }

        // Double checking
        $newMemoryLimit = $this->getMemoryInBytes(@ini_get("memory_limit"));
        if ($newMemoryLimit <= $this->maxMemoryLimit)
        {
            return false;
        }

        // Set the new Maximum memory limit
        $this->maxMemoryLimit   = $newMemoryLimit;

        // Calculate threshold limit
        $this->memoryLimit      = $newMemoryLimit * self::MAX_MEMORY_RATIO;

        return true;
    }

    /**
     * Attempt to reset time
     * @return bool
     */
    private function resetTime()
    {
        // Attempt to reset timeout
        if (!@set_time_limit(300))
        {
            return false;
        }

        // Increase execution limit
        $this->executionLimit = $this->executionLimit * 2;

        return true;
    }

    /**
     * Reset time limit and memory
     * @return bool
     */
    private function reset()
    {
        // Attempt to reset time
        if (!$this->resetTime())
        {
            return false;
        }

        // Attempt to reset memory
        if (!$this->resetMemory())
        {
            return false;
        }

        return true;
    }
}