<?php

namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

use WPStaging\Backend\Modules\Jobs\Interfaces\JobInterface;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Core\Utils\Cache;
use WPStaging\Core\thirdParty\thirdPartyCompatibility;
use DateTime;
use DateInterval;
use Exception;

/**
 * Class Job
 * @package WPStaging\Backend\Modules\Jobs
 */
abstract class Job implements JobInterface
{

    const EXECUTION_TIME_RATIO = 0.8;
    const MAX_MEMORY_RATIO = 1;

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
     * System total maximum memory consumption
     * @var int
     */
    protected $maxMemoryLimit;

    /**
     * Script maximum memory consumption
     * @var int
     */
    protected $memoryLimit;

    /**
     * @var int
     */
    protected $maxExecutionTime;

    /**
     * @var int
     */
    protected $executionLimit;

    /**
     * @var int
     */
    protected $totalRecursion;

    /**
     * @var int
     */
    protected $maxRecursionLimit;

    /**
     * Multisite Home Url without Scheme
     * @var string
     */
    //protected $multisiteHomeUrlWithoutScheme;

    /**
     * Multisite home domain without scheme
     * @var string
     */
    //protected $multisiteDomainWithoutScheme;

    /**
     * Multisite home domain without scheme
     * @var string
     */
    protected $baseUrl;

    /**
     *
     * @var object
     */
    protected $thirdParty;

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
        $this->start = $this->time();
        $this->maxMemoryLimit = $this->getMemoryInBytes(@ini_get("memory_limit"));
        $this->thirdParty = new thirdPartyCompatibility();

        //$multisite = new Multisite;
        //$this->multisiteHomeUrlWithoutScheme = $multisite->getHomeUrlWithoutScheme();
        //$this->baseUrl = (new Helper)->getBaseUrl();
        //$this->multisiteDomainWithoutScheme = $multisite->getHomeDomainWithoutScheme();
        $this->maxExecutionTime = ( int )10;

        // Services
        $this->cache = new Cache(-1, \WPStaging\Core\WPStaging::getContentDir());
        $this->logger = WPStaging::getInstance()->get("logger");

        // Settings and Options
        $this->options = $this->cache->get("clone_options");

        $this->settings = ( object )get_option("wpstg_settings", []);

        if (!$this->options) {
            $this->options = new \stdClass();
        }

        if (isset($this->options->existingClones) && is_object($this->options->existingClones)) {
            $this->options->existingClones = json_decode(json_encode($this->options->existingClones), true);
        }

        // check default options
        if (!isset($this->settings) ||
            !isset($this->settings->queryLimit) ||
            !isset($this->settings->querySRLimit) ||
            !isset($this->settings->batchSize) ||
            !isset($this->settings->cpuLoad) ||
            !isset($this->settings->maxFileSize) ||
            !isset($this->settings->fileLimit)
        ) {
            $this->settings = new \stdClass();
            $this->setDefaultSettings();
        }

        // Set limits accordingly to CPU LIMITS
        $this->setLimits();

        $this->maxRecursionLimit = ( int )ini_get("xdebug.max_nesting_level");

        /*
         * This is needed to make sure that maxRecursionLimit = -1
         * if xdebug is not used in production env.
         * For using xdebug, maxRecursionLimit must be larger
         * otherwise xdebug is throwing an error 500 while debugging
         */
        if ($this->maxRecursionLimit < 1) {
            $this->maxRecursionLimit = -1;
        } else {
            $this->maxRecursionLimit = $this->maxRecursionLimit - 50; // just to make sure
        }

