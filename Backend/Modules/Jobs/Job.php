<?php

namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

use DateInterval;
use DateTime;
use Exception;
use WPStaging\Core\Utils\Cache;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Database\ExcludedTables;
use WPStaging\Framework\Interfaces\ShutdownableInterface;
use WPStaging\Framework\Traits\ResourceTrait;

/**
 * Class Job
 * @package WPStaging\Backend\Modules\Jobs
 */
abstract class Job implements ShutdownableInterface
{
    use ResourceTrait;

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
     * Multisite home domain without scheme
     * @var string
     */
    protected $baseUrl;

    /**
     * @var ExcludedTables
     */
    protected $excludedTableService;

    /**
     * Job constructor.
     * @throws Exception
     */
    public function __construct()
    {
        // TODO: inject using DI
        $this->excludedTableService = new ExcludedTables();

        // Services
        $this->cache = new Cache(-1, WPStaging::getContentDir());
        $this->logger = WPStaging::getInstance()->get("logger");

        // Settings and Options
        $this->options = $this->cache->get("clone_options");

        $this->settings = (object)get_option("wpstg_settings", []);

        if (!$this->options) {
            $this->options = new \stdClass();
        }

        if (isset($this->options->existingClones) && is_object($this->options->existingClones)) {
            $this->options->existingClones = json_decode(json_encode($this->options->existingClones), true);
        }

        // check default options
        if (
            !isset($this->settings) ||
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

        if (method_exists($this, "initialize")) {
            $this->initialize();
        }
    }

    public function onWpShutdown()
    {
        // Commit logs
        if ($this->logger instanceof Logger) {
            $this->logger->commit();
        } else {
            if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                error_log('Tried to commit log, but $this->logger was not a logger.');
            }
        }
    }

    /**
     * Set default settings
     */
    protected function setDefaultSettings()
    {
        $this->settings->queryLimit = "10000";
        $this->settings->querySRLimit = "5000";

        if (defined('WPSTG_DEV') && WPSTG_DEV) {
            $this->settings->fileLimit = "500";
            $this->settings->cpuLoad = 'high';
        } else {
            $this->settings->fileLimit = "50";
            $this->settings->cpuLoad = 'low';
        }

        $this->settings->batchSize = "2";
        $this->settings->maxFileSize = 8;
        $this->settings->optimizer = "1";
        update_option('wpstg_settings', $this->settings);
    }

    /**
     * Save options
     * @param null|array|object $options
     * @return bool
     */
    public function saveOptions($options = null)
    {
        // Get default options
        if ($options === null) {
            $options = $this->options;
        }

        $now = new DateTime();
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
        $usedMemory = $this->getMemoryPeakUsage(true);
        $maxMemoryLimit = $this->getMaxMemoryLimit();
        $scriptMemoryLimit = $this->getScriptMemoryLimit();

        $this->debugLog(
            sprintf(
                "Used Memory: %s Max Memory Limit: %s Max Script Memory Limit: %s",
                size_format($usedMemory),
                size_format($maxMemoryLimit),
                size_format($scriptMemoryLimit)
            ),
            Logger::TYPE_DEBUG
        );

        if ($this->isMemoryLimit()) {
            $this->log(
                sprintf(
                    "Used Memory: %s Memory Limit: %s Max Script memory limit: %s",
                    size_format($usedMemory),
                    size_format($maxMemoryLimit),
                    size_format($scriptMemoryLimit)
                ),
                Logger::TYPE_ERROR
            );

            return true;
        }

        // Check if execution time is over threshold
        if ($this->isTimeLimit()) {
            $this->debugLog(
                sprintf(
                    "RESET TIME: current time: %s, Start Time: %d, exec time limit: %s",
                    $this->getRunningTime(),
                    WPStaging::getInstance()->getStartTime(),
                    $this->findExecutionTimeLimit()
                )
            );
            return true;
        }

        return false;
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

        if ($this->hasLoggedFileNameSet === false && $this->options->clone != '') {
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
            $now = new DateTime();
            $expiresAt = new DateTime($this->options->expiresAt);
            return $this->options->isRunning === true && $now < $expiresAt;
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function isMultisiteAndPro()
    {
        return defined('WPSTGPRO_VERSION') && is_multisite();
    }

    /**
     * Check if external database is used
     * @return boolean
     */
    protected function isExternalDatabase()
    {
        return !(empty($this->options->databaseUser) && empty($this->options->databasePassword));
    }
}
