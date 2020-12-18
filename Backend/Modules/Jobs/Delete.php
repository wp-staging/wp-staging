<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Backend\Modules\Jobs\Exceptions\CloneNotFoundException;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\DirectoryDeleter;

/**
 * Class Delete
 * @package WPStaging\Backend\Modules\Jobs
 */
class Delete extends Job {

    /**
     * @var \stdClass
     */
    private $clone = false;

    /**
     * The path to delete
     * @var string
     */
    private $deleteDir = '';

    /**
     * @var null|object
     */
    private $tables = null;

    /**
     * @var object|null
     */
    private $job = null;

    /**
     * @var bool
     */
    private $forceDeleteDirectories = false;

    /**
     *
     * @var object 
     */
    public $wpdb;

    /**
     *
     * @var bool
     */
    private $isExternal;

    public function __construct( $isExternal = false ) {
        parent::__construct();
        $this->isExternal = $isExternal;

        $this->deleteDir = !empty( $_POST['deleteDir'] ) ? urldecode( $_POST['deleteDir'] ) : '';
    }

    /**
     * Sets Clone and Table Records
     * @param null|array $clone
     * @return bool
     */
    public function setData($clone = null) 
    {
        if(!is_array($clone)) {
            $this->getCloneRecords();
        } else {
            $this->clone = (object) $clone;
            $this->forceDeleteDirectories = true;
        }

        if(!$this->isExternalDatabase()) {
            $this->wpdb = WPStaging::getInstance()->get("wpdb");
            $this->getTableRecords();
            return true; 
        }
        
        if ($this->isExternalDatabaseError()) {
            return false;
        }
       
        $this->wpdb = $this->getStagingDb();
        $this->getTableRecords();
        return true;
    }

    /**
     * Get database object to interact with
     */
    private function getStagingDb() {
        return new \wpdb( $this->clone->databaseUser, $this->clone->databasePassword, $this->clone->databaseDatabase, $this->clone->databaseServer );
    }

    /**
     * Date database name
     * @return string
     */
    public function getDbName() {
        return $this->wpdb->dbname;
    }

    /**
     * Check if external database is used
     * @return boolean
     */
    private function isExternalDatabase() {
        if( $this->isExternal ) {
            return true;
        }

        if( !empty( $this->clone->databaseUser ) ) {
            return true;
        }
        return false;
    }

    /**
     * Get clone
     * @param null|string $name
     * @throws CloneNotFoundException
     */
    private function getCloneRecords( $name = null ) {
        if( $name === null && !isset( $_POST["clone"] ) ) {
            $this->log( "Clone name is not set", Logger::TYPE_FATAL );
            $this->returnException( "Clone name is not set" );
        }

        if( $name === null ) {
            $name = (string)$_POST["clone"];
        }

        $clones = get_option( "wpstg_existing_clones_beta", [] );

        if( empty( $clones ) || !isset( $clones[$name] ) ) {
            $this->log( "Couldn't find clone name {$name} or no existing clone", Logger::TYPE_FATAL );
            $this->returnException( "Couldn't find clone name {$name} or no existing clone" );
        }

        $this->clone         = $clones[$name];
        $this->clone["name"] = $name;

        $this->clone = ( object ) $this->clone;

        unset( $clones );
    }

    /**
     * Get Tables
     */
    private function getTableRecords() {

        $stagingPrefix = $this->getStagingPrefix();

        // Escape "_" to allow searching for that character
        $prefix = wpstg_replace_last_match('_', '\_', $stagingPrefix);

        $tables = $this->wpdb->get_results( "SHOW TABLE STATUS LIKE '{$prefix}%'" );

        $this->tables = [];

        // no results
        if( $tables !== null ) {
            foreach ( $tables as $table ) {
                $this->tables[] = [
                    "name" => $table->Name,
                    "size" => $this->formatSize( ($table->Data_length + $table->Index_length ) )
                ];
            }
        }

        $this->tables = json_decode( json_encode( $this->tables ) );
    }

