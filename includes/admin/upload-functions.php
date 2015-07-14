<?php

/**
 * Upload Functions
 *
 * @package     WPSTG
 * @subpackage  Admin/Upload
 * @copyright   Copyright (c) 2015, Pippin Williamson, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.9.0
 */


/**
 * Retrieve the absolute path to the file upload directory without the trailing slash
 *
 * @since  0.9.0
 * @return string $path Absolute path to the WPSTG upload directory
 */
function wpstg_get_upload_dir() {
	$wp_upload_dir = wp_upload_dir();
	wp_mkdir_p( $wp_upload_dir['basedir'] . '/wp-staging' );
	$path = $wp_upload_dir['basedir'] . '/wp-staging';

	return apply_filters( 'wpstg_get_upload_dir', $path );
}

/**
 * Checks if the .htaccess file exists in wp-content/uploads/wp-staging
 *
 * @since 0.9.0
 * @return bool
 */
function wpstg_htaccess_exists() {
	$upload_path = wpstg_get_upload_dir();

	return file_exists( $upload_path . '/.htaccess' );
}

/**
 * Checks if the remaining_files.json file exists in wp-content/uploads/wp-staging
 *
 * @since 0.9.0
 * @return bool
 */
function wpstg_remainingjson_exists() {
	$upload_path = wpstg_get_upload_dir();

	return file_exists( $upload_path . '/remaining_files.json' );
}

/**
 * Checks if the clone_details.json file exists in wp-content/uploads/wp-staging
 *
 * @since 0.9.0
 * @return bool
 */
function wpstg_clonedetailsjson_exists() {
	$upload_path = wpstg_get_upload_dir();

	return file_exists( $upload_path . '/clone_details.json' );
}

/**
 * Retrieve the .htaccess rules to wp-content/uploads/wp-staging/
 *
 * @since 0.9.0
 *
 * @param bool $method
 * @return mixed|void The htaccess rules
 */
function wpstg_get_htaccess_rules() {
    // Prevent directory browsing and direct access to all files
    $rules = "<Files \"*\">\n";
    $rules .= "<IfModule mod_access.c>\n";
    $rules .= "Deny from all\n";
    $rules .= "</IfModule>\n";
    $rules .= "<IfModule !mod_access_compat>\n";
    $rules .= "<IfModule mod_authz_host.c>\n";
    $rules .= "Deny from all\n";
    $rules .= "</IfModule>\n";
    $rules .= "</IfModule>\n";
    $rules .= "<IfModule mod_access_compat>\n";
    $rules .= "Deny from all\n";
    $rules .= "</IfModule>\n";
    $rules .= "</Files>\n";
    $rules = apply_filters('wpstg_protected_directory_htaccess_rules', $rules);
    return $rules;
}

/**
 * Creates blank index.php and .htaccess files
 * Creates /wp-content/uploads/wp-staging subfolder
 *
 * This function runs approximately once per month in order to ensure all folders
 * have their necessary protection files
 *
 * @since 1.1.0
 *
 * @param bool $force
 */

function wpstg_create_protection_files( $force = false ) {
	if ( false === get_transient( 'wpstg_check_protection_files' ) || $force ) {

		$upload_path = wpstg_get_upload_dir();

		// Make sure the /wpstg folder is created
		wp_mkdir_p( $upload_path );

		// Top level .htaccess file
		$rules = wpstg_get_htaccess_rules();
		if ( wpstg_htaccess_exists() ) {
			$contents = @file_get_contents( $upload_path . '/.htaccess' );
			if ( $contents !== $rules || ! $contents ) {
				// Update the .htaccess rules if they don't match
				@file_put_contents( $upload_path . '/.htaccess', $rules );
			}
		} elseif( wp_is_writable( $upload_path ) ) {
			// Create the file if it doesn't exist
			@file_put_contents( $upload_path . '/.htaccess', $rules );
		}

		// Top level blank index.php
		if ( ! file_exists( $upload_path . '/index.php' ) && wp_is_writable( $upload_path ) ) {
			@file_put_contents( $upload_path . '/index.php', '<?php' . PHP_EOL . '// Silence is golden.' );
		}

		// Now place index.php files in all sub folders
		$folders = wpstg_scan_upload_folders( $upload_path );
		foreach ( $folders as $folder ) {
			// Create index.php, if it doesn't exist
			if ( ! file_exists( $folder . 'index.php' ) && wp_is_writable( $folder ) ) {
				@file_put_contents( $folder . 'index.php', '<?php' . PHP_EOL . '// Silence is golden.' );
			}
		}
		// Check for the files once per day
		set_transient( 'wpstg_check_protection_files', true, 3600 * 24 );
	}
}
add_action( 'admin_init', 'wpstg_create_protection_files' );

/**
 * Scans all folders inside of /uploads/wpstg
 *
 * @since 0.9.0
 * @return array $return List of files inside directory
 */
function wpstg_scan_upload_folders( $path = '', $return = array() ) {
	$path = $path == ''? dirname( __FILE__ ) : $path;
	$lists = @scandir( $path );

	if ( ! empty( $lists ) ) {
		foreach ( $lists as $f ) {
			if ( is_dir( $path . DIRECTORY_SEPARATOR . $f ) && $f != "." && $f != ".." ) {
				if ( ! in_array( $path . DIRECTORY_SEPARATOR . $f, $return ) )
					$return[] = trailingslashit( $path . DIRECTORY_SEPARATOR . $f );

                                    wpstg_scan_upload_folders( $path . DIRECTORY_SEPARATOR . $f, $return);
			}
		}
	}

	return $return;
}

// For installs on pre WP 3.6
if( ! function_exists( 'wp_is_writable' ) ) {

	/**
	 * Determine if a directory is writable.
	 *
	 * This function is used to work around certain ACL issues
	 * in PHP primarily affecting Windows Servers.
	 *
	 * @see win_is_writable()
	 *
	 * @since 3.6.0
	 *
	 * @param string $path
	 * @return bool
	 */
	function wp_is_writable( $path ) {
	        if ( 'WIN' === strtoupper( substr( PHP_OS, 0, 3 ) ) )
	                return win_is_writable( $path );
	        else
	                return @is_writable( $path );
	}
}

