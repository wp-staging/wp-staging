<?php
/**
 * Admin Footer
 *
 * @package     WPSTG
 * @subpackage  Admin/Footer
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

/**
 * Add rating links to the admin dashboard
 *
 * @since	0.9.0
 * @global	string $typenow
 * @param       string $footer_text The existing footer text
 * @return      string
 */
function wpstg_admin_rate_us( $footer_text ) {
	global $typenow;

	if ( wpstg_is_admin_page() ) {

		/*$rate_text = sprintf( __( 'Thank you for using <a href="%1$s" target="_blank">WP Staging</a>! Please <a href="%2$s" target="_blank">rate WP Staging</a> on <a href="%2$s" target="_blank">WordPress.org</a> and help to support this project.<br>Something not working as expected with WP Staging? Read the <a href="https://www.wp-staging.net/faq/" target="blank">FAQ</a> and visit the WP-Staging <a href="https://wp-staging.net/support" target="blank">Support Forum</a>', 'wpstg' ),
			'https://www.wp-staging.net',
			'http://wordpress.org/support/view/plugin-reviews/wp-staging?filter=5#postform'
		);*/
                $rate_text = sprintf( __( 'Please <a href="%1$s" target="_blank">rate WP Staging</a> and help to support this project.<br>Something not working as expected? Visit the WP Staging <a href="https://wordpress.org/support/plugin/wp-staging" target="blank">Support Forum</a>', 'wpstg' ),
			'http://wordpress.org/support/view/plugin-reviews/wp-staging?filter=5#postform'
		);

		return str_replace( '</span>', '', '' ) . $rate_text . '</span>';
	} else {
		return $footer_text;
	}
}
add_filter( 'admin_footer_text', 'wpstg_admin_rate_us' );