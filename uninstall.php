<?php

namespace WPStaging\Backend;

use WPStaging\Backend\Optimizer\Optimizer;

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

class uninstall {

    public function __construct() {

        // Plugin Folder Path
        if( !defined( 'WPSTG_PLUGIN_DIR' ) ) {
            define( 'WPSTG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        }

        /**
         * Path to main WP Staging class
         * Make sure to not redeclare class in case free version has been installed previosly
         */
        if( !class_exists( 'WPStaging\WPStaging' ) ) {
            require_once plugin_dir_path( __FILE__ ) . "apps/Core/WPStaging.php";
        }
        $wpStaging = \WPStaging\WPStaging::getInstance();

        // Delete our must use plugin
        $this->deleteMuPlugin();

        $this->init();
    }

    private function init() {

        $options = json_decode( json_encode( get_option( "wpstg_settings", array() ) ) );

        if( isset( $options->unInstallOnDelete ) && '1' === $options->unInstallOnDelete ) {
            // Delete options
            delete_option( "wpstg_version_upgraded_from" );
            delete_option( "wpstg_version" );
            delete_option( "wpstg_installDate" );
            delete_option( "wpstg_firsttime" );
            delete_option( "wpstg_is_staging_site" );
            // Do not delete main wpstg_settings any longer. 
            // People forget that this removes their staging sites from the list and ask us often to restore it for them
            //delete_option( "wpstg_settings" );
            delete_option( "wpstg_rmpermalinks_executed" );
            delete_option( "wpstg_activation_redirect" );

            /* Do not delete these fields without actually deleting the staging site
             * @create a delete routine which deletes the staging sites first 
             */
            //delete_option( "wpstg_existing_clones" );
            //delete_option( "wpstg_existing_clones_beta" );
            // Old wpstg 1.3 options for admin notices
            delete_option( "wpstg_start_poll" );
            delete_option( "wpstg_hide_beta" );
            delete_option( "wpstg_RatingDiv" );

            // New 2.x options for admin notices
            delete_option( "wpstg_poll" );
            delete_option( "wpstg_rating" );
            delete_option( "wpstg_beta" );

            // Delete events
            wp_clear_scheduled_hook( 'wpstg_weekly_event' );
        }
    }

    /**
     * delete MuPlugin
     */
    private function deleteMuPlugin() {
        $muDir       = ( defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
        $destination = trailingslashit( $muDir ) . 'wp-staging-optimizer.php';
        if( file_exists( $destination ) && !unlink( $destination ) ) {
            return false;
        }

        //$optimizer = new Optimizer;
        //$optimizer->unstallOptimizer();
    }

}

new uninstall();
