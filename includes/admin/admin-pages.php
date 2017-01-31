<?php
/**
 * Admin Pages
 *
 * @package     WPSTG
 * @subpackage  Admin/Pages
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.9.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Creates the admin submenu pages under the WP-Staging menu and assigns their
 * links to global variables
 *
 * @since 1.0
 * @global $wpstg_settings_page
 * @global $wpstg_add_ons_page
 * @global $wpstg_tools_page
 * @return void
 */
function wpstg_add_options_link() {
	global $wpstg_parent_page, $wpstg_add_ons_page, $wpstg_add_ons_page2, $wpstg_settings_page, $wpstg_tools_page, $wpstg_clone_page;
        $wpstg_parent_page   = add_menu_page( 'WP-Staging', __( 'WP Staging', 'wpstg' ), 'manage_options', 'wpstg_clone', 'wpstg_clone_page', 'dashicons-hammer' );
	$wpstg_clone_page = add_submenu_page('wpstg_clone', __('WP Staging Jobs', 'wpstg'), __('Start', 'wpstg'), 'manage_options', 'wpstg_clone', 'wpstg_clone_page');
        $wpstg_settings_page = add_submenu_page( 'wpstg_clone', __( 'WP Staging Settings', 'wpstg' ), __( 'Settings', 'wpstg' ), 'manage_options', 'wpstg-settings', 'wpstg_options_page' );
        $wpstg_tools_page = add_submenu_page( 'wpstg_clone', __( 'WP Staging Tools', 'wpstg' ), __( 'Tools', 'wpstg' ), 'manage_options', 'wpstg-tools', 'wpstg_tools_page' );

}
add_action( 'admin_menu', 'wpstg_add_options_link', 10 );

/**
 *  Determines whether the current admin page is an WPSTG admin page.
 *  
 *  Only works after the `wp_loaded` hook, & most effective 
 *  starting on `admin_menu` hook.
 *  
 *  @since 0.9.0
 *  @return bool True if WPSTG admin page.
 */
function wpstg_is_admin_page() {
        $currentpage = isset($_GET['page']) ? $_GET['page'] : '';
	if ( ! is_admin() || ! did_action( 'wp_loaded' ) ) {
		return false;
	}
	
	global $wpstg_parent_page, $pagenow, $typenow, $wpstg_settings_page, $wpstg_add_ons_page, $wpstg_tools_page, $wpstg_clone_page;

	if ( 'wpstg-settings' == $currentpage || 'wpstg-addons' == $currentpage || 'wpstg-tools' == $currentpage || 'wpstg-clone' == $currentpage || 'wpstg_clone' == $currentpage) {
                //mashdebug()->info("wpstg_is_admin_page() = true");
		return true;      
	}     
}
