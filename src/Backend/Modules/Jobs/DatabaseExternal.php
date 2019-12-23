<?php

namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if( !defined( "WPINC" ) ) {
    die;
}

use WPStaging\WPStaging;
use WPStaging\Utils\Strings;
use WPStaging\Backend\Modules\Jobs\JobExecutable;

//use WPStaging\Utils\MySQL;
//use WPStaging\Utils\MySQLi;

/**
 * Class Database
 * @package WPStaging\Backend\Modules\Jobs
 */
class DatabaseExternal extends JobExecutable {

    /**
     * @var int
     */
    private $total = 0;

    /**
     * @var \WPDB
     */
    private $db;

    /**
     * Staging Database
     * @var \WPDB
     */
    private $stagingDb;

    /**
     * The database client
     * @var obj
     */
    //private $mysql;

    /**
     * Initialize
     */
    public function initialize() {
        $this->db        = WPStaging::getInstance()->get( "wpdb" );
        $this->stagingDb = $this->getExternalDBConnection();
        $this->total     = count( $this->options->tables );
        $this->isFatalError();

        // Get database client
//        if( empty( $this->db->use_mysqli ) ) {
//            $this->mysql = new MySQL( $this->db );
//        } else {
//            $this->mysql = new MySQLi( $this->db );
//        }
    }

    /**
     * Get external db object
     * @return mixed db | false
     */
    private function getExternalDBConnection() {

        $db = new \wpdb( $this->options->databaseUser, str_replace( "\\\\", "\\", $this->options->databasePassword ), $this->options->databaseDatabase, $this->options->databaseServer );

        // Can not connect to mysql
        if( !empty( $db->error->errors['db_connect_fail']['0'] ) ) {
            $this->returnException( "Can not connect to external database {$this->options->databaseDatabase}" );
            return false;
        }

        // Can not connect to database
        $db->select( $this->options->databaseDatabase );
        if( !$db->ready ) {
            $error = isset( $db->error->errors['db_select_fail'] ) ? $db->error->errors['db_select_fail'] : "Error: Can't select {database} Either it does not exist or you don't have privileges to access it.";
            $this->returnException( $error );
            exit;
        }


        return $db;
    }

