<?php
/**
 * Admin Plugins
 *
 * @package     WPSTG
 * @subpackage  Admin/Plugins
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Plugins row action links
 *
 * @author Michael Cannon <mc@aihr.us>
 * @since 0.9.0
 * @param array $links already defined action links
 * @param string $file plugin file path and name being processed
 * @return array $links
 */
function wpstg_plugin_action_links( $links, $file ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=wpstg-settings' ) . '">' . esc_html__( 'General Settings', 'wpstg' ) . '</a>';
	if ( $file == 'wp-staging/wp-staging.php' )
		array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links', 'wpstg_plugin_action_links', 10, 2 );


/**
 * Plugin row meta links
 *
 * @author Michael Cannon <mc@aihr.us>
 * @since 2.0
 * @param array $input already defined meta links
 * @param string $file plugin file path and name being processed
 * @return array $input
 */
function wpstg_plugin_row_meta( $input, $file ) {
	if ( $file != 'wp-staging/wp-staging.php' )
		return $input;

	/*$links = array(
		'<a href="' . admin_url( 'options-general.php?page=wpstg-settings' ) . '">' . esc_html__( 'Getting Started', 'wpstg' ) . '</a>',
		'<a href="https://www.wp-staging.net/downloads/">' . esc_html__( 'Add Ons', 'wpstg' ) . '</a>',
	);*/
        
        $links = array(
		'<a href="' . admin_url( 'admin.php?page=wpstg-settings' ) . '">' . esc_html__( 'Getting Started', 'wpstg' ) . '</a>',
	);

	$input = array_merge( $input, $links );

	return $input;
}
add_filter( 'plugin_row_meta', 'wpstg_plugin_row_meta', 10, 2 );