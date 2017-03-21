<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

use WPStaging\Backend\Modules\Jobs\Interfaces\JobInterface;
use WPStaging\Utils\Logger;
use WPStaging\WPStaging;
use WPStaging\Utils\Cache;

/**
 * Class Job
 * @package WPStaging\Backend\Modules\Jobs
 */
abstract class Job implements JobInterface
{

    const EXECUTION_TIME_RATIO = 0.8;

    const MAX_MEMORY_RATIO = 0.8;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $hasLoggedFileNameSet = false;

    /**
     * @var object
     */
    protected $options;

    /**
     * @var object
     */
    protected $settings;

    /**
     * @var int
     */
    protected $maxMemoryLimit;

    /**
     * @var int
     */
    protected $maxExecutionTime;

    /**
     * @var int
     */
    protected $memoryLimit;

    /**
     * @var int
     */
    protected $executionLimit;

    /**
     * @var int
     */
    protected $start;

    /**
     * Job constructor.
     */
    public function __construct()
    {
        // Get max limits
        $this->start            = $this->time();
        $this->maxMemoryLimit   = $this->getMemoryInBytes(@ini_get("memory_limit"));
        $this->maxExecutionTime = (int) ini_get("max_execution_time");

        if ($this->maxExecutionTime < 1)
        {
            $this->maxExecutionTime = 30;
        }
        // Services
        $this->cache    = new Cache(-1, \WPStaging\WPStaging::getContentDir());
        $this->logger   = WPStaging::getInstance()->get("logger");

        // Settings and Options
        $this->options  = $this->cache->get("clone_options");
        $this->settings = json_decode(json_encode(get_option("wpstg_settings", array())));

        if (!$this->options)
        {
            $this->options = new \stdClass();
        }

        if (isset($this->options->existingClones) && is_object($this->options->existingClones))
        {
            $this->options->existingClones = json_decode(json_encode($this->options->existingClones), true);
        }

        if (!$this->settings)
        {
            $this->settings = new \stdClass();
        }

        // Set limits accordingly to CPU LIMITS
        $this->setLimits();

        if (method_exists($this, "initialize"))
        {
            $this->initialize();
        }
    }

    /**
     * Job destructor
     */
    public function __destruct()
    {
        // Commit logs
        $this->logger->commit();
    }

    /**
     * Set limits accordingly to
     */
    protected function setLimits()
    {
        if (!isset($this->settings->wpstg_cpu_load))
        {
            $this->settings->wpstg_cpu_load = "medium";
        }

        $memoryLimit= self::MAX_MEMORY_RATIO;
        $timeLimit  = self::EXECUTION_TIME_RATIO;

        switch($this->settings->wpstg_cpu_load)
        {
            case "medium":
                $memoryLimit= $memoryLimit / 2;
                $timeLimit  = $timeLimit / 2;
                break;
            case "low":
                $memoryLimit= $memoryLimit / 4;
                $timeLimit  = $timeLimit / 4;
                break;

            case "default":
            default:
                break;
        }

        $this->memoryLimit      = $this->maxMemoryLimit * $memoryLimit;
        $this->executionLimit   = $this->maxExecutionTime * $timeLimit;
    }

    /**
     * Save options
     * @param null|array|object $options
     * @return bool
     */
    protected function saveOptions($options = null)
    {
        // Get default options
        if (null === $options)
        {
            $options = $this->options;
        }

        // Ensure that it is an object
        $options = json_decode(json_encode($options));

        return $this->cache->save("clone_options", $options);
    }

    /**
     * @return object
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $memory
     * @return int
     */
    protected function getMemoryInBytes($memory)
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
    protected function formatBytes($bytes)
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
    protected function time()
    {
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        return $time;
    }

    /**
     * @return bool
     */
    protected function isOverThreshold()
    {
        // Check if the memory is over threshold
        $usedMemory = (int) @memory_get_usage(true);
        
        $this->log('used memory is ' . $usedMemory);
        
        if ($usedMemory >= $this->memoryLimit)
        {
            return (!$this->resetMemory());
        }

        
        // Check if execution time is over threshold
        ///$time = round($this->start + $this->time(), 4);
        $time = round($this->time() - $this->start, 4);
        $this->log('execution time is ' . $time . ' | execution time limit is ' . $this->executionLimit);
        if ($time >= $this->executionLimit)
        {
            return (!$this->resetTime());
        }

        return false;
    }

    /**
     * Attempt to reset memory
     * @return bool
     */
    protected function resetMemory()
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
    protected function resetTime()
    {
        // Attempt to reset timeout
        if (!@set_time_limit($this->maxExecutionTime))
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
    protected function reset()
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

    /**
     * @param string $msg
     * @param string $type
     */
    protected function log($msg, $type = Logger::TYPE_INFO)
    {
        if (false === $this->hasLoggedFileNameSet && 0 < strlen($this->options->clone))
        {
            $this->logger->setFileName($this->options->clone);
            $this->hasLoggedFileNameSet = true;
        }

        $this->logger->add($msg, $type);
    }
}