<?php
/**
 * Scripts
 *
 * @package     WPSTG
 * @subpackage  Functions
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Load Admin Scripts
 *
 * Enqueues the required admin scripts.
 *
 * @since 1.0
 * @global $post
 * @param string $hook Page hook
 * @return void
 */

function wpstg_load_admin_scripts( $hook ) {
        if ( ! apply_filters( 'wpstg_load_admin_scripts', wpstg_is_admin_page(), $hook ) ) {
		return;
	}
	global $wp_version, $wpstg_options;
        

	$js_dir  = WPSTG_PLUGIN_URL . 'assets/js/';
	$css_dir = WPSTG_PLUGIN_URL . 'assets/css/';

	// Use minified libraries if DEBUG is turned off
        $suffix = isset($wpstg_options['debug_mode']) ? '.min' : ''; 

        
	// These have to be global
	wp_enqueue_script( 'wpstg-admin-script', $js_dir . 'wpstg-admin' . $suffix . '.js', array( 'jquery' ), WPSTG_VERSION, false );
	wp_enqueue_style( 'wpstg-admin', $css_dir . 'wpstg-admin' . $suffix . '.css', WPSTG_VERSION );
        
	wp_localize_script( 'wpstg-admin-script', 'wpstg', array(
		'nonce'                                 => wp_create_nonce( 'wpstg_ajax_nonce' ),
                'mu_plugin_confirmation'                => __( "If confirmed we will install an additional WordPress 'Must Use' plugin. This plugin will allow us to control which plugins are loaded during WP Staging specific operations. Do you wish to continue?", 'wpstg' ),
                'plugin_compatibility_settings_problem' => __( 'A problem occurred when trying to change the plugin compatibility setting.', 'wpstg' ),
                'saved'                                 => __( 'Saved', 'The settings were saved successfully', 'wpstg' ),
                'status'                                => __( 'Status', 'Current request status', 'wpstg' ),
                'response'                              => __( 'Response', 'The message the server responded with', 'wpstg' ),
            	'blacklist_problem'                     => __( 'A problem occurred when trying to add plugins to backlist.', 'wpstg' ),
                'cpu_load'                              => wpstg_get_cpu_load_sett(),

	));
}
add_action( 'admin_enqueue_scripts', 'wpstg_load_admin_scripts', 100 );

/**
 * Load Scripts on staging site
 *
 * Enqueues the required staging scripts on all staging sites including administration dashboard
 *
 * @since 1.0.3
 * @global $post
 * @return void
 */

function wpstg_load_staging_styles() {
	global $wpstg_options;

	$css_dir = WPSTG_PLUGIN_URL . 'assets/css/';
	$suffix = isset($wpstg_options['debug_mode']) ? '.min' : ''; 
	$url = $css_dir . 'wpstg-admin-bar' . $suffix . '.css';

        if(wpstg_is_staging_site()) { // Load it on all pages
            wp_register_style( 'wpstg-admin-bar', $url, array(), WPSTG_VERSION, 'all' );
            wp_enqueue_style( 'wpstg-admin-bar' );
        }
}
add_action( 'admin_enqueue_scripts', 'wpstg_load_staging_styles', 90 );
add_action( 'wp_enqueue_scripts', 'wpstg_load_staging_styles', 10);



/**
 * Get cpu load setting
 * 
 * @global array $wpstg_options
 * @return int
 */
function wpstg_get_cpu_load_sett(){
    global $wpstg_options;
    
    $get_cpu_load = isset($wpstg_options['wpstg_cpu_load']) ? $wpstg_options['wpstg_cpu_load'] : 'default';
    
    if ($get_cpu_load === 'default')
        $cpu_load = 1000;
    
    if ($get_cpu_load === 'high')
        $cpu_load = 0;
    
    if ($get_cpu_load === 'medium')
        $cpu_load = 1000;
    
    if ($get_cpu_load === 'low')
        $cpu_load = 3000;
    
    return $cpu_load;
    
}