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
	global $wp_version;
        

	$js_dir  = WPSTG_PLUGIN_URL . 'assets/js/';
	$css_dir = WPSTG_PLUGIN_URL . 'assets/css/';

	// Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix  = '';//( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
        //echo $css_dir . 'wpstg-admin' . $suffix . '.css', WPSTG_VERSION;
	// These have to be global
	wp_enqueue_script( 'wpstg-admin-script', $js_dir . 'wpstg-admin' . $suffix . '.js', array( 'jquery' ), WPSTG_VERSION, false );
	wp_enqueue_style( 'wpstg-admin', $css_dir . 'wpstg-admin' . $suffix . '.css', WPSTG_VERSION );
	wp_localize_script( 'wpstg-admin-script', 'wpstg', array(
		'nonce' => wp_create_nonce( 'wpstg_ajax_nonce' )
	));
}
add_action( 'admin_enqueue_scripts', 'wpstg_load_admin_scripts', 100 );