<?php
/*
Plugin Name: WP Staging Optimizer
Plugin URI: https://wp-staging.com
Description: Prevents 3rd party plugins from being loaded during WP Staging specific operations
Author: René Hermenau
Version: 1.0
Author URI: https://wp-staging.com
Credit: Original version is made by Delicious Brains (WP Migrate DB). Thank you guys!
*/

$GLOBALS['wpstg_optimizer'] = true;

/**
 * Remove TGM Plugin Activation 'force_activation' admin_init action hook if present.
 *
 * This is to stop excluded plugins being deactivated after a migration, when a theme uses TGMPA to require a plugin to be always active.
 */
function wpstg_tgmpa_compatibility() {
	$remove_function = false;

	// run on wpstg page
	if ( isset( $_GET['page'] ) && 'wpstg_clone' == $_GET['page'] ) {
		$remove_function = true;
	}
	// run on wpstg ajax requests
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_POST['action'] ) && false !== strpos( $_POST['action'], 'wpstg' ) ) {
		$remove_function = true;
	}

	if ( $remove_function ) {
		global $wp_filter;
		$admin_init_functions = $wp_filter['admin_init'];
		foreach ( $admin_init_functions as $priority => $functions ) {
			foreach ( $functions as $key => $function ) {
				// searching for function this way as can't rely on the calling class being named TGM_Plugin_Activation
				if ( false !== strpos( $key, 'force_activation' ) ) {
					unset( $wp_filter['admin_init'][ $priority ][ $key ] );

					return;
				}
			}
		}
	}
}

add_action( 'admin_init', 'wpstg_tgmpa_compatibility', 1 );

/**
 * remove blog-active plugins
 *
 * @param array $plugins numerically keyed array of plugin names
 *
 * @return array
 */
function wpstg_exclude_plugins( $plugins ) {
	if ( ! is_array( $plugins ) || empty( $plugins ) ) {
		return $plugins;
	}

	if ( ! wpstg_is_compatibility_mode_request() ) {
		return $plugins;
	}

	$blacklist_plugins = wpstg_get_blacklist_plugins();

	if ( ! empty( $blacklist_plugins ) ) {
		foreach ( $plugins as $key => $plugin ) {
			if ( false !== strpos( $plugin, 'wp-staging' ) || ! isset( $blacklist_plugins[ $plugin ] ) ) {
				continue;
			}
			unset( $plugins[ $key ] );
		}
	}
        
	return $plugins;
        
}

add_filter( 'option_active_plugins', 'wpstg_exclude_plugins' );

/**
 * remove network-active plugins
 *
 * @param array $plugins array of plugins keyed by name (name=>timestamp pairs)
 *
 * @return array
 */
function wpstg_exclude_site_plugins( $plugins ) {
	if ( ! is_array( $plugins ) || empty( $plugins ) ) {
		return $plugins;
	}

	if ( ! wpstg_is_compatibility_mode_request() ) {
		return $plugins;
	}

	$blacklist_plugins = wpstg_get_blacklist_plugins();

	if ( ! empty( $blacklist_plugins ) ) {
		foreach ( array_keys( $plugins ) as $plugin ) {
			if ( false !== strpos( $plugin, 'wp-staging' ) || ! isset( $blacklist_plugins[ $plugin ] ) ) {
				continue;
			}
			unset( $plugins[ $plugin ] );
		}
	}

	return $plugins;
}

add_filter( 'site_option_active_sitewide_plugins', 'wpstg_exclude_site_plugins' );

/**
 * Should the current request be processed by Compatibility Mode?
 *
 * @return bool
 */
function wpstg_is_compatibility_mode_request() {
	if ( ! defined( 'DOING_AJAX' ) ||
	     ! DOING_AJAX ||
	     ! isset( $_POST['action'] ) ||
	     false === strpos( $_POST['action'], 'wpstg' )
	) {
            return false;
	}
	return true;
}

/**
 * Returns an array of plugin slugs to be blacklisted.
 *
 * @return array 
 */
function wpstg_get_blacklist_plugins() {
	$blacklist_plugins = array();
        
        //@todo use this later for multisites
	//$wpstg_options = get_site_option( 'wpstg_settings' );
        $wpstg_options = get_option( 'wpstg_settings' );

	if ( ! empty( $wpstg_options['blacklist_plugins'] ) ) {
		$blacklist_plugins = array_flip( $wpstg_options['blacklist_plugins'] );
	}

	return $blacklist_plugins;
}
