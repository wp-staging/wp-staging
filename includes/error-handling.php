<?php

/**
 * Write the debug log for regular events
 * 
 * @param string $string
 */
function wpstg_log($string){
        $wpstg_logger = new wpstgLogger("wpstglog_" . date("Y-m-d") . ".log", wpstgLogger::INFO);
        $wpstg_logger->info($string);
}

/**
 * Write the debug log for error events
 * 
 * @param string $string
 */
function wpstg_log_error($string){
        $wpstg_logger = new wpstgLogger("wpstglog_" . date("Y-m-d") . ".log", wpstgLogger::INFO);
        $wpstg_logger->error($string);
}

/**
 * Write extended debug messsages into logfiles
 * when debug mode is enabled
 * 
 * @param string $error
 */
function wpstg_debug_log($error){
    global $wpstg_options;
    
    if ( isset($wpstg_options['debug_mode']) ){
        wpstg_log($error);
    }
}