        if (method_exists($this, "initialize")) {
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
     * Set default settings
     */
    protected function setDefaultSettings()
    {
        $this->settings->queryLimit = "10000";
        $this->settings->querySRLimit = "5000";
        $this->settings->fileLimit = "50";
        $this->settings->batchSize = "2";
        $this->settings->cpuLoad = 'low';
        $this->settings->maxFileSize = 8;
        $this->settings->optimizer = "1";
        update_option('wpstg_settings', $this->settings);
    }

    /**
     * Set limits accordingly to
     */
    protected function setLimits()
    {
        if (!isset($this->settings->cpuLoad)) {
            $this->settings->cpuLoad = "low";
        }

        $memoryLimit = self::MAX_MEMORY_RATIO;
        $timeLimit = self::EXECUTION_TIME_RATIO;

        switch ($this->settings->cpuLoad) {
            case "medium":
                $timeLimit = $timeLimit / 2;
                break;
            case "low":
                $timeLimit = $timeLimit / 4;
                break;

            case "fast":
            default:
                break;
        }

        $this->memoryLimit = $this->maxMemoryLimit * $memoryLimit;
        $this->executionLimit = $this->maxExecutionTime * $timeLimit;
    }

    /**
     * Save options
     * @param null|array|object $options
     * @return bool
     */
    protected function saveOptions($options = null)
    {
        // Get default options
        if ($options === null) {
            $options = $this->options;
        }

        $now = new DateTime;
        $options->expiresAt = $now->add(new DateInterval('P1D'))->format('Y-m-d H:i:s');

        // Ensure that it is an object
        $options = json_decode(json_encode($options));
        return $this->cache->save('clone_options', $options);
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
        if (( int )$memory < 1) {
            // 128 MB default value
            return ( int )134217728;
        }

        $bytes = ( int )$memory; // grab only the number
        $size = trim(str_replace($bytes, null, strtolower($memory))); // strip away number and lower-case it
        // Actual calculation
        switch ($size) {
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
        if (( int )$bytes < 1) {
            return '';
        }

        $units = ['B', 'K', 'M', 'G']; // G since PHP 5.1.x so we are good!

        $bytes = ( int )$bytes;
        $base = log($bytes) / log(1000);
        $pow = pow(1000, $base - floor($base));

        return round($pow, 0) . $units[( int )floor($base)];
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
    public function isOverThreshold()
    {
        // Check if the memory is over threshold
        $usedMemory = ( int )@memory_get_usage(true);

        $this->debugLog('Used Memory: ' . $this->formatBytes($usedMemory) . ' Max Memory Limit: ' . $this->formatBytes($this->maxMemoryLimit) . ' Max Script Memory Limit: ' . $this->formatBytes($this->memoryLimit), Logger::TYPE_DEBUG);

        if ($usedMemory >= $this->memoryLimit) {
            $this->log('Used Memory: ' . $this->formatBytes($usedMemory) . ' Memory Limit: ' . $this->formatBytes($this->maxMemoryLimit) . ' Max Script memory limit: ' . $this->formatBytes($this->memoryLimit), Logger::TYPE_ERROR);
            return true;
        }

        if ($this->isRecursionLimit()) {
            return true;
        }

        // Check if execution time is over threshold
        $time = round($this->time() - $this->start, 4);
        if ($time >= $this->executionLimit) {
            $this->debugLog('RESET TIME: current time: ' . $time . ', Start Time: ' . $this->start . ', exec time limit: ' . $this->executionLimit);
            return true;
        }

        return false;
    }

    /**
     * Checks if calls are over recursion limit
     * @return bool
     */
    protected function isRecursionLimit()
    {
        return ($this->maxRecursionLimit > 0 && $this->totalRecursion >= $this->maxRecursionLimit);
    }

    /**
     * @param string $msg
     * @param string $type
     */
    public function log($msg, $type = Logger::TYPE_INFO)
    {
        if (!isset($this->options->clone)) {
            $this->options->clone = date(DATE_ATOM, mktime(0, 0, 0, 7, 1, 2000));
        }

        if ($this->hasLoggedFileNameSet === false && strlen($this->options->clone) > 0) {
            $this->logger->setFileName($this->options->clone);
            $this->hasLoggedFileNameSet = true;
        }

        $this->logger->add($msg, $type);
    }

    /**
     * @param string $msg
     * @param string $type
     */
    public function debugLog($msg, $type = Logger::TYPE_INFO)
    {
        if (!isset($this->options->clone)) {
            $this->options->clone = date(DATE_ATOM, mktime(0, 0, 0, 7, 1, 2000));
        }

        if ($this->hasLoggedFileNameSet === false && strlen($this->options->clone) > 0) {
            $this->logger->setFileName($this->options->clone);
            $this->hasLoggedFileNameSet = true;
        }


        if (isset($this->settings->debugMode)) {
            $this->logger->add($msg, $type);
        }
    }

    /**
     * Throw a errror message via json and stop further execution
     * @param string $message
     */
    public function returnException($message = '')
    {
        wp_die(
            json_encode(
                [
                    'job' => isset($this->options->currentJob) ? $this->options->currentJob : '',
                    'status' => false,
                    'message' => $message,
                    'error' => true
                ]
            )
        );
    }

    /**
     * @return bool
     */
    protected function isRunning()
    {
        if (!isset($this->options) || !isset($this->options->isRunning) || !isset($this->options->expiresAt)) {
            return false;
        }

        try {
            $now = new DateTime;
            $expiresAt = new DateTime($this->options->expiresAt);
            return $this->options->isRunning === true && $now < $expiresAt;
        } catch (Exception $e) {
        }

        return false;
    }
}
