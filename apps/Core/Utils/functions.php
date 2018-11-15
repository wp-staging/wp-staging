<?php

/**
 * Get directory permissions
 *
 * @return int
 */
function wpstg_get_permissions_for_directory() {
    if( defined( 'FS_CHMOD_DIR' ) ) {
        return FS_CHMOD_DIR;
    }

    return 0755;
}

/**
 * Get file permissions
 *
 * @return int
 */
function wpstg_get_permissions_for_file() {
    if( defined( 'FS_CHMOD_FILE' ) ) {
        return FS_CHMOD_FILE;
    }

    return 0644;
}

/**
 * PHP setup environment
 *
 * @return void
 */
function wpstg_setup_environment() {
    // Set whether a client disconnect should abort script execution
    @ignore_user_abort( true );

    // Set maximum execution time
    @set_time_limit( 0 );

    // Set maximum time in seconds a script is allowed to parse input data
    @ini_set( 'max_input_time', '-1' );

    // Set maximum backtracking steps
    @ini_set( 'pcre.backtrack_limit', PHP_INT_MAX );

    // Set binary safe encoding
//	if ( @function_exists( 'mb_internal_encoding' ) && ( @ini_get( 'mbstring.func_overload' ) & 2 ) ) {
//		@mb_internal_encoding( 'ISO-8859-1' );
//	}
}

/**
 * Escape Windows directory separator
 *
 * @param string $path Path
 *
 * @return string
 */
function wpstg_escape_windows_directory_separator( $path ) {
    return preg_replace( '/[\\\\]+/', '\\\\\\\\', $path );
}

/**
 * Replace Windows directory separator
 * Replace backward slash with forward slash directory separator
 * Windows Compatibility Fix
 *
 * @param string $path Path
 *
 * @return string
 */
function wpstg_replace_windows_directory_separator( $path ) {
    return preg_replace( '/[\\\\]+/', '/', $path );
}
