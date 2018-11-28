<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Backend\Modules\Jobs\Exceptions\JobNotFoundException;
use WPStaging\WPStaging;
use WPStaging\Backend\Modules\Jobs\Multisite\Database as muDatabase;
use WPStaging\Backend\Modules\Jobs\Multisite\SearchReplace as muSearchReplace;
use WPStaging\Backend\Modules\Jobs\Multisite\Data as muData;
use WPStaging\Backend\Modules\Jobs\Multisite\Finish as muFinish;
use WPStaging\Backend\Modules\Jobs\Multisite\Directories as muDirectories;
use WPStaging\Backend\Modules\Jobs\Multisite\Files as muFiles;
use WPStaging\Utils\Helper;

/**
 * Class Cloning
 * @package WPStaging\Backend\Modules\Jobs
 */
class Updating extends Job {

    /**
     * External Database Used
     * @var bool 
     */
    public $isExternal;

    /**
     * Initialize is called in \Job
     */
    public function initialize() {
        $this->db = WPStaging::getInstance()->get( "wpdb" );
    }

    /**
     * Save Chosen Cloning Settings
     * @return bool
     */
    public function save() {
        if( !isset( $_POST ) || !isset( $_POST["cloneID"] ) ) {
            return false;
        }

        // Delete files to copy listing
        $this->cache->delete( "files_to_copy" );

        // Generate Options
        // Clone
        $this->options->clone               = $_POST["cloneID"];
        $this->options->cloneDirectoryName  = preg_replace( "#\W+#", '-', strtolower( $this->options->clone ) );
        $this->options->cloneNumber         = 1;
        $this->options->includedDirectories = array();
        $this->options->excludedDirectories = array();
        $this->options->extraDirectories    = array();
        $this->options->excludedFiles       = array(
            '.htaccess',
            '.DS_Store',
            '.git',
            '.svn',
            '.tmp',
            'desktop.ini',
            '.gitignore',
            '.log',
            'db.php',
            'object-cache.php',
            'web.config' // Important: Windows IIS configuartion file. Must not be in the staging site!

        );

        $this->options->excludedFilesFullPath = array(
            'wp-content' . DIRECTORY_SEPARATOR . 'db.php',
            'wp-content' . DIRECTORY_SEPARATOR . 'object-cache.php',
            'wp-content' . DIRECTORY_SEPARATOR . 'advanced-cache.php'
        );

        // Define mainJob to differentiate between cloning, updating and pushing
        $this->options->mainJob = 'updating';

        // Job
        $this->options->job = new \stdClass();

        // Check if clone data already exists and use that one
        if( isset( $this->options->existingClones[$this->options->clone] ) ) {
            $this->options->cloneNumber         = $this->options->existingClones[$this->options->clone]['number'];
            $this->options->databaseUser        = $this->options->existingClones[$this->options->clone]['databaseUser'];
            $this->options->databasePassword    = $this->options->existingClones[$this->options->clone]['databasePassword'];
            $this->options->databaseDatabase    = $this->options->existingClones[$this->options->clone]['databaseDatabase'];
            $this->options->databaseServer      = $this->options->existingClones[$this->options->clone]['databaseServer'];
            $this->options->databasePrefix      = $this->options->existingClones[$this->options->clone]['databasePrefix'];
            $this->options->destinationHostname = $this->options->existingClones[$this->options->clone]['url'];
            $this->options->prefix              = $this->getStagingPrefix();
            $helper                             = new Helper();
            $this->options->homeHostname        = $helper->get_home_url_without_scheme();
        } else {
            wp_die( 'Fatal Error: Can not update clone because there is no clone data.' );
        }

        $this->isExternal = (empty( $this->options->databaseUser ) && empty( $this->options->databasePassword )) ? false : true;

        // Included Tables
        if( isset( $_POST["includedTables"] ) && is_array( $_POST["includedTables"] ) ) {
            $this->options->tables = $_POST["includedTables"];
        } else {
            $this->options->tables = array();
        }

        // Excluded Directories
        if( isset( $_POST["excludedDirectories"] ) && is_array( $_POST["excludedDirectories"] ) ) {
            $this->options->excludedDirectories = $_POST["excludedDirectories"];
        }

        // Excluded Directories TOTAL
        // Do not copy these folders and plugins
        $excludedDirectories = array(
            ABSPATH . 'wp-content' . DIRECTORY_SEPARATOR . 'cache',
            ABSPATH . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wps-hide-login'
        );

        $this->options->excludedDirectories = array_merge( $excludedDirectories, $this->options->excludedDirectories );

        // Included Directories
        if( isset( $_POST["includedDirectories"] ) && is_array( $_POST["includedDirectories"] ) ) {
            $this->options->includedDirectories = $_POST["includedDirectories"];
        }

        // Extra Directories
        if( isset( $_POST["extraDirectories"] ) && !empty( $_POST["extraDirectories"] ) ) {
            $this->options->extraDirectories = $_POST["extraDirectories"];
        }

        $this->options->cloneDir = '';
        if( isset( $_POST["cloneDir"] ) && !empty( $_POST["cloneDir"] ) ) {
            $this->options->cloneDir = trailingslashit( $_POST["cloneDir"] );
        }

        //$this->options->destinationDir = !empty( $this->options->cloneDir ) ? trailingslashit( $this->options->cloneDir ) : trailingslashit( $this->options->existingClones[$this->options->clone]['path'] );

        $this->options->destinationDir = $this->getDestinationDir();

        $this->options->cloneHostname = '';
        if( isset( $_POST["cloneHostname"] ) && !empty( $_POST["cloneHostname"] ) ) {
            $this->options->cloneHostname = $_POST["cloneHostname"];
        }
        
        $this->options->destinationHostname = $this->getDestinationHostname();


        // Directories to Copy
        $this->options->directoriesToCopy = array_merge(
                $this->options->includedDirectories, $this->options->extraDirectories
        );

        array_unshift( $this->options->directoriesToCopy, ABSPATH );

        return $this->saveOptions();
    }

