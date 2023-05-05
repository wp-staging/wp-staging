<?php

/*
 * Low-level error handler and debugger for WP STAGING
 */

namespace WPStaging\functions;

/**
 * @param string $message The debug message.
 * @param string $logType A PSR-3 compatible-log type. If "debug", it only logs if WPSTG_DEBUG is true.
 *
 * @see \Psr\Log\LogLevel
 */
function debug_log($message, $logType = 'info')
{
    // Keep the file handler open for the duration of the request for performance.
    static $fileHandler;

    if ($logType === 'debug' && !defined('WPSTG_DEBUG') || defined('WPSTG_DEBUG') && !WPSTG_DEBUG) {
        return;
    }

    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('[' . $logType . '] WP Staging - ' . $message, 0);
    }

    if (!defined('WPSTG_DEBUG_LOG_FILE')) {
        return;
    }

    if (is_null($fileHandler)) {
        // Open the file handler once per request, and keep it open, as we might need to write to it multiple times.
        $fileHandler = @fopen(WPSTG_DEBUG_LOG_FILE, 'a');

        // On Windows OS we need to remove the lock handle first before locking it again.
        if (stripos(PHP_OS, "WIN") === 0) {
            flock($fileHandler, LOCK_UN);
        }

        // Make sure the lock is shared, as we might need to open the handler again if a fatal error occurs.
        if (is_resource($fileHandler)) {
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
        fwrite($fileHandler, $message, 5 * MB_IN_BYTES);
    }
}

/**
 * Logs fatal errors in the WP STAGING debug file.
 */
function shutdown_function()
{
    if (!defined('WPSTG_DEBUG_LOG_FILE') || !defined('WPSTG_PLUGIN_SLUG')) {
        return;
    }

    $error = error_get_last();

    if (!is_array($error)) {
        return;
    }

    // Errors that bring PHP to a halt.
    $fatalErrorTypes = [
        E_ERROR             => 'E_ERROR',
        E_PARSE             => 'E_PARSE',
        E_USER_ERROR        => 'E_USER_ERROR',
        E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
    ];

    // Provide friendly-names for the error codes
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
        E_STRICT            => "E_STRICT",
        E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
        E_DEPRECATED        => "E_DEPRECATED",
        E_USER_DEPRECATED   => "E_USER_DEPRECATED",
        E_ALL               => "E_ALL",
    ];

    $isFatalError       = isset($fatalErrorTypes[$error['type']]);
    $comesFromWpStaging = strpos($error['file'], WPSTG_PLUGIN_SLUG) !== false;

    /*
     * Logs fatal errors that happens anywhere,
     * and notices, warnings that comes from a WP STAGING file.
     *
     * (It will only log notices and errors from WP STAGING
     * if it was the last notice/warning triggered before PHP shutdown)
     */
    if ($isFatalError || $comesFromWpStaging) {
        // Opening a file handler gives us more control than error_log('foo', 3, 'custom-file.log');
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
