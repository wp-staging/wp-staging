<?php

namespace WPStaging\Backend\Modules\Jobs\Multisite;

// No Direct Access
if( !defined( "WPINC" ) ) {
   die;
}

use WPStaging\WPStaging;
use WPStaging\Utils\Strings;
use WPStaging\Backend\Modules\Jobs\JobExecutable;

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
    * Initialize
    */
   public function initialize() {
      $this->db = WPStaging::getInstance()->get( "wpdb" );
      $this->stagingDb = $this->getExternalDBConnection();
      $this->getTables();
      // Add 2 to total table count because we need to copy two more tables from the main multisite installation wp_users and wp_usermeta
      $this->total = count( $this->options->tables ) + 2;
      $this->isFatalError();
   }

   /**
    * Get external db object
    * @return mixed db | false
    */
   private function getExternalDBConnection() {

      $db = new \wpdb( $this->options->databaseUser, $this->options->databasePassword, $this->options->databaseDatabase, $this->options->databaseServer );

      // Can not connect to mysql
      if( !empty( $db->error->errors['db_connect_fail']['0'] ) ) {
         $this->returnException( "Can not connect to external database {$this->options->databaseDatabase}" );
         return false;
      }

      // Can not connect to database
      $sql = "SHOW DATABASES LIKE '{$this->options->databaseDatabase}';";
      $results = $db->query( $sql );
      if( empty( $results ) ) {
         $this->returnException( "Can not connect to external database {$this->options->databaseDatabase}" );
         return false;
      }
      return $db;
   }

   private function getTables() {
      // Add wp_users and wp_usermeta to the tables if they do not exist
      // because they are not available in MU installation but we need them on the staging site

      if( !in_array( $this->db->prefix . 'users', $this->options->tables ) ) {
         array_push( $this->options->tables, $this->db->prefix . 'users' );
         $this->saveOptions();
      }
      if( !in_array( $this->db->prefix . 'usermeta', $this->options->tables ) ) {
         array_push( $this->options->tables, $this->db->prefix . 'usermeta' );
         $this->saveOptions();
      }
   }

   /**
    * Return fatal error and stops here if subfolder already exists
    * and mainJob is not updating the clone 
    * @return boolean
    */
   private function isFatalError() {
      $path = trailingslashit( get_home_path() ) . $this->options->cloneDirectoryName;
      if( isset( $this->options->mainJob ) && $this->options->mainJob !== 'updating' && is_dir( $path ) ) {
         $this->returnException( " Can not continue! Change the name of the clone or delete existing folder. Then try again. Folder already exists: " . $path );
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
      if( $this->options->currentStep > $this->total ) {
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

//      $this->copyWpUsers();
//
//      $this->copyWpUsermeta();
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

      $strings = new Strings();
      $tableName = is_object( $tableName ) ? $tableName->name : $tableName;
      $newTableName = $this->getStagingPrefix() . $strings->str_replace_first( $this->db->prefix, null, $tableName );

      // Get wp_users from main site
      if( 'users' === $strings->str_replace_first( $this->db->prefix, null, $tableName ) ) {
         $tableName = $this->db->base_prefix . 'users';
      }
      // Get wp_usermeta from main site
      if( 'usermeta' === $strings->str_replace_first( $this->db->prefix, null, $tableName ) ) {
         $tableName = $this->db->base_prefix . 'usermeta';
      }

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
    * Copy multisite global user table wp_users to wpstgX_users
    * @return bool
    */
//   private function copyWpUsers() {
//      $strings = new Strings();
//      $tableName = $this->db->base_prefix . 'users';
//      $newTableName = $this->getStagingPrefix() . $strings->str_replace_first( $this->db->base_prefix, null, $tableName );
//
//      // Drop table if necessary
//      $this->dropTable( $newTableName );
//
//      // Save current job
//      $this->setJob( $newTableName );
//
//      // Beginning of the job
//      if( !$this->startJob( $newTableName, $tableName ) ) {
//         return true;
//      }
//
//      // Copy data
//      $this->copyData( $newTableName, $tableName );
//
//      // Finish the step
//      return $this->finishStep();
//   }

   /**
    * Copy multisite global user table wp_usermeta to wpstgX_users
    * @return bool
    */
//   private function copyWpUsermeta() {
//      $strings = new Strings();
//      $tableName = $this->db->base_prefix . 'usermeta';
//      $newTableName = $this->getStagingPrefix() . $strings->str_replace_first( $this->db->base_prefix, null, $tableName );
//
//      // Drop table if necessary
//      $this->dropTable( $newTableName );
//
//      // Save current job
//      $this->setJob( $newTableName );
//
//      // Beginning of the job
//      if( !$this->startJob( $newTableName, $tableName ) ) {
//         return true;
//      }
//      // Copy data
//      $this->copyData( $newTableName, $tableName );
//
//      // Finish the step
//      return $this->finishStep();
//   }

   /**
    * Copy data from old table to new table
    * @param string $new
    * @param string $old
    */
   private function copyData( $new, $old ) {
      $old = $this->db->dbname . '.' . $old;
      $new = $this->options->databaseDatabase . '.' . $new;


      $rows = $this->options->job->start + $this->settings->queryLimit;
      $this->log(
              "DB Copy: {$old} as {$new} from {$this->options->job->start} to {$rows} records"
      );

      $limitation = '';

      if( 0 < ( int ) $this->settings->queryLimit ) {
         $limitation = " LIMIT {$this->settings->queryLimit} OFFSET {$this->options->job->start}";
      }

      $this->stagingDb->query(
              "INSERT INTO {$new} SELECT * FROM {$old} {$limitation}"
      );

      // Set new offset
      $this->options->job->start += $this->settings->queryLimit;
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
      $this->options->job->start = 0;
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

      $this->options->job->total = 0;

      $new = $this->options->databaseDatabase . '.' . $new;
      $old = $this->db->dbname . '.' . $old;

      if( 0 != $this->options->job->start ) {
         return true;
      }

      // Table does not exists
      $table = str_replace( $this->db->dbname . '.', null, $old );
      $result = $this->db->query( "SHOW TABLES LIKE '{$table}'" );
      if( !$result || 0 === $result ) {
         return true;
      }

      $this->log( "DB Copy: Creating table {$new}" );

      $this->stagingDb->query( "CREATE TABLE {$new} LIKE {$old}" );

      $this->options->job->total = ( int ) $this->stagingDb->get_var( "SELECT COUNT(1) FROM {$old}" );

      if( 0 == $this->options->job->total ) {
         $this->finishStep();
         return false;
      }

      return true;
   }

   /**
    * Is table excluded from search replace processing?
    * @param string $table
    * @return boolean
    */
   private function isExcludedTable( $table ) {

      $customTables = apply_filters( 'wpstg_clone_database_tables_exclude', array() );
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

      $this->log( "DB Copy: {$new} already exists, dropping it first" );
      $this->stagingDb->query( "DROP TABLE {$new}" );
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