    /**
     * Return fatal error and stops here if subfolder already exists
     * and mainJob is not updating the clone
     * @return boolean
     */
    private function isFatalError() {
        $path = trailingslashit($this->options->cloneDir);
        if( isset( $this->options->mainJob ) && $this->options->mainJob !== 'updating' && (is_dir( $path ) && !wpstg_is_empty_dir( $path ) ) ) {
            $this->returnException(" Can not continue for security purposes. Directory {$path} is not empty! Use FTP or a file manager plugin and make sure it does not contain any files. ");
        }
        return false;
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps() {
        $this->options->totalSteps = ($this->total === 0) ? 1 : $this->total;
    }

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     */
    protected function execute() {


        // Over limits threshold
        if( $this->isOverThreshold() ) {
            // Prepare response and save current progress
            $this->prepareResponse( false, false );
            $this->saveOptions();
            return false;
        }

        // No more steps, finished
        if (!$this->isRunning() || $this->options->currentStep > $this->total) {
            $this->prepareResponse( true, false );
            return false;
        }

        // Copy table
        if( isset( $this->options->tables[$this->options->currentStep] ) && !$this->copyTable( $this->options->tables[$this->options->currentStep] ) ) {
            // Prepare Response
            $this->prepareResponse( false, false );

            // Not finished
            return true;
        }

        // Prepare Response
        $this->prepareResponse();

        // Not finished
        return true;
    }

    /**
     * Get new prefix for the staging site
     * @return string
     */
    private function getStagingPrefix() {

        $this->options->prefix = !empty( $this->options->databasePrefix ) ? $this->options->databasePrefix : $this->db->prefix;

        return $this->options->prefix;
    }

    /**
     * No worries, SQL queries don't eat from PHP execution time!
     * @param string $tableName
     * @return bool
     */
    private function copyTable( $tableName ) {

        $strings      = new Strings();
        $tableName    = is_object( $tableName ) ? $tableName->name : $tableName;
        $newTableName = $this->getStagingPrefix() . $strings->str_replace_first( $this->db->prefix, null, $tableName );

        // Drop table if necessary
        $this->dropTable( $newTableName );

        // Save current job
        $this->setJob( $newTableName );

        // Beginning of the job
        if( !$this->startJob( $newTableName, $tableName ) ) {
            return true;
        }

        // Copy data
        $this->copyData( $newTableName, $tableName );

        // Finish the step
        return $this->finishStep();
    }

    /**
     * Set the job
     * @param string $table
     */
    private function setJob( $table ) {
        if( isset( $this->options->job->current ) ) {
            return;
        }

        $this->options->job->current = $table;
        $this->options->job->start   = 0;
    }

    /**
     * Start Job
     * @param string $new
     * @param string $old
     * @return bool
     */
    private function startJob( $new, $old ) {

        if( $this->isExcludedTable( $new ) ) {
            return false;
        }

        if( 0 != $this->options->job->start ) {
            return true;
        }

        // Table does not exist
        //$table  = $old;
        $result = $this->db->query( "SHOW TABLES LIKE '{$old}'" );
        if( !$result || 0 === $result ) {
            return true;
        }

        $this->log( "DB External Copy: CREATE table {$this->options->databaseDatabase}.{$new}" );
        $sql = $this->getCreateStatement( $old );

        // Replace table prefix to the new one
        $sql = str_replace( "CREATE TABLE `{$old}`", "CREATE TABLE `{$new}`", $sql );

        // Make constraint unique to prevent error:(errno: 121 "Duplicate key on write or update")
        $sql = wpstg_unique_constraint($sql);

        $this->stagingDb->query('SET FOREIGN_KEY_CHECKS=0;');

        if( false === $this->stagingDb->query( $sql ) ) {
            $this->returnException( "DB External Copy - Fatal Error: {$this->stagingDb->last_error} Query: {$sql}" );
        }

        // Count amount of tables to insert with next step
        $this->options->job->total = 0;
        $this->options->job->total = ( int ) $this->db->get_var( "SELECT COUNT(1) FROM `{$this->db->dbname}`.`{$old}`" );

        if( 0 == $this->options->job->total ) {
            $this->finishStep();
            return false;
        }

        return true;
    }

    /**
     *  Change create statements according to MySQL version
     *  @since 1.0.0
     */
//    private function adaptCreateStatement( $create ) {
//
//        $fromDbVersion = $this->stagingDb->get_var("SELECT VERSION()");
//
//        $toDbVersion = $this->db->get_var("SELECT VERSION()");
//
//        // If same version, all is good
//        if( version_compare( $fromDbVersion, $toDbVersion ) == 0 ) {
//            return $create;
//        }
//
//        // Change from unicode 5.2 (520) to "normal" utf8mb4 unicode on MySQL versions before 5.6
//        if( version_compare( $toDbVersion, '5.6', '<' ) ) {
//            $create = str_replace( 'utf8mb4_unicode_520_ci', 'utf8mb4_unicode_ci', $create );
//            $create = str_replace( 'utf8_unicode_520_ci', 'utf8_unicode_ci', $create );
//        }
//
//        return $create;
//    }

    /**
     * Get MySQL query create table
     *
     * @param  string $table_name Table name
     * @return array
     */
    private function getCreateStatement( $tableName ) {

        $row = $this->db->get_results( "SHOW CREATE TABLE `{$tableName}`", ARRAY_A );

        // Get create table
        if( isset( $row[0]['Create Table'] ) ) {
            return $row[0]['Create Table'];
        }
        return array();
    }

    /**
     * Copy data from old table to new table
     * @param string $new
     * @param string $old
     */
    private function copyData( $new, $old ) {

        $rows = $this->options->job->start + $this->settings->queryLimit;
        $this->log(
                "DB External Copy: INSERT {$this->db->dbname}.{$old} as {$this->options->databaseDatabase}.{$new} from {$this->options->job->start} to {$rows} records"
        );

        $limitation = '';

        if( 0 < ( int ) $this->settings->queryLimit ) {
            $limitation = " LIMIT {$this->settings->queryLimit} OFFSET {$this->options->job->start}";
        }

        // Get data from production site
        $rows = $this->db->get_results( "SELECT * FROM `{$old}` {$limitation}", ARRAY_A );

        // Start transaction
        $this->stagingDb->query( 'SET autocommit=0;' );
        $this->stagingDb->query( 'SET FOREIGN_KEY_CHECKS=0;');
        $this->stagingDb->query( 'START TRANSACTION;' );

        // Copy into staging site
        foreach ( $rows as $row ) {
            $escaped_values = $this->mysqlEscapeMimic( array_values( $row ) );
            $values         = implode( "', '", $escaped_values );
            $this->stagingDb->query( "INSERT INTO `{$new}` VALUES ('{$values}')" );
        }
        // Commit transaction
        $this->stagingDb->query( 'COMMIT;' );
        $this->stagingDb->query( 'SET autocommit=1;' );


        // Set new offset
        $this->options->job->start += $this->settings->queryLimit;
    }

    /**
     * Mimics the mysql_real_escape_string function. Adapted from a post by 'feedr' on php.net.
     * @link   http://php.net/manual/en/function.mysql-real-escape-string.php#101248
     * @access public
     * @param  string $input The string to escape.
     * @return string
     */
    private function mysqlEscapeMimic( $input ) {
        if( is_array( $input ) ) {
            return array_map( __METHOD__, $input );
        }
        if( !empty( $input ) && is_string( $input ) ) {
            return str_replace( array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $input );
        }

        return $input;
    }

    /**
     * Is table excluded from search replace processing?
     * @param string $table
     * @return boolean
     */
    private function isExcludedTable( $table ) {

        $customTables  = apply_filters( 'wpstg_clone_database_tables_exclude', array() );
        $defaultTables = array('blogs', 'blog_versions');

        $tables = array_merge( $customTables, $defaultTables );

        $excludedTables = array();
        foreach ( $tables as $key => $value ) {
            $excludedTables[] = $this->options->prefix . $value;
        }

        if( in_array( $table, $excludedTables ) ) {
            return true;
        }
        return false;
    }

    /**
     * Finish the step
     */
    private function finishStep() {
        // This job is not finished yet
        if( $this->options->job->total > $this->options->job->start ) {
            return false;
        }

        // Add it to cloned tables listing
        $this->options->clonedTables[] = isset( $this->options->tables[$this->options->currentStep] ) ? $this->options->tables[$this->options->currentStep] : false;

        // Reset job
        $this->options->job = new \stdClass();

        return true;
    }

    /**
     * Drop table if necessary
     * @param string $new
     */
    private function dropTable( $new ) {
        $old = $this->stagingDb->get_var( $this->stagingDb->prepare( "SHOW TABLES LIKE %s", $new ) );

        if( !$this->shouldDropTable( $new, $old ) ) {
            return;
        }

        $this->log( "DB External Copy: {$new} already exists, dropping it first" );
        $this->stagingDb->query( "SET FOREIGN_KEY_CHECKS=0" );
        $this->stagingDb->query( "DROP TABLE {$new}" );
        $this->stagingDb->query( "SET FOREIGN_KEY_CHECKS=1" );
    }

    /**
     * Check if table needs to be dropped
     * @param string $new
     * @param string $old
     * @return bool
     */
    private function shouldDropTable( $new, $old ) {



        if( $old === $new &&
                (
                !isset( $this->options->job->current ) ||
                !isset( $this->options->job->start ) ||
                0 == $this->options->job->start
                ) ) {
            return true;
        }
        return false;
    }

}
