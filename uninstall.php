<?php
/**
 * Uninstall WP-Staging
 *
 * @package     WPSTG
 * @subpackage  Uninstall
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Load WPSTG file
include_once( 'staging.php' );

global $wpdb, $wpstg_options;

if( wpstg_get_option( 'uninstall_on_delete' ) ) {
	/** Delete all the Plugin Options */
	delete_option( 'wpstg_settings' );
        delete_option( 'wpstg_install_date');
        delete_option( 'wpstg_rating_div');
        delete_option( 'wpstg_version');
        delete_option( 'wpstg_version_upgraded_from');
}
