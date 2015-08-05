<?php
/**
 * Uninstall WP-Staging
 *
 * @package     WPSTG
 * @subpackage  Uninstall
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.9.0
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Load WPSTG file
include_once( 'wp-staging.php' );

global $wpdb, $wpstg_options;

/** 
 * Delete all the Plugin Options 
 * 
 */
if( wpstg_get_option( 'uninstall_on_delete' ) ) {
	delete_option('wpstg_version_upgraded_from');
        delete_option('wpstg_version');
        delete_option('wpstg_installDate');
        delete_option('wpstg_RatingDiv');
        delete_option('wpstg_firsttime');
        delete_option('wpstg_is_staging_site');
        delete_option('wpstg_hide_beta');
        delete_option('wpstg_settings');
        delete_option( 'wpstg_existing_clones' );
}

