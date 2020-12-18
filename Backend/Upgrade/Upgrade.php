<?php

namespace WPStaging\Backend\Upgrade;

use WPStaging\Core\WPStaging;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\Utils\IISWebConfig;
use WPStaging\Core\Utils\Htaccess;
use WPStaging\Core\Utils\Filesystem;

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
     * @var object
     */
    private $clones;

    /**
     * Global settings
     * @var object
     */
    private $settings;

    /**
     * Cron data
     * @var object
     */
    private $cron;

    /**
     * Logger
     * @var object
     */
    private $logger;

    /**
     * db object
     * @var object
     */
    private $db;

    public function __construct() {

        // add wpstg_weekly_event to cron events
        $this->cron = new \WPStaging\Core\Cron\Cron;

        // Previous version
        $this->previousVersion = preg_replace( '/[^0-9.].*/', '', get_option( 'wpstg_version' ) );

        $this->settings = ( object ) get_option( "wpstg_settings", [] );

        // Logger
        $this->logger = new Logger;

        // db
        $this->db = WPStaging::getInstance()->get( "wpdb" );
    }

    public function doUpgrade() {
        $this->upgrade2_0_3();
        $this->upgrade2_1_2();
        $this->upgrade2_2_0();
        $this->upgrade2_4_4();
        $this->upgrade2_5_9();

        $this->setVersion();
    }

    /**
     * Fix array keys of staging sites
     */
    private function upgrade2_5_9() {
        // Previous version lower than 2.5.9
        if( version_compare( $this->previousVersion, '2.5.9', '<' ) ) {

            // Current options
            $sites = get_option( "wpstg_existing_clones_beta", [] );

            $new = [];

            // Fix keys. Replace white spaces with dash character
            foreach ( $sites as $oldKey => $site ) {
                $key       = preg_replace( "#\W+#", '-', strtolower( $oldKey ) );
                $new[$key] = $sites[$oldKey];
            }
            update_option( "wpstg_existing_clones_beta", $new );
        }
    }

    private function upgrade2_4_4() {
        // Previous version lower than 2.4.4
        if( version_compare( $this->previousVersion, '2.4.4', '<' ) ) {
            // Add htaccess to wp staging uploads folder
            $htaccess = new Htaccess();
            $htaccess->create( trailingslashit( \WPStaging\Core\WPStaging::getContentDir() ) . '.htaccess' );
            $htaccess->create( trailingslashit( \WPStaging\Core\WPStaging::getContentDir() ) . 'logs/.htaccess' );

            // Add litespeed htaccess to wp root folder
            if( extension_loaded( 'litespeed' ) ) {
                $htaccess->createLitespeed( ABSPATH . '.htaccess' );
            }

            // Create empty index.php in wp staging uploads folder
            $filesystem = new Filesystem();
            $filesystem->create( trailingslashit( \WPStaging\Core\WPStaging::getContentDir() ) . 'index.php', "<?php // silence" );
            $filesystem->create( trailingslashit( \WPStaging\Core\WPStaging::getContentDir() ) . 'logs/index.php', "<?php // silence" );

            // create web.config file for IIS in wp staging uploads folder
            $webconfig = new IISWebConfig();
            $webconfig->create( trailingslashit( \WPStaging\Core\WPStaging::getContentDir() ) . 'web.config' );
            $webconfig->create( trailingslashit( \WPStaging\Core\WPStaging::getContentDir() ) . 'logs/web.config' );
        }
    }

    /**
     * Upgrade method 2.2.0
     */
    public function upgrade2_2_0() {
        // Previous version lower than 2.2.0
        if( version_compare( $this->previousVersion, '2.2.0', '<' ) ) {
            $this->upgradeElements();
        }
    }

    /**
     * Add missing elements
     */
    private function upgradeElements() {
        // Current options
        $sites = get_option( "wpstg_existing_clones_beta", [] );

        if( $sites === false || count( $sites ) === 0 ) {
            return;
        }

        // Check if key prefix is missing and add it
        foreach ( $sites as $key => $value ) {
            if( empty( $sites[$key]['directoryName'] ) ) {
                continue;
            }
            //!empty( $sites[$key]['prefix'] ) ? $sites[$key]['prefix'] = $value['prefix'] : $sites[$key]['prefix'] = $key . '_';        
            !empty( $sites[$key]['prefix'] ) ?
                            $sites[$key]['prefix'] = $value['prefix'] :
                            $sites[$key]['prefix'] = $this->getStagingPrefix( $sites[$key]['directoryName'] );
        }

        if( !empty( $sites ) ) {
            update_option( 'wpstg_existing_clones_beta', $sites );
        }
    }

    /**
     * Check and return prefix of the staging site
     * @param string $directory
     * @return string
     */
    private function getStagingPrefix( $directory ) {
        // Try to get staging prefix from wp-config.php of staging site

        $path    = ABSPATH . $directory . "/wp-config.php";
        if( ($content = @file_get_contents( $path )) === false ) {
            $prefix = "";
        } else {
            // Get prefix from wp-config.php
            preg_match( "/table_prefix\s*=\s*'(\w*)';/", $content, $matches );

            if( !empty( $matches[1] ) ) {
                $prefix = $matches[1];
            } else {
                $prefix = "";
            }
        }
        // return result: Check if staging prefix is the same as the live prefix
        if( $this->db->prefix != $prefix ) {
            return $prefix;
        } else {
            return "";
        }
    }

    /**
     * NEW INSTALLATION
     * Upgrade method 2.0.3
     */
    public function upgrade2_0_3() {
        // Previous version lower than 2.0.2 or new install
        if( $this->previousVersion === false || version_compare( $this->previousVersion, '2.0.2', '<' ) ) {
            $this->upgradeOptions();
            $this->upgradeNotices();
        }
    }

    /**
     * Upgrade method 2.1.2
     * Sanitize the clone key value.
     */
    private function upgrade2_1_2() {

        // Current options
        $clonesBeta = get_option( "wpstg_existing_clones_beta", [] );

        if( $this->previousVersion === false || version_compare( $this->previousVersion, '2.1.7', '<' ) ) {
            foreach ($clonesBeta as $key => $value ) {
                unset( $clonesBeta[$key] );
                $clonesBeta[preg_replace( "#\W+#", '-', strtolower( $key ) )] = $value;
            }
            if( empty($clonesBeta) ) {
                return;
            }

            update_option( 'wpstg_existing_clones_beta', $clonesBeta);
        }
    }

    /**
     * Upgrade routine for new install
     */
    private function upgradeOptions() {
        // Write some default vars
        add_option( 'wpstg_installDate', date( 'Y-m-d h:i:s' ) );
        $this->settings->optimizer = 1;
        update_option( 'wpstg_settings', $this->settings );
    }

    /**
     * Write new version number into db
     * return bool
     */
    private function setVersion() {
        // Check if version number in DB is lower than version number in current plugin
        if( version_compare( $this->previousVersion, WPStaging::getVersion(), '<' ) ) {
            // Update Version number
            update_option( 'wpstg_version', preg_replace( '/[^0-9.].*/', '', WPStaging::getVersion() ) );
            // Update "upgraded from" version number
            update_option( 'wpstg_version_upgraded_from', preg_replace( '/[^0-9.].*/', '', $this->previousVersion ) );

            return true;
        }
        return false;
    }

    /**
     * Upgrade Notices db options from wpstg 1.3 -> 2.0.1
     * Fix some logical db options
     */
    private function upgradeNotices() {
        $poll   = get_option( "wpstg_start_poll", false );
        $beta   = get_option( "wpstg_hide_beta", false );
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
