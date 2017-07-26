<?php

namespace WPStaging\Backend\Activation;

// No Direct Access
if( !defined( "WPINC" ) ) {
   die;
}

class Welcome {

   	public function __construct() {
		add_action( 'admin_init', array( $this, 'welcome'    ) );
	}

	

	/**
	 * Sends user to the welcome page on first activation of WPSTG as well as each
	 * time WPSTG is upgraded to a new version
	 *
	 * @access public
	 * @since 1.0.1
	 * @return void
	 */
	public function welcome() {
		// Bail if no activation redirect
		if ( false === get_transient( 'wpstg_activation_redirect' ) ){
			return;
                }

		// Delete the redirect transient
		delete_transient( 'wpstg_activation_redirect' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ){
			return;
                }                

               wp_safe_redirect( admin_url( 'admin.php?page=wpstg-welcome' ) ); exit;
	}
       

}
