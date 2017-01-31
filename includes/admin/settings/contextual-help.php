<?php
/**
 * Contextual Help
 *
 * @package     WPSTG
 * @subpackage  Admin/Settings
 * @copyright   Copyright (c) 2014, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Settings contextual help.
 *
 * @access      private
 * @since       1.0
 * @return      void
 */
function wpstg_settings_contextual_help() {
	$screen = get_current_screen();

	/*if ( $screen->id != 'wpstg-settings' )
		return;
*/
	$screen->set_help_sidebar(
		'<p><strong>' . $screen->id . sprintf( __( 'For more information:', 'wpstg' ) . '</strong></p>' .
		'<p>' . sprintf( __( 'Visit the <a href="%s">documentation</a> on the WP-Staging website.', 'wpstg' ), esc_url( 'https://www.wp-staging.net/' ) ) ) . '</p>' .
		'<p>' . sprintf(
					__( '<a href="%s">Post an issue</a> on <a href="%s">WP-Staging</a>. View <a href="%s">extensions</a>.', 'wpstg' ),
					esc_url( 'https://www.wp-staging.net/contact-support/' ),
					esc_url( 'https://www.wp-staging.net' ),
					esc_url( 'https://www.wp-staging.net/downloads' )
				) . '</p>'
	);

	$screen->add_help_tab( array(
		'id'	    => 'wpstg-settings-general',
		'title'	    => __( 'General', 'wpstg' ),
		'content'	=> '<p>' . __( 'This screen provides the most basic settings for configuring WP-Staging.', 'wpstg' ) . '</p>'
	) );


	

	do_action( 'wpstg_settings_contextual_help', $screen );
}
add_action( 'load-wpstg-settings', 'wpstg_settings_contextual_help' );
