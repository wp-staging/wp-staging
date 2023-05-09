<?php

namespace WPStaging\Framework;

use WPStaging\Core\Utils\Logger;

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
         * @var array $wpStagingRequests
         */
        $wpStagingRequests = [
            'wpstg_backup',
            'wpstg_restore',
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
            return;
        }

        // Temporary file to store the error message
        $errorTmpFile = WPSTG_UPLOADS_DIR . $wpStagingRequest . self::ERROR_FILE_EXTENSION;

        $fileHandler = fopen($errorTmpFile, 'w');

        $message = json_encode([
            'memoryUsage'         => memory_get_usage(true),
            'peakMemoryUsage'     => memory_get_peak_usage(true),
            'phpMemoryLimit'      => ini_get('memory_limit'),
            'wpMemoryLimit'       => defined('WP_MEMORY_LIMIT') ? WP_MEMORY_LIMIT : '',
            'allowedMemoryLimit'  => $data[1],
            'exhaustedMemorySize' => $data[2],
            'time'                => date(Logger::LOG_DATETIME_FORMAT, time()),
        ]);

        if (is_resource($fileHandler)) {
            fwrite($fileHandler, $message);
            fclose($fileHandler);
        }
    }
}
