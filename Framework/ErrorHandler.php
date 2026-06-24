<?php

namespace WPStaging\Framework;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Logger\SseEventCache;

/**
 * @package WPStaging\Framework
 */
class ErrorHandler
{
    /** @var string */
    const ERROR_FILE_EXTENSION = '.wpstgerror';

    public function registerShutdownHandler()
    {
        register_shutdown_function([$this, 'shutdownHandler']);
    }

    public function shutdownHandler()
    {
        if (!defined('WPSTG_REQUEST')) {
            return;
        }

        if (!defined('WPSTG_UPLOADS_DIR')) {
            return;
        }

        /**
         * Requests for which to check for memory exhaustion
         * Using hardcoded values below to avoid loading all classes
         * @var array $wpStagingRequests
         */
        $wpStagingRequests = [
            'wpstg_backup', // @see WPStaging\Backup\Ajax\Backup::WPSTG_REQUEST
            'wpstg_restore', // @see WPStaging\Backup\Ajax\Restore::WPSTG_REQUEST
            'wpstg_cloning', // @see WPStaging\Backend\Modules\Jobs\Cloning::WPSTG_REQUEST,
            'wpstg_remote_sync_pull', // @see WPStaging\Pro\RemoteSync\BackgroundProcessing\PreparePull::WPSTG_REQUEST
            'wpstg_staging_create', // @see WPStaging\Staging\Ajax\Create::WPSTG_REQUEST
            'wpstg_staging_update', // @see WPStaging\Staging\Ajax\Update::WPSTG_REQUEST
            'wpstg_staging_reset', // @see WPStaging\Staging\Ajax\Reset::WPSTG_REQUEST
            'wpstg_staging_push', // @see WPStaging\Pro\Push\Ajax\Push::WPSTG_REQUEST
        ];

        $wpStagingRequest = WPSTG_REQUEST;
        if (!in_array($wpStagingRequest, $wpStagingRequests, true)) {
            return;
        }

        $error = error_get_last();
        if (!is_array($error)) {
            return;
        }

        if ($error['type'] !== E_ERROR) {
            return;
        }

        preg_match('/Allowed memory size of (\d+) bytes exhausted \(tried to allocate (\d+) bytes\)/', $error['message'], $data);
        if (!is_array($data) || count($data) !== 3) {
            $data['time'] = date('Y/m/d H:i:s', time()); // @see WPStaging\Core\Utils\Logger::LOG_DATETIME_FORMAT, use hardcoded value to avoid loading class
            $this->logSseEvent($data, false);
            $this->releaseProcessLock();
            return;
        }

        // Temporary file to store the error message
        $errorTmpFile = WPSTG_UPLOADS_DIR . $wpStagingRequest . self::ERROR_FILE_EXTENSION;

        $fileHandler = fopen($errorTmpFile, 'w');

        $data = [
            'memoryUsage'         => memory_get_usage(true),
            'peakMemoryUsage'     => memory_get_peak_usage(true),
            'phpMemoryLimit'      => ini_get('memory_limit'),
            'wpMemoryLimit'       => defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : '',
            'allowedMemoryLimit'  => $data[1],
            'exhaustedMemorySize' => $data[2],
            'time'                => date('Y/m/d H:i:s', time()), // @see WPStaging\Core\Utils\Logger::LOG_DATETIME_FORMAT, use hardcoded value to avoid loading class
        ];

        if (is_resource($fileHandler)) {
            fwrite($fileHandler, json_encode($data));
            fclose($fileHandler);
        }

        $this->logSseEvent($data);
        $this->releaseProcessLock();
    }

    /**
     * Release the process lock when a fatal error terminates PHP before the job
     * can clean up. Without this, the next request stays blocked by the stale
     * lock until ProcessLock's 120s timeout elapses.
     *
     * @return void
     */
    private function releaseProcessLock()
    {
        try {
            WPStaging::make(ProcessLock::class)->unlockProcess();
        } catch (\Throwable $e) {
            // No-op: shutdown handler must not throw.
        }
    }

    private function logSseEvent(array $data, bool $isMemoryExhaust = true)
    {
        /**
         * @var JobTransientCache $jobTransientCache
         */
        $jobTransientCache = WPStaging::make(JobTransientCache::class);

        $jobId = $jobTransientCache->getJobId();
        if (empty($jobId)) {
            return;
        }

        if ($isMemoryExhaust) {
            $exhaustedMemorySize = isset($data['exhaustedMemorySize']) ? (int)$data['exhaustedMemorySize'] : 0;
            $memoryUsage         = isset($data['memoryUsage']) ? (int)$data['memoryUsage'] : 0;
            $peakMemoryUsage     = isset($data['peakMemoryUsage']) ? (int)$data['peakMemoryUsage'] : 0;
            $allowedMemoryLimit  = isset($data['allowedMemoryLimit']) ? (int)$data['allowedMemoryLimit'] : 0;
            $phpMemoryLimit      = isset($data['phpMemoryLimit']) ? $data['phpMemoryLimit'] : ini_get('memory_limit');
            $wpMemoryLimit       = isset($data['wpMemoryLimit']) ? $data['wpMemoryLimit'] : (defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : '');

            $message = sprintf(
                esc_html__('Memory exhaust issue is detected during the process. Error occurred when allocating more %s on top of current usage of %s. Peak memory usage: %s, Allowed memory limit: %s, PHP memory limit: %s, WP memory limit: %s', 'wp-staging'),
                size_format($exhaustedMemorySize),
                size_format($memoryUsage),
                size_format($peakMemoryUsage),
                size_format($allowedMemoryLimit),
                $phpMemoryLimit,
                $wpMemoryLimit
            );
        } else {
            $message = sprintf(
                esc_html__('Job failed due to a fatal error! Error data: %s', 'wp-staging'),
                esc_html(print_r($data, true))
            );
        }

        $data['jobId']   = $jobId;
        $data['message'] = $message;

        /**
         * @var SseEventCache $sseEventCache
         */
        $sseEventCache = WPStaging::make(SseEventCache::class);
        $sseEventCache->setJobId($jobId);
        $sseEventCache->load();
        $sseEventCache->push([
            'type' => $isMemoryExhaust ? SseEventCache::EVENT_TYPE_MEMORY_EXHAUST : SseEventCache::EVENT_TYPE_FATAL_ERROR,
            'data' => $data,
        ]);

        $jobTransientCache->failJob('', $message);
    }
}