    /**
     * Check and return prefix of the staging site
     */
    public function getStagingPrefix() {

        if( $this->isExternalDatabase() && !empty( $this->clone->prefix ) ) {
            return $this->clone->prefix;
        }

        // Prefix not defined! Happens if staging site has been generated with older version of wpstg
        // Try to get staging prefix from wp-config.php of staging site
        if( empty( $this->clone->prefix ) ) {
            // Throw error
            $path    = ABSPATH . $this->clone->directoryName . "/wp-config.php";
            if( ($content = @file_get_contents( $path )) === false ) {
                $this->log( "Can not open {$path}. Can't read contents", Logger::TYPE_ERROR );
            } else {
                // Try to get prefix from wp-config.php
                preg_match( "/table_prefix\s*=\s*'(\w*)';/", $content, $matches );

                if( !empty( $matches[1] ) ) {
                    $this->clone->prefix = $matches[1];
                } else {
                    $this->returnException( "Fatal Error: Can not delete staging site. Can not find Prefix. '{$matches[1]}'. Stopping for security reasons. Creating a new staging site will likely resolve this the next time. Contact support@wp-staging.com" );
                }
            }
        }

        if( empty( $this->clone->prefix ) ) {
            $this->returnException( "Fatal Error: Can not delete staging site. Can not find table prefix. Contact support@wp-staging.com" );
        }

        // Check if staging prefix is the same as the live prefix
        if( empty( $this->options->databaseUser ) && $this->wpdb->prefix === $this->clone->prefix ) {
            $this->log( "Fatal Error: Can not delete staging site. Prefix. '{$this->clone->prefix}' is used for the live site. Creating a new staging site will likely resolve this the next time. Stopping for security reasons. Contact support@wp-staging.com" );
            $this->returnException( "Fatal Error: Can not delete staging site. Prefix. '{$this->clone->prefix}' is used for the live site. Creating a new staging site will likely resolve this the next time. Stopping for security reasons. Contact support@wp-staging.com" );
        }

        // Else
        return $this->clone->prefix;
    }

    /**
     * Format bytes into human readable form
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public function formatSize( $bytes, $precision = 2 ) {
        if( ( int ) $bytes < 1 ) {
            return '';
        }

        $units = ['B', "KB", "MB", "GB", "TB"];

        $bytes = ( int ) $bytes;
        $base  = log( $bytes ) / log( 1000 ); // 1024 would be for MiB KiB etc
        $pow   = pow( 1000, $base - floor( $base ) ); // Same rule for 1000

        return round( $pow, $precision ) . ' ' . $units[( int ) floor( $base )];
    }

    /**
     * @return false
     */
    public function getClone() {
        return $this->clone;
    }

    /**
     * @return null|object
     */
    public function getTables() {
        return $this->tables;
    }

    /**
     * Start Module
     * @param null|array $clone
     * @return bool
     */
    public function start( $clone = null ) {
        // Set data
        $this->setData( $clone );

        // Get the job first
        $this->getJob();

        $method = "delete" . ucwords( $this->job->current );
        return $this->{$method}();
    }

    /**
     * Get job data
     */
    private function getJob() {
        $this->job = $this->cache->get( "delete_job_{$this->clone->name}" );


        if( $this->job !== null ) {
            return;
        }

        // Generate JOB
        $this->job = ( object ) [
                    "current"               => "tables",
                    "nextDirectoryToDelete" => $this->clone->path,
                    "name"                  => $this->clone->name
        ];

        $this->cache->save( "delete_job_{$this->clone->name}", $this->job );
    }

    /**
     * @return bool
     */
    private function updateJob() {
        $this->job->nextDirectoryToDelete = trim( $this->job->nextDirectoryToDelete );
        return $this->cache->save( "delete_job_{$this->clone->name}", $this->job );
    }

    /**
     * @return array
     */
    private function getTablesToRemove() {
        $tables = $this->getTableNames();

        if( !isset( $_POST["excludedTables"] ) || !is_array( $_POST["excludedTables"] ) || empty( $_POST["excludedTables"] ) ) {
            return $tables;
        }

        return array_diff( $tables, $_POST["excludedTables"] );
    }

    /**
     * @return array
     */
    private function getTableNames() {
        return (!is_array( $this->tables )) ? [] : array_map( function($value) {
                    return ($value->name);
                }, $this->tables );
    }

    /**
     * Delete Tables
     */
    public function deleteTables() {
        if( $this->isOverThreshold() ) {
            $this->log( "Deleting: Is over threshold", Logger::TYPE_INFO );
            return;
        }

        $tables = $this->getTablesToRemove();

        foreach ( $tables as $table ) {
            // PROTECTION: Never delete any table that beginns with wp prefix of live site
            if( !$this->isExternalDatabase() && $this->startsWith( $table, $this->wpdb->prefix ) ) {
                $this->log( "Fatal Error: Trying to delete table {$table} of main WP installation!", Logger::TYPE_CRITICAL );
                return false;
            }

            $this->wpdb->query( "DROP TABLE {$table}" );
        }

        // Move on to the next
        $this->job->current = "directory";
        $this->updateJob();
    }