    /**
     * Return target hostname
     * @return string
     */
    private function getDestinationHostname() {
        if( empty( $this->options->cloneHostname ) ) {
            $helper = new Helper();
            return $helper->get_home_url_without_scheme();
        }
        return $this->getHostnameWithoutScheme( $this->options->cloneHostname );
    }

    /**
     * Get Destination Directory including staging subdirectory
     * @return type
     */
    private function getDestinationDir() {
        if( empty( $this->options->cloneDir ) ) {
            return trailingslashit( \WPStaging\WPStaging::getWPpath() . $this->options->cloneDirectoryName );
        }
        //return trailingslashit( $this->options->cloneDir . $this->options->cloneDirectoryName );
        return trailingslashit( $this->options->cloneDir );
    }

    /**
     * Check and return prefix of the staging site
     */
    public function getStagingPrefix() {
        // prefix not defined! Happens if staging site has ben generated with older version of wpstg
        // Try to get staging prefix from wp-config.php of staging site
        $this->options->prefix = $this->options->existingClones[$this->options->clone]['prefix'];
        if( empty( $this->options->prefix ) ) {
            // Throw error if wp-config.php is not readable 
            $path    = ABSPATH . $this->options->cloneDirectoryName . "/wp-config.php";
            if( false === ($content = @file_get_contents( $path )) ) {
                $this->log( "Can not open {$path}. Can't read contents", Logger::TYPE_ERROR );
                $this->returnException( "Fatal Error: Can not read {$path} to get correct table prefix. Stopping for security reasons. Deleting this staging site and creating a new one could fix this issue. Otherwise contact us support@wp-staging.com" );
                wp_die( "Fatal Error: Can not read {$path} to get correct table prefix. Stopping for security reasons. Deleting this staging site and creating a new one could fix this issue. Otherwise contact us support@wp-staging.com" );
            } else {
                // Get prefix from wp-config.php
                preg_match( "/table_prefix\s*=\s*'(\w*)';/", $content, $matches );

                if( !empty( $matches[1] ) ) {
                    $this->options->prefix = $matches[1];
                } else {
                    $this->returnException( "Fatal Error: Can not detect prefix from {$path}. Stopping for security reasons. Deleting this staging site and creating a new one could fix this issue. Otherwise contact us support@wp-staging.com" );
                    wp_die( "Fatal Error: Can not detect prefix from {$path}. Stopping for security reasons. Deleting this staging site and creating a new one could fix this issue. Otherwise contact us support@wp-staging.com" );
                }
            }
        }

        // Die() if staging prefix is the same as the live prefix
        if( false === $this->isExternal && $this->db->prefix == $this->options->prefix ) {
            $this->log( "Fatal Error: Can not update staging site. Prefix. '{$this->options->prefix}' is used for the live site. Stopping for security reasons. Deleting this staging site and creating a new one could fix this issue. Otherwise contact us support@wp-staging.com" );
            wp_die( "Fatal Error: Can not update staging site. Prefix. '{$this->options->prefix}' is used for the live site. Stopping for security reasons. Deleting this staging site and creating a new one could fix this issue. Otherwise contact us support@wp-staging.com" );
        }

        // Else
        return $this->options->prefix;
    }

