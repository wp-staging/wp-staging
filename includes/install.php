<?php
/**
 * Install Function
 *
 * @package     WPSTG
 * @subpackage  Functions/Install
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/* Install Multisite
 * check first if multisite is enabled
 * @since 0.9.0
 * 
 */

register_activation_hook( WPSTG_PLUGIN_FILE, 'wpstg_install_multisite' );

function wpstg_install_multisite($networkwide) {
    global $wpdb;
                 
    if (function_exists('is_multisite') && is_multisite()) {
        // check if it is a network activation - if so, run the activation function for each blog id
        if ($networkwide) {
            $old_blog = $wpdb->blogid;
            // Get all blog ids
            $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blogids as $blog_id) {
                switch_to_blog($blog_id);
                wpstg_install();
            }
            switch_to_blog($old_blog);
            return;
        }   
    } 
    wpstg_install();      
}

/**
 * Install
 *
 * Runs on plugin install to populates the settings fields for those plugin
 * pages. After successful install, the user is redirected to the WPSTG Welcome
 * screen.
 *
 * @since 0.9.0
 * @global $wpdb
 * @global $wpstg_options
 * @global $wp_version
 * @return void
 */



function wpstg_install() {
	global $wpdb, $wpstg_options, $wp_version;
        

	// Add Upgraded from Option
	$current_version = get_option( 'wpstg_version' );
	if ( $current_version ) {
		update_option( 'wpstg_version_upgraded_from', $current_version );
	}

        // Update the current version
        update_option( 'wpstg_version', WPSTG_VERSION );
        // Add plugin installation date and variable for rating div
        add_option('wpstg_installDate',date('Y-m-d h:i:s'));
        add_option('wpstg_RatingDiv','no');
        // Add First-time variables
        add_option('wpstg_firsttime','true');
        add_option('wpstg_is_staging_site','false');
        // Show beta notice
        add_option('wpstg_hide_beta','no');
                
        // Create empty config files in /wp-content/uploads/wp-staging
        wpstg_create_remaining_files();
        wpstg_create_clonedetails_files();

	
                
    /* Setup some default options
     * Store our initial social networks in separate option row.
     * For easier modification and to prevent some trouble
     */
    
    // Bail if activating from network, or bulk
	if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
		return;
        }
        
        // Add the transient to redirect / not for multisites
	set_transient( '_wpstg_activation_redirect', true, 30 );

}

/**
 * Post-installation
 *
 * Runs just after plugin installation and exposes the
 * wpstg_after_install hook.
 *
 * @since 2.0
 * @return void
 */
function wpstg_after_install() {

	if ( ! is_admin() ) {
		return;
	}


	$activation_pages = get_transient( '_wpstg_activation_pages' );

	// Exit if not in admin or the transient doesn't exist
	if ( false === $activation_pages ) {
		return;
	}

	// Delete the transient
	delete_transient( '_wpstg_activation_pages' );

	do_action( 'wpstg_after_install', $activation_pages );
}
add_action( 'admin_init', 'wpstg_after_install' );


/** 
 * Create json remaining_files.json after activation of the plugin
 * 
 * @return bool
 */
function wpstg_create_remaining_files() { 
        $path = wpstg_get_upload_dir();
	if (wp_is_writable($path)) {
                $file = 'remaining_files.json';
		file_put_contents($path . '/' . $file, null);
        }else {
            WPSTG()->logger->info($path . '/' . $file . ' is not writeable! ');
        }
}

/** 
 * Create json cloning_details.json after activation of the plugin
 * 
 * @return bool
 */
function wpstg_create_clonedetails_files() { 
        $path = wpstg_get_upload_dir();
	if (wp_is_writable($path)) {
                $file = 'clone_details.json';
		file_put_contents($path . '/' . $file, null);
        }else {
            WPSTG()->logger->info($path . '/' . $file . ' is not writeable! ');
        }
}