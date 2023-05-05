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

    public function __construct()
    {
        set_exception_handler(['WPStaging\Framework\ErrorHandler', 'customExceptionHandler']);
    }

    /**
     * @param $exception
     * @return void
     */
    public static function customExceptionHandler($exception)
    {
        $message = "Something went wrong. Error: " . $exception;
        error_log($message);

        $stackTrace = "<pre style='font-size:10px;'>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";

        $mailSubject = urlencode("Fatal Error on Our Website");
        $mailBody    = rawurlencode(htmlspecialchars($exception->getTraceAsString()));

        $html = "<div style='background-color:#cfe1ff;padding:20px;margin-right:20px;'><strong>Something went wrong!</strong> Please <a href='https://wp-staging.com/support/#pro-support' target='_blank'>contact WP Staging</a> or write to <a href='mailto:support@wp-staging.com?subject=" . $mailSubject . "&body=" . $mailBody . "' target='_blank'>support@wp-staging.com</a> and send these errors!";
        $html .= "<p>If this error causes your entire site to be unavailable, go to <code>wp-admin > Plugins</code> and temporarily disable the WP Staging plugin until we fix the issue.</p>";
        $html .= "If you can't access the plugins page, rename the folder <code>wp-content/plugins/wp-staging(-pro)</code> with FTP to disable the WP Staging plugin.";
        $html .= '</div>';
        $html .= "<div style='background-color:#deedff;padding:20px;margin-right:20px;'>";
        $html .= "<h1 style='font-size: 14px;line-height: 20px;color:#d63638;margin-top:0px;'>Exception: " . htmlspecialchars($exception->getMessage()) . "</h1>";
        $html .= "" . htmlspecialchars($exception->getFile()) . " on line " . htmlspecialchars($exception->getLine()) . "";
        $html .= "Call stack:";
        $html .= $stackTrace;
        $html .= '</div>';

        echo wp_kses_post(strip_tags($html, '<div><pre><code><p><a><strong><h1>'));
    }

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
