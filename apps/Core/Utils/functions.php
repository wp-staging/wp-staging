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

/**
 * Mimics the mysql_real_escape_string function. Adapted from a post by 'feedr' on php.net.
 * @link   http://php.net/manual/en/function.mysql-real-escape-string.php#101248
 * @access public
 * @param  string $input The string to escape.
 * @return string
 */
function wpstg_mysql_escape_mimic( $input ) {
    if( is_array( $input ) ) {
        return array_map( __METHOD__, $input );
    }
    if( !empty( $input ) && is_string( $input ) ) {
        return str_replace( array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $input );
    }

    return $input;
}

/**
 * Search & Replace first occurence of string in haystack
 * @param type $haystack
 * @param type $needle
 * @return type
 */
function wpstg_replace_first_match( $needle, $replace, $haystack ) {
    $result = $haystack;
    $pos    = strpos( $haystack, $needle );
    if( $pos !== false ) {
        $result = substr_replace( $haystack, $replace, $pos, strlen( $needle ) );
    }
    return $result;
}

/**
 * Search & Replace last occurence of string in haystack
 * @param type $haystack
 * @param type $needle
 * @return type
 */
function wpstg_replace_last_match( $needle, $replace, $haystack ) {
    $result = $haystack;
    $pos    = strrpos( $haystack, $needle );
    if( $pos !== false ) {
        $result = substr_replace( $haystack, $replace, $pos, strlen( $needle ) );
    }
    return $result;
}

/**
 * Check if string is valid date
 * @param type $date
 * @param type $format
 * @return bool
 */
function wpstg_validate_date( $date, $format = 'Y-m-d' ) {
    $d = DateTime::createFromFormat( $format, $date );
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $d->format( $format ) === $date;
}