    /**
     * Check if a strings start with a specific string
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    protected function startsWith( $haystack, $needle ) {
        $length = strlen( $needle );
        return ($needle === substr( $haystack, 0, $length ));
    }

    /**
     *
     * Delete complete directory including all files and sub folders
     * @throws \Exception
     */
    public function deleteDirectory() 
    {
        if ($this->isFatalError()) {
            $this->returnException('Can not delete directory: ' . $this->deleteDir . '. This seems to be the root directory. Exclude this directory from deleting and try again.');
            throw new \Exception('Can not delete directory: ' . $this->deleteDir . ' This seems to be the root directory. Exclude this directory from deleting and try again.');
        }

        // Finished or path does not exist
        if (empty( $this->deleteDir ) ||
            $this->deleteDir == get_home_path() ||
            !is_dir( $this->deleteDir)) {

            $this->job->current = "finish";
            $this->updateJob();
            return $this->deleteFinish();
        }

        $this->log("Delete staging site: " . $this->clone->path, Logger::TYPE_INFO);

        // Make sure the root dir is never deleted!
        if($this->deleteDir == get_home_path()) {
            $this->log("Fatal Error 8: Trying to delete root of WP installation!", Logger::TYPE_CRITICAL);
            $this->returnException('Fatal Error 8: Trying to delete root of WP installation!');
        }

        // Check if threshold is reached
        if ($this->isOverThreshold()) {
            return;
        }

        if ($this->isNotEmpty($this->deleteDir)) {
            $fs = (new Filesystem())
                ->setShouldStop([$this, 'isOverThreshold'])
                ->setRecursive();
            if (!$fs->deleteNew($this->deleteDir)) {
                return;
            }
        }

        // Throw fatal error if the folder has still not been deleted and there are files in it
        if ($this->isNotEmpty($this->deleteDir)) {
            $clone = (string) $this->clone->path;
            $response = [
                'job'     => 'delete',
                'status'  => true,
                'delete'  => 'finished',
                'message' => "Could not delete the entire staging site. The folder {$clone} still exists and is not empty. <br/> Try to empty this folder manually by using FTP or file manager plugin and then try to delete again the staging site here.<br/> If this happens again please contact us at support@wp-staging.com",
                'error'   => true,
            ];
            wp_die(json_encode($response));
        }

        // Successful finish deleting job
        return $this->deleteFinish();
    }

    /**
     * Check if directory exists and is not empty
     * @param string $dir
     * @return bool
     */
    private function isNotEmpty( $dir ) {
        // Throw fatal error if the folder has still not been deleted and there are files in it
        $isDirNotEmpty = false;
        if( is_dir( $dir ) ) {
            $iterator      = new \FilesystemIterator( $dir );
            $isDirNotEmpty = $iterator->valid();
        }
        return $isDirNotEmpty;
    }

    /**
     * 
     * @return boolean
     */
    public function isFatalError() {
        $homePath = rtrim( get_home_path(), "/" );
        return $homePath == rtrim($this->deleteDir, "/");
    }

    /**
     * Finish / Update Existing Clones
     */
    public function deleteFinish() {

        $response = [
            'delete' => 'finished',
        ];

        $existingClones = get_option( "wpstg_existing_clones_beta", [] );

        // Check if clone exist and then remove it from options
        $this->log( "Verifying existing clones..." );
        foreach ( $existingClones as $name => $clone ) {
            if( $clone["path"] == $this->clone->path ) {
                unset( $existingClones[$name] );
            }
        }

        if( update_option( "wpstg_existing_clones_beta", $existingClones ) === false ) {
            $this->log( "Delete: Nothing to save.'" );
        }

        // Delete cached file
        $this->cache->delete( "delete_job_{$this->clone->name}" );
        $this->cache->delete( "delete_directories_{$this->clone->name}" );
        $this->cache->delete( "clone_options" );

        wp_die( json_encode( $response ) );
    }

    /**
     * Check if there is error in external database connection
     * can happen if the external database does not exist or stored credentials are wrong
     * @return bool
     * 
     * @todo replace it logic with DbInfo once collation check PR is merged.
     */
    private function isExternalDatabaseError() 
    {
        $db = new \mysqli($this->clone->databaseServer, $this->clone->databaseUser, $this->clone->databasePassword, $this->clone->databaseDatabase);
        if ($db->connect_error) {
            return true;
        }

        return false;
    }

}
