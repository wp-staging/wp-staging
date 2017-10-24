<?php

namespace WPStaging\Backend\Upgrade;

use WPStaging\WPStaging;
use WPStaging\Utils\Logger;

/**
 * Upgrade Class
 * This must be loaded on every page init to ensure all settings are 
 * adjusted correctly and to run any upgrade process if necessary.
 */

// No Direct Access
if( !defined( "WPINC" ) ) {
    die;
}

class Upgrade {

    /**
    * Previous Version number
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
    * Cron data
    * @var obj
    */
   private $cron;

   /**
     * Logger
     * @var obj 
     */
    private $logger;

    public function __construct() {
      
      // add wpstg_weekly_event to cron events
      $this->cron = new \WPStaging\Cron\Cron;
      
      // Previous version
        $this->previousVersion = preg_replace( '/[^0-9.].*/', '', get_option( 'wpstg_version' ) );
      
      // Options earlier than version 2.0.0
        $this->clones = get_option( "wpstg_existing_clones", array() );
      
      // Current options
        $this->clonesBeta = get_option( "wpstg_existing_clones_beta", array() );
      
      // Logger
        $this->logger = new Logger;
    }

    public function doUpgrade() {
      $this->upgrade2_0_3();
      //$this->upgrade2_0_4();
      $this->upgrade2_1_2();
      $this->setVersion();
   }

   /**
    * Upgrade method 2.0.3
    */
   public function upgrade2_0_3() {
        // Previous version lower than 2.0.2 or new install
        if( false === $this->previousVersion || version_compare( $this->previousVersion, '2.0.2', '<' ) ) {
            $this->upgradeOptions();
            $this->upgradeClonesBeta();
            $this->upgradeNotices();
        }
    }

    /**
    * Upgrade method 2.0.4
    */
//   public function upgrade2_0_4() {
//      if( false === $this->previousVersion || version_compare( $this->previousVersion, '2.0.4', '<' ) ) {
//
//      // Register cron job.
//      $this->cron->schedule_event();
//
//      // Install Optimizer 
//      $optimizer = new Optimizer();
//      $optimizer->installOptimizer();
//      }
//   }
    
       /**
    * Upgrade method 2.1.2
    * Sanitize the clone key value.
    */
   private function upgrade2_1_2(){
       if( false === $this->previousVersion || version_compare( $this->previousVersion, '2.1.7', '<' ) ) {
           foreach ( $this->clonesBeta as $key => $value){
               unset($this->clonesBeta[$key]);
               $this->clonesBeta[preg_replace("#\W+#", '-', strtolower($key))] = $value;
           }
          update_option('wpstg_existing_clones_beta', $this->clonesBeta);
       }
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
    * Upgrade Notices db options from wpstg 1.3 -> 2.0.1
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
