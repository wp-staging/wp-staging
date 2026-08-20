<?php





namespace WPStaging\functions;








function debug_log($message, $logType = 'info', $logInDebugLog = true)
{
 
    static $fileHandler;

    if ($logType === 'debug' && !defined('WPSTG_DEBUG') || defined('WPSTG_DEBUG') && !WPSTG_DEBUG) {
        return;
    }

    if ($logInDebugLog && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('[' . $logType . '] WP Staging - ' . $message, 0);
    }

    if (!defined('WPSTG_DEBUG_LOG_FILE')) {
        return;
    }

    if (is_null($fileHandler)) {
 
        $fileHandler = @fopen(WPSTG_DEBUG_LOG_FILE, 'a');

 
 
        if (stripos(PHP_OS, "WIN") !== 0 && is_resource($fileHandler)) {
            flock($fileHandler, LOCK_SH | LOCK_NB);
        }
    }

    $message = sprintf(
        "[WP STAGING Manual Logging][%s][%s] %s\n",
        $logType,
        current_time('mysql'),
        $message
    );

    if (is_resource($fileHandler)) {
        try {
            fwrite($fileHandler, $message, 5 * MB_IN_BYTES);
        } catch (\Throwable $ex) {
 
            error_log('WP Staging - Error writing to the debug log file: ' . $ex->getMessage());
        }
    }
}




function shutdown_function()
{
    if (!defined('WPSTG_DEBUG_LOG_FILE') || !defined('WPSTG_PLUGIN_SLUG')) {
        return;
    }

    $error = error_get_last();

    if (!is_array($error)) {
        return;
    }

 
    $fatalErrorTypes = [
        E_ERROR             => 'E_ERROR',
        E_PARSE             => 'E_PARSE',
        E_USER_ERROR        => 'E_USER_ERROR',
        E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
    ];

 
    $allErrorTypes = [
        E_ERROR             => "E_ERROR",
        E_WARNING           => "E_WARNING",
        E_PARSE             => "E_PARSE",
        E_NOTICE            => "E_NOTICE",
        E_CORE_ERROR        => "E_CORE_ERROR",
        E_CORE_WARNING      => "E_CORE_WARNING",
        E_COMPILE_ERROR     => "E_COMPILE_ERROR",
        E_COMPILE_WARNING   => "E_COMPILE_WARNING",
        E_USER_ERROR        => "E_USER_ERROR",
        E_USER_WARNING      => "E_USER_WARNING",
        E_USER_NOTICE       => "E_USER_NOTICE",
        E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
        E_DEPRECATED        => "E_DEPRECATED",
        E_USER_DEPRECATED   => "E_USER_DEPRECATED",
        E_ALL               => "E_ALL",
    ];

 
    if (version_compare(PHP_VERSION, '8.4.0', '<')) {
        $allErrorTypes[E_STRICT] = "E_STRICT";
    }

    $isFatalError       = isset($fatalErrorTypes[$error['type']]);
    $comesFromWpStaging = strpos($error['file'], WPSTG_PLUGIN_SLUG) !== false;








    if ($isFatalError || $comesFromWpStaging) {
 
        $fileHandler = @fopen(WPSTG_DEBUG_LOG_FILE, 'a');

        $message = sprintf(
            "[WP STAGING Shutdown Function][%s][%s] %s - File: %s Line: %s | Is it Fatal Error? %s | Is it Thrown by WP STAGING? %s\n",
            $allErrorTypes[$error['type']],
            current_time('mysql'),
            $error['message'],
            $error['file'],
            $error['line'],
            $isFatalError ? 'Yes' : 'No',
            $comesFromWpStaging ? 'Yes' : 'No'
        );

        if (is_resource($fileHandler)) {
            fwrite($fileHandler, $message, 5 * MB_IN_BYTES);
        }
    }
}

register_shutdown_function('\WPStaging\functions\shutdown_function');
