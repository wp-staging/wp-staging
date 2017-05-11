<?php

namespace WPStaging\Backend\Upgrade;

use WPStaging\WPStaging;
use WPStaging\Utils\Logger;

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

    /**
     * Clone data
     * @var obj 
     */
    private $clones;

    /**
     * Clone data
     * @var obj 
     */
    private $clonesBeta;

    /**
     * Logger
     * @var obj 
     */
    private $logger;

    public function __construct() {
        $this->previousVersion = preg_replace( '/[^0-9.].*/', '', get_option( 'wpstg_version' ) );
        // Original option
        $this->clones = get_option( "wpstg_existing_clones", array() );
        $this->clonesBeta = get_option( "wpstg_existing_clones_beta", array() );
        $this->logger = new Logger;
    }

    public function doUpgrade() {
        // Previous version lower than 2.0.2 or new install
        if( false === $this->previousVersion || version_compare( $this->previousVersion, '2.0.2', '<' ) ) {
            $this->upgradeOptions();
            $this->upgradeClonesBeta();
            $this->upgradeNotices();
        }
        //$this->setVersion();
    }

    /**
     * Upgrade routine for new install
     */
    private function upgradeOptions() {
        // Write some default vars
        add_option( 'wpstg_installDate', date( 'Y-m-d h:i:s' ) );
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

    /**
     * Create a new db option for beta version 2.0.2
     * @return bool
     */
    private function upgradeClonesBeta() {

        // Copy old data to new option
        //update_option( 'wpstg_existing_clones_beta', $this->clones );

        $new = array();

        if( empty( $this->clones ) ) {
            return false;
        }


        foreach ( $this->clones as $key => &$value ) {

            // Skip the rest of the loop if data is already compatible to wpstg 2.0.2
            if( isset( $value['directoryName'] ) || !empty( $value['directoryName'] ) ) {
                continue;
            }

            $new[$value]['directoryName'] = $value;
            $new[$value]['path'] = get_home_path() . $value;
            $new[$value]['url'] = get_home_url() . "/" . $value;
            $new[$value]['number'] = $key + 1;
            $new[$value]['version'] = $this->previousVersion;
        }
        unset( $value );

        if( empty( $new ) || false === update_option( 'wpstg_existing_clones_beta', $new ) ) {
            $this->logger->log( 'Failed to upgrade clone data from ' . $this->previousVersion . ' to ' . \WPStaging\WPStaging::VERSION );
        }
    }

    /**
     * Convert clone data from wpstg 1.x to wpstg 2.x 
     * Only use this later when wpstg 2.x is ready for production
     */
    private function upgradeClones() {

        $new = array();

        if( empty( $this->clones ) ) {
            return false;
        }

        foreach ( $this->clones as $key => &$value ) {

            // Skip the rest of the loop if data is already compatible to wpstg 2.0.1
            if( isset( $value['directoryName'] ) || !empty( $value['directoryName'] ) ) {
                continue;
            }
            $new[$value]['directoryName'] = $value;
            $new[$value]['path'] = get_home_path() . $value;
            $new[$value]['url'] = get_home_url() . "/" . $value;
            $new[$value]['number'] = $key + 1;
            $new[$value]['version'] = $this->previousVersion;
        }
        unset( $value );

        if( empty( $new ) || false === update_option( 'wpstg_existing_clones', $new ) ) {
            $this->logger->log( 'Failed to upgrade clone data from ' . $this->previousVersion . ' to ' . \WPStaging\WPStaging::VERSION );
        }
    }

    /**
     * Upgrade Notices Db options from wpstg 1.3 -> 2.0.1
     * Fix some logical db options
     */
    private function upgradeNotices() {
        $poll = get_option( "wpstg_start_poll", false );
        $beta = get_option( "wpstg_hide_beta", false );
        $rating = get_option( "wpstg_RatingDiv", false );

        if( $poll && $poll === "no" ) {
            update_option( 'wpstg_poll', 'no' );
        }
        if( $beta && $beta === "yes" ) {
            update_option( 'wpstg_beta', 'no' );
        }
        if( $rating && $rating === 'yes' ) {
            update_option( 'wpstg_rating', 'no' );
        }
    }

}
