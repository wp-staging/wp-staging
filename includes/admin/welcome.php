<?php
/**
 * Weclome Page Class
 *
 * @package     WPSTG
 * @subpackage  Admin/Welcome
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPSTG_Welcome Class
 *
 * A general class for About and Credits page.
 *
 * @since 1.0
 */
class WPSTG_Welcome {

	/**
	 * @var string The capability users should have to view the page
	 */
	public $minimum_capability = 'manage_options';

	/**
	 * Get things started
	 *
	 * @since 1.0.1
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'welcome'    ) );
	}

	

	/**
	 * Sends user to the Settings page on first activation of WPSTG as well as each
	 * time WPSTG is upgraded to a new version
	 *
	 * @access public
	 * @since 0.9.0
	 * @global $wpstg_options Array of all the WPSTG Options
	 * @return void
	 */
	public function welcome() {
		global $wpstg_options;

		// Bail if no activation redirect
		if ( ! get_transient( '_wpstg_activation_redirect' ) )
			return;

		// Delete the redirect transient
		delete_transient( '_wpstg_activation_redirect' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) )
			return;

		$upgrade = get_option( 'wpstg_version_upgraded_from' );
                
                //@since 0.9.0
		if( ! $upgrade ) { // First time install
			wp_safe_redirect( admin_url( 'admin.php?page=wpstg_clone' ) ); exit;
		} else { // Update
			wp_safe_redirect( admin_url( 'admin.php?page=wpstg_clone' ) ); exit;
		}
	}
}
new WPSTG_Welcome();
