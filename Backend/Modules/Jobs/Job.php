<?php

namespace WPStaging\Backend\Modules\Jobs;

use DateInterval;
use DateTime;
use Exception;
use stdClass;
use WPStaging\Core\DTO\Settings;
use WPStaging\Core\Utils\Cache;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Database\ExcludedTables;
use WPStaging\Framework\Interfaces\ShutdownableInterface;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Utils\Math;

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

    /** @var \stdClass */
    protected $utilsMath;

    /**
     * Job constructor.
     * @throws Exception
     */
    public function __construct()
    {

        $this->utilsMath = new Math();

        $this->excludedTableService = new ExcludedTables();

        // Services
        $this->cache  = new Cache(-1, WPStaging::getContentDir());
        $this->logger = WPStaging::getInstance()->get("logger");

        // Settings and Options
        $this->options = $this->cache->get("clone_options");

        $this->settings = (object)((new Settings())->setDefault());

        if (!$this->options) {
            $this->options = new stdClass();
        }

        if (isset($this->options->existingClones) && is_object($this->options->existingClones)) {
            $this->options->existingClones = json_decode(json_encode($this->options->existingClones), true);
        }

        if (method_exists($this, "initialize")) {
            $this->initialize();
        }
    }

    public function onWpShutdown()
    {
        // Commit logs
        if ($this->logger instanceof Logger) {
            if (isset($this->options->mainJob)) {
                if (!empty($this->options->existingClones[$this->options->clone]) && array_key_exists('datetime', $this->options->existingClones[$this->options->clone])) {
                    $timestamp = date('Y-m-d_H-i-s', $this->options->existingClones[$this->options->clone]['datetime']);
                } else {
                    // This is a fallback for older staging sites that did not have the datetime property.
                    $timestamp = sanitize_file_name($this->options->clone) . '_' . date('Y-m-d', time());
                }

                $this->logger->setFileName($this->options->mainJob . '_' . $timestamp);
            }

            $this->logger->commit();
        } else {
            \WPStaging\functions\debug_log('Tried to commit log, but $this->logger was not a logger.');
        }
    }

    /**
     * @param null|array|object $options
     * @return bool
     * @throws Exception
     */
    public function saveOptions($options = null)
    {
        // Get default options
        if ($options === null) {
            $options = $this->options;
        }

        $now                = new DateTime();
        $options->expiresAt = $now->add(new DateInterval('P1D'))->format('Y-m-d H:i:s');

        if (!property_exists($options, 'jobIdentifier')) {
            $options->jobIdentifier = rand(0, 2147483647); // 32 bits int max
        }

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
        $usedMemory        = $this->getMemoryPeakUsage(true);
        $maxMemoryLimit    = $this->getMaxMemoryLimit();
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
                    WPStaging::$startTime,
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
            $this->options->clone = time();
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
     * Throw an error message via json and stop further execution
     * @param string $message
     */
    public function returnException($message = '')
    {
        wp_die(
            json_encode(
                [
                    'job'     => isset($this->options->currentJob) ? $this->options->currentJob : '',
                    'status'  => false,
                    'message' => $message,
                    'error'   => true
                ]
            )
        );
    }

    /**
     * Is job running
     * @return bool
     */
    protected function isRunning()
    {
        if (!isset($this->options) || !isset($this->options->isRunning) || !isset($this->options->expiresAt)) {
            return false;
        }

        try {
            $now       = new DateTime();
            $expiresAt = new DateTime($this->options->expiresAt);
            return $this->options->isRunning === true && $now < $expiresAt;
        } catch (Exception $e) {
        }

        return false;
    }

    protected function isPro()
    {
        return defined('WPSTGPRO_VERSION');
    }

    /**
     * @return bool
     */
    protected function isMultisiteAndPro()
    {
        return $this->isPro() && is_multisite();
    }

    /**
     * @return bool
     */
    public function isNetworkClone()
    {
        if (!isset($this->options->networkClone)) {
            return false;
        }

        return $this->isMultisiteAndPro() && $this->options->networkClone;
    }

    /**
     * Should exclude wp-config file during clone update
     *
     * @return bool
     */
    public function excludeWpConfigDuringUpdate()
    {
        return $this->options->mainJob === Updating::NORMAL_UPDATE;
    }

    /**
     * Check if external database is used
     * @return bool
     */
    protected function isExternalDatabase()
    {
        return !(empty($this->options->databaseUser) && empty($this->options->databasePassword));
    }
}
