<?php

namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if( !defined( "WPINC" ) ) {
   die;
}

use WPStaging\WPStaging;
use WPStaging\Utils\Strings;
use WPStaging\Utils\Helper;

/**
 * Class Database
 * @package WPStaging\Backend\Modules\Jobs
 */
class SearchReplace extends JobExecutable {

   /**
    * @var int
    */
   private $total = 0;

   /**
    * @var \WPDB
    */
   public $db;

   
   /**
    *
    * @var string
    */
   private $homeUrl;


   /**
    * The prefix of the new database tables which are used for the live site after updating tables
    * @var string 
    */
   public $tmpPrefix;

   /**
    * Initialize
    */
   public function initialize() {
      $this->total = count( $this->options->tables );
      $this->db = WPStaging::getInstance()->get( "wpdb" );
      //$this->tmpPrefix = 'wpstgtmp_';
      $this->tmpPrefix = $this->options->prefix;
      $helper = new Helper();      
      $this->homeUrl = $helper->get_home_url();
   }

   public function start() {
      // Skip job. Nothing to do
      if ($this->options->totalSteps === 0){
         $this->prepareResponse( true, false );
      }

      $this->run();

      // Save option, progress
      $this->saveOptions();

      return ( object ) $this->response;
   }

   /**
    * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
    * @return void
    */
   protected function calculateTotalSteps() {
      $this->options->totalSteps = $this->total;
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
      if( $this->options->currentStep > $this->total || !isset( $this->options->tables[$this->options->currentStep] ) ) {
         $this->prepareResponse( true, false );
         return false;
      }

      // Table is excluded
      if( in_array( $this->options->tables[$this->options->currentStep], $this->options->excludedTables ) ) {
         $this->prepareResponse();
         return true;
      }

      // Search & Replace
      if( !$this->stopExecution() && !$this->updateTable( $this->options->tables[$this->options->currentStep] ) ) {
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

//   private function convertExcludedTables() {
//      $tmp = array();
//      foreach ( $this->options->excludedTables as $table ) {
//         $tmp[] = str_replace( $this->options->prefix, $this->tmpPrefix, $table );
//      }
//      $this->options->excludedTables = $tmp;
//   }

   /**
    * Stop Execution immediately
    * return mixed bool | json
    */
   private function stopExecution() {
      if( $this->db->prefix == $this->tmpPrefix ) {
         $this->returnException( 'Fatal Error 9: Prefix ' . $this->db->prefix . ' is used for the live site hence it can not be used for the staging site as well. Please ask support@wp-staging.com how to resolve this.' );
      }
      return false;
   }

   /**
    * Copy Tables
    * @param string $tableName
    * @return bool
    */
   private function updateTable( $tableName ) {
      $strings = new Strings();
      $table = $strings->str_replace_first( $this->db->prefix, '', $tableName );
      $newTableName = $this->tmpPrefix . $table;

      // Save current job
      $this->setJob( $newTableName );

      // Beginning of the job
      if( !$this->startJob( $newTableName, $tableName ) ) {
         return true;
      }
      // Copy data
      $this->startReplace( $newTableName );

      // Finis the step
      return $this->finishStep();
   }

   /**
    * Start search replace job
    * @param string $new
    * @param string $old
    */
   private function startReplace( $new ) {
      $rows = $this->options->job->start + $this->settings->queryLimit;
      $this->log(
              "DB Processing:  Table {$new} {$this->options->job->start} to {$rows} records"
        );

      // Search & Replace
      $this->searchReplace( $new, $rows, array() );

      // Set new offset
      $this->options->job->start += $this->settings->queryLimit;
   }

   /**
    * Returns the number of pages in a table.
    * @access public
    * @return int
    */
   private function get_pages_in_table( $table ) {
      $table = esc_sql( $table );
      $rows = $this->db->get_var( "SELECT COUNT(*) FROM $table" );
      $pages = ceil( $rows / $this->settings->queryLimit );
      return absint( $pages );
   }

   /**
    * Gets the columns in a table.
    * @access public
    * @param  string $table The table to check.
    * @return array
    */
   private function get_columns( $table ) {
      $primary_key = null;
      $columns = array();
      $fields = $this->db->get_results( 'DESCRIBE ' . $table );
      if( is_array( $fields ) ) {
         foreach ( $fields as $column ) {
            $columns[] = $column->Field;
            if( $column->Key == 'PRI' ) {
               $primary_key = $column->Field;
            }
         }
      }
      return array($primary_key, $columns);
   }

   /**
    * Adapated from interconnect/it's search/replace script, adapted from Better Search Replace
    *
    * Modified to use WordPress wpdb functions instead of PHP's native mysql/pdo functions,
    * and to be compatible with batch processing.
    *
    * @link https://interconnectit.com/products/search-and-replace-for-wordpress-databases/
    *
    * @access public
    * @param  string 	$table 	The table to run the replacement on.
    * @param  int          $page  	The page/block to begin the query on.
    * @param  array 	$args         An associative array containing arguements for this run.
    * @return array
    */
   private function searchReplace( $table, $page, $args ) {


      // Load up the default settings for this chunk.
      $table = esc_sql( $table );
      $current_page = $this->options->job->start + $this->settings->queryLimit;
      $pages = $this->get_pages_in_table( $table );
      //$done = false;

            
      if( $this->isSubDir() ) {
         //$homeUrl = rtrim($this->homeUrl, "/") . $this->getSubDir() . $this->options->cloneDirectoryName;
      // Search URL example.com/staging and root path to staging site /var/www/htdocs/staging
      $args['search_for'] = array(
             rtrim( $this->homeUrl, "/" ) . $this->getSubDir(),
          ABSPATH
      );

      $args['replace_with'] = array(
             rtrim( $this->homeUrl, "/" ) . $this->getSubDir() . '/' . $this->options->cloneDirectoryName,
             rtrim( ABSPATH, '/' ) . '/' . $this->options->cloneDirectoryName
         );
      } else {
         $args['search_for'] = array(
             rtrim( $this->homeUrl, '/' ),
             ABSPATH
         );
         $args['replace_with'] = array(
             rtrim( $this->homeUrl, '/' ) . '/' . $this->options->cloneDirectoryName,
          rtrim( ABSPATH, '/' ) . '/' . $this->options->cloneDirectoryName
      );
      }

//      // Search URL example.com/staging and root path to staging site /var/www/htdocs/staging
//      $args['search_for'] = array(
//          $this->homeUrl,
//          ABSPATH
//      );
//
//      
//      $args['replace_with'] = array(
//          rtrim( $this->homeUrl, '/' ) . '/' . $this->options->cloneDirectoryName,
//          rtrim( ABSPATH, '/' ) . '/' . $this->options->cloneDirectoryName
//      );
      $args['replace_guids'] = 'off';
      $args['dry_run'] = 'off';
      $args['case_insensitive'] = false;
      $args['replace_guids'] = 'off';

      // Get a list of columns in this table.
      list( $primary_key, $columns ) = $this->get_columns( $table );

      // Bail out early if there isn't a primary key.
      if( null === $primary_key ) {
         return false;
      }

      $current_row = 0;
      $start = $this->options->job->start;
      $end = $this->settings->queryLimit;

      // Grab the content of the table.
      $data = $this->db->get_results( "SELECT * FROM $table LIMIT $start, $end", ARRAY_A );

      // Loop through the data.
      foreach ( $data as $row ) {
         $current_row++;
         $update_sql = array();
         $where_sql = array();
         $upd = false;

         foreach ( $columns as $column ) {

            $dataRow = $row[$column];

            if( $column == $primary_key ) {
               $where_sql[] = $column . ' = "' . $this->mysql_escape_mimic( $dataRow ) . '"';
               continue;
            }

            // Skip GUIDs by default.
            if( 'on' !== $args['replace_guids'] && 'guid' == $column ) {
               continue;
            }

            
            // Check options table
            if( $this->options->prefix . 'options' === $table ) {

               // Skip certain options
               if( isset( $should_skip ) && true === $should_skip ) {
                  $should_skip = false;
                  continue;
               }

               // Skip this row
               if( 'wpstg_existing_clones_beta' === $dataRow ||
                       'wpstg_existing_clones' === $dataRow ||
                       'wpstg_settings' === $dataRow ||
                       'wpstg_license_status' === $dataRow ||
                       'siteurl' === $dataRow ||
                       'home' === $dataRow
               ) {
                  $should_skip = true;
               }
            }

            // Run a search replace on the data that'll respect the serialisation.
            $i = 0;
            foreach ( $args['search_for'] as $replace ) {
               $dataRow = $this->recursive_unserialize_replace( $args['search_for'][$i], $args['replace_with'][$i], $dataRow, false, $args['case_insensitive'] );
               $i++;
            }
            unset( $replace );
            unset( $i );

            // Something was changed
            if( $row[$column] != $dataRow ) {
               $update_sql[] = $column . ' = "' . $this->mysql_escape_mimic( $dataRow ) . '"';
               $upd = true;
            }
         }

         // Determine what to do with updates.
         if( $args['dry_run'] === 'on' ) {
            // Don't do anything if a dry run
         } elseif( $upd && !empty( $where_sql ) ) {
            // If there are changes to make, run the query.
            $sql = 'UPDATE ' . $table . ' SET ' . implode( ', ', $update_sql ) . ' WHERE ' . implode( ' AND ', array_filter( $where_sql ) );
            $result = $this->db->query( $sql );

            if( !$result ) {
               $this->log( "Error updating row {$current_row}", \WPStaging\Utils\Logger::TYPE_ERROR );
            }
         }
      } // end row loop
      unset( $row );

//      if( $current_page >= $pages - 1 ) {
//         $done = true;
//      }

      // DB Flush
      $this->db->flush();
      return true;
   }

   /**
    * Adapted from interconnect/it's search/replace script.
    *
    * @link https://interconnectit.com/products/search-and-replace-for-wordpress-databases/
    *
    * Take a serialised array and unserialise it replacing elements as needed and
    * unserialising any subordinate arrays and performing the replace on those too.
    *
    * @access private
    * @param  string 			$from       		String we're looking to replace.
    * @param  string 			$to         		What we want it to be replaced with
    * @param  array  			$data       		Used to pass any subordinate arrays back to in.
    * @param  boolean 			$serialised 		Does the array passed via $data need serialising.
    * @param  sting|boolean              $case_insensitive 	Set to 'on' if we should ignore case, false otherwise.
    *
    * @return string|array	The original array with all elements replaced as needed.
    */
   private function recursive_unserialize_replace( $from = '', $to = '', $data = '', $serialised = false, $case_insensitive = false ) {
      try {

         if( is_string( $data ) && !is_serialized_string( $data ) && ( $unserialized = $this->unserialize( $data ) ) !== false ) {
            $data = $this->recursive_unserialize_replace( $from, $to, $unserialized, true, $case_insensitive );
         } elseif( is_array( $data ) ) {
            $_tmp = array();
            foreach ( $data as $key => $value ) {
               $_tmp[$key] = $this->recursive_unserialize_replace( $from, $to, $value, false, $case_insensitive );
            }

            $data = $_tmp;
            unset( $_tmp );
         }

         // Submitted by Tina Matter
         elseif( $this->isValidObject($data) ) {
            $_tmp = $data; // new $data_class( );
            $props = get_object_vars( $data );
            foreach ( $props as $key => $value ) {
               $_tmp->$key = $this->recursive_unserialize_replace( $from, $to, $value, false, $case_insensitive );
            }

            $data = $_tmp;
            unset($_tmp);
            } elseif (is_serialized_string($data)) {
                if (false !== ($data = $this->unserialize($data)) ) {
                    $data = $this->str_replace($from, $to, $data, $case_insensitive);
                    $data = serialize($data);
            }
         } else {
            if( is_string( $data ) ) {
               $data = $this->str_replace( $from, $to, $data, $case_insensitive );
            }
         }

         if( $serialised ) {
            return serialize( $data );
         }
      } catch ( Exception $error ) {
         
      }

      return $data;
   }

   /**
    * Check if the object is a valid one and not __PHP_Incomplete_Class_Name
    * Can not use is_object alone because in php 7.2 it's returning true even though object is __PHP_Incomplete_Class_Name
    * @return boolean
    */
   private function isValidObject($data){
      if( !is_object( $data ) || gettype( $data ) != 'object' ) {
         return false;
      }
      
      $invalid_class_props = get_object_vars( $data );
      
      if (!isset($invalid_class_props['__PHP_Incomplete_Class_Name'])){
         // Assume it must be an valid object
         return true;
      }
      
      $invalid_object_class = $invalid_class_props['__PHP_Incomplete_Class_Name'];

      if( !empty( $invalid_object_class ) ) {
         return false;
      }
      
      // Assume it must be an valid object
      return true;
   }

   /**
    * Mimics the mysql_real_escape_string function. Adapted from a post by 'feedr' on php.net.
    * @link   http://php.net/manual/en/function.mysql-real-escape-string.php#101248
    * @access public
    * @param  string $input The string to escape.
    * @return string
    */
   private function mysql_escape_mimic( $input ) {
      if( is_array( $input ) ) {
         return array_map( __METHOD__, $input );
      }
      if( !empty( $input ) && is_string( $input ) ) {
         return str_replace( array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $input );
      }

      return $input;
   }

   /**
    * Return unserialized object or array
    *
    * @param string $serialized_string Serialized string.
    * @param string $method            The name of the caller method.
    *
    * @return mixed, false on failure
    */
   private static function unserialize( $serialized_string ) {
      if( !is_serialized( $serialized_string ) ) {
         return false;
      }

      $serialized_string = trim( $serialized_string );
      $unserialized_string = @unserialize( $serialized_string );

      return $unserialized_string;
   }

   /**
    * Wrapper for str_replace
    *
    * @param string $from
    * @param string $to
    * @param string $data
    * @param string|bool $case_insensitive
    *
    * @return string
    */
   private function str_replace( $from, $to, $data, $case_insensitive = false ) {
      if( 'on' === $case_insensitive ) {
         $data = str_ireplace( $from, $to, $data );
      } else {
         $data = str_replace( $from, $to, $data );
      }

      return $data;
   }

   /**
    * Set the job
    * @param string $table
    */
   private function setJob( $table ) {
      if( !empty( $this->options->job->current ) ) {
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
      $this->options->clonedTables[] = $this->options->tables[$this->options->currentStep];

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

      $this->log( "DB Processing: {$new} already exists, dropping it first" );
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
              $old == $new &&
              (
              !isset( $this->options->job->current ) ||
              !isset( $this->options->job->start ) ||
              0 == $this->options->job->start
              )
              );
   }

    /**
     * Check if WP is installed in subdir
     * @return boolean
     */
    private function isSubDir(){
        if ( get_option( 'siteurl' ) !== get_option( 'home' ) ) { 
            return true;
}
        return false;
    }
   
    /**
     * Get the install sub directory if WP is installed in sub directory
     * @return string
     */
   private function getSubDir() {
      $home = get_option( 'home' );
      $siteurl = get_option( 'siteurl' );
       
      if( empty( $home ) || empty( $siteurl ) ) {
          return '/';
       }
       
      $dir = str_replace( $home, '', $siteurl );
      return '/' . str_replace( '/', '', $dir );
    }

}
