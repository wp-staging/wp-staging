<?php

namespace WPStaging\Backend\Upgrade;

use WPStaging\WPStaging;


/**
 * Upgrade Class
 */

// No Direct Access
if( !defined( "WPINC" ) ) {
    die;
}

class Upgrade {

    /**
     * Previous Version
     * @var string 
     */
    private $previousVersion;

    public function __construct(){
        $this->previousVersion =  preg_replace( '/[^0-9.].*/', '', get_option( 'wpstg_version' ) );
    }
    
    public function doUpgrade() {
        // Previous version lower than 2.0.0 or new install
        if( false === $this->previousVersion || version_compare( $this->previousVersion, '2.0.0', '<' ) ) {
            echo 'lets upgrade';
        }
        $this->setVersion();
    }

    /**
     * Write new version number into db
     * return bool
     */
    private function setVersion() {
        // Check if version number in DB is lower than version number in current plugin
        if( version_compare( $this->previousVersion, \WPStaging\WPStaging::VERSION, '<' ) ) {
            // Update Version number
            update_option( 'wpstg_version', preg_replace( '/[^0-9.].*/', '', \WPStaging\WPStaging::VERSION ) );
            // Update "upgraded from" version number
            update_option( 'wpstg_version_upgraded_from', preg_replace( '/[^0-9.].*/', '', $this->previousVersion ) );
            
            return true;
        } 
        return false;
    }

}