    /**
     * Start the cloning job
     */
    public function start() {
        if( null === $this->options->currentJob ) {
            $this->log( "Cloning job for {$this->options->clone} finished" );
            return true;
        }

        $methodName = "job" . ucwords( $this->options->currentJob );

        if( !method_exists( $this, $methodName ) ) {
            $this->log( "Can't execute job; Job's method {$methodName} is not found" );
            throw new JobNotFoundException( $methodName );
        }

        // Call the job
        //$this->log("execute job: Job's method {$methodName}");
        return $this->{$methodName}();
    }

    /**
     * @param object $response
     * @param string $nextJob
     * @return object
     */
    private function handleJobResponse( $response, $nextJob ) {
        // Job is not done
        if( true !== $response->status ) {
            return $response;
        }

        $this->options->currentJob  = $nextJob;
        $this->options->currentStep = 0;
        $this->options->totalSteps  = 0;

        // Save options
        $this->saveOptions();

        return $response;
    }

    /**
     * Clone Database
     * @return object
     */
//   public function jobDatabase() {
//      if( is_multisite() ) {
//         $database = new muDatabase();
//      } else {
//         $database = new Database();
//      }
//      return $this->handleJobResponse( $database->start(), "directories" );
//   }

    /**
     * Clone Database
     * @return object
     */
    public function jobDatabase() {
        if( is_multisite() ) {
            $database = new muDatabase();
        } else {
            $database = new Database();
        }
        return $this->handleJobResponse( $database->start(), "directories" );
    }

    /**
     * Get All Files From Selected Directories Recursively Into a File
     * @return object
     */
    public function jobDirectories() {
        if( is_multisite() ) {
            $directories = new muDirectories();
        } else {
            $directories = new Directories();
        }
        return $this->handleJobResponse( $directories->start(), "files" );
    }

    /**
     * Copy Files
     * @return object
     */
    public function jobFiles() {
        if( is_multisite() ) {
            $files = new muFiles();
        } else {
            $files = new Files();
        }
        return $this->handleJobResponse( $files->start(), "data" );
    }

    /**
     * Replace Data
     * @return object
     */
    public function jobData() {
        if( is_multisite() ) {
            $data = new muData();
        } else {
            $data = new Data();
        }
        return $this->handleJobResponse( $data->start(), "finish" );
    }

    /**
     * Save Clone Data
     * @return object
     */
    public function jobFinish() {
        if( is_multisite() ) {
            $finish = new muFinish();
        } else {
            $finish = new Finish();
        }
        $finish = new Finish();
        return $this->handleJobResponse( $finish->start(), '' );
    }

}
