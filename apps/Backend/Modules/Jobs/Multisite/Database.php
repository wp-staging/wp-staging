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
class Database extends JobExecutable {

   /**
    * @var int
    */
   private $total = 0;

   /**
    * @var \WPDB
    */
   private $db;

   /**
    * Initialize
    */
   public function initialize() {
      // Add 2 to total table count because we need to copy two more tables from the main multisite installation wp_users and wp_usermeta
      $this->total = count( $this->options->tables ) + 2;
      $this->db = WPStaging::getInstance()->get( "wpdb" );
      $this->isFatalError();

   }
   
      
   /**
    * Return fatal error and stops here if subfolder already exists
    * and mainJob is not updating the clone 
    * @return boolean
    */
   private function isFatalError(){
      $path = trailingslashit(get_home_path()) . $this->options->cloneDirectoryName;
      if (isset($this->options->mainJob) && $this->options->mainJob !== 'updating' && is_dir($path)){
         $this->returnException( " Can not continue! Change the name of the clone or delete existing folder. Then try again. Folder already exists: " . $path );
      }
      return false;
   }

   /**
    * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
    * @return void
    */
   protected function calculateTotalSteps() {
      $this->options->totalSteps = $this->total === 0 ? 1 : $this->total;
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
      //if( $this->options->currentStep > $this->total || !isset( $this->options->tables[$this->options->currentStep] ) ) {
      if( $this->options->currentStep > $this->total ) {
         $this->prepareResponse( true, false );
         return false;
      }

      // Copy table
      //if (!$this->copyTable($this->options->tables[$this->options->currentStep]->name))
      if( isset($this->options->tables[$this->options->currentStep]) && !$this->copyTable( $this->options->tables[$this->options->currentStep] ) ) {
         // Prepare Response
         $this->prepareResponse( false, false );

         // Not finished
         return true;
      }

      $this->copyWpUsers();

      $this->copyWpUsermeta();

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
      $stagingPrefix = $this->options->prefix;
      // Make sure prefix of staging site is NEVER identical to prefix of live site! 
      if( $stagingPrefix == $this->db->prefix ) {
         wp_die( 'Fatal error 7: The new database table prefix ' . $stagingPrefix . ' would be identical to the table prefix of the live site. Please open a support ticket at support@wp-staging.com' );
      }
      return $stagingPrefix;
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
   private function copyWpUsers() {
      $strings = new Strings();
      $tableName = $this->db->base_prefix . 'users';
      $newTableName = $this->getStagingPrefix() . $strings->str_replace_first( $this->db->base_prefix, null, $tableName );

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
    * Copy multisite global user table wp_usermeta to wpstgX_users
    * @return bool
    */
   private function copyWpUsermeta() {
      $strings = new Strings();
      $tableName = $this->db->base_prefix . 'usermeta';
      $newTableName = $this->getStagingPrefix() . $strings->str_replace_first( $this->db->base_prefix, null, $tableName );

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
    * Copy data from old table to new table
    * @param string $new
    * @param string $old
    */
   private function copyData( $new, $old ) {
      $rows = $this->options->job->start + $this->settings->queryLimit;
      $this->log(
              "DB Copy: {$old} as {$new} from {$this->options->job->start} to {$rows} records"
      );

      $limitation = '';

      if( 0 < ( int ) $this->settings->queryLimit ) {
         $limitation = " LIMIT {$this->settings->queryLimit} OFFSET {$this->options->job->start}";
      }

      $this->db->query(
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
      if( 0 != $this->options->job->start ) {
         return true;
      }

      $this->log( "DB Copy: Creating table {$new}" );

      $this->db->query( "CREATE TABLE {$new} LIKE {$old}" );

      $this->options->job->total = ( int ) $this->db->get_var( "SELECT COUNT(1) FROM {$old}" );

      if( 0 == $this->options->job->total ) {
         $this->finishStep();
         return false;
      }

      return true;
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
      $this->options->clonedTables[] = isset($this->options->tables[$this->options->currentStep]) ? $this->options->tables[$this->options->currentStep] : false;

      // Reset job
      $this->options->job = new \stdClass();

      return true;
   }

   /**
    * Drop table if necessary
    * @param string $new
    */
   private function dropTable( $new ) {
      $old = $this->db->get_var( $this->db->prepare( "SHOW TABLES LIKE %s", $new ) );

      if( !$this->shouldDropTable( $new, $old ) ) {
         return;
      }

      $this->log( "DB Copy: {$new} already exists, dropping it first" );
      $this->db->query( "DROP TABLE {$new}" );
   }

   /**
    * Check if table needs to be dropped
    * @param string $new
    * @param string $old
    * @return bool
    */
   private function shouldDropTable( $new, $old ) {
      return (
              $old === $new &&
              (
              !isset( $this->options->job->current ) ||
              !isset( $this->options->job->start ) ||
              0 == $this->options->job->start
              )
              );
   }

}
