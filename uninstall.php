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
// No direct access
if( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Get options
$options = json_decode( json_encode( get_option( "wpstg_settings", array() ) ) );

// No need to delete
if( isset( $options->unInstallOnDelete ) && '1' === $options->unInstallOnDelete ) {
    // Delete options
    delete_option( "wpstg_version_upgraded_from" );
    delete_option( "wpstg_version" );
    delete_option( "wpstg_installDate" );
    delete_option( "wpstg_firsttime" );
    delete_option( "wpstg_is_staging_site" );
    delete_option( "wpstg_settings" );
    delete_option( "wpstg_existing_clones" );
    // Old wpstg 1.3 options for admin notices
    delete_option( "wpstg_start_poll" );
    delete_option( "wpstg_hide_beta" );
    delete_option( "wpstg_RatingDiv" );
    // New ones
    delete_option( "wpstg_poll" );
    delete_option( "wpstg_rating" );
    delete_option( "wpstg_beta" );
}

