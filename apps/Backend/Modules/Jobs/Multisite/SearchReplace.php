<?php

namespace WPStaging\Backend\Modules\Jobs\Multisite;

// No Direct Access
if( !defined( "WPINC" ) ) {
    die;
}

use WPStaging\WPStaging;
use WPStaging\Utils\Strings;
use WPStaging\Utils\Helper;
use WPStaging\Utils\Multisite;
use WPStaging\Backend\Modules\Jobs\JobExecutable;

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
     * @var Obj 
     */
    private $strings;

    /**
     *
     * @var string
     */
    private $destinationHostname;

    /**
     *
     * @var string
     */
    private $sourceHostname;

    /**
     *
     * @var string
     */
    //private $targetDir;

    /**
     * The prefix of the new database tables which are used for the live site after updating tables
     * @var string 
     */
    public $tmpPrefix;

    /**
     * Initialize
     */
    public function initialize() {
        $this->total               = count( $this->options->tables );
        $this->db                  = WPStaging::getInstance()->get( "wpdb" );
        $this->tmpPrefix           = $this->options->prefix;
        $this->strings             = new Strings();
        $this->sourceHostname      = $this->getSourceHostname();
        $this->destinationHostname = $this->getDestinationHostname();
    }

    public function start() {
        // Skip job. Nothing to do
        if( $this->options->totalSteps === 0 ) {
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
        $strings      = new Strings();
        $table        = $strings->str_replace_first( $this->db->prefix, '', $tableName );
        $newTableName = $this->tmpPrefix . $table;

        // Save current job
        $this->setJob( $newTableName );

        // Beginning of the job
        if( !$this->startJob( $newTableName, $tableName ) ) {
            return true;
        }
        // Copy data
        $this->startReplace( $newTableName );

        // Finish the step
        return $this->finishStep();
    }

    /**
     * Get source Hostname depending on wheather WP has been installed in sub dir or not
     * @return type
     */
    public function getSourceHostname() {

        if( $this->isSubDir() ) {
            return trailingslashit( $this->multisiteHomeUrlWithoutScheme ) . '/' . $this->getSubDir();
        }
        return $this->multisiteHomeUrlWithoutScheme;
    }

    /**
     * Get destination Hostname depending on wheather WP has been installed in sub dir or not
     * @return type
     */
    public function getDestinationHostname() {

        if( !empty( $this->options->cloneHostname ) ) {
            return $this->strings->getUrlWithoutScheme( $this->options->cloneHostname );
        }

        if( $this->isSubDir() ) {
            return trailingslashit( $this->strings->getUrlWithoutScheme( $this->multisiteDomainWithoutScheme ) ) . $this->getSubDir() . '/' . $this->options->cloneDirectoryName;
        }

        // Get the path to the main multisite without appending and trailingslash e.g. wordpress
        $multisitePath = defined( 'PATH_CURRENT_SITE') ? PATH_CURRENT_SITE : '/';       
        $url = rtrim( $this->strings->getUrlWithoutScheme( $this->multisiteDomainWithoutScheme ), '/\\' ) . $multisitePath . $this->options->cloneDirectoryName;
        //$multisitePath = defined( 'PATH_CURRENT_SITE' ) ? str_replace( '/', '', PATH_CURRENT_SITE ) : '';
        //$url           = trailingslashit( $this->strings->getUrlWithoutScheme( $this->multisiteDomainWithoutScheme ) ) . $multisitePath . '/' . $this->options->cloneDirectoryName;
        return $url;
    }

    /**
     * Get the install sub directory if WP is installed in sub directory
     * @return string
     */
    private function getSubDir() {
        $home    = get_option( 'home' );
        $siteurl = get_option( 'siteurl' );

        if( empty( $home ) || empty( $siteurl ) ) {
            return '';
        }

        $dir = str_replace( $home, '', $siteurl );
        return str_replace( '/', '', $dir );
    }

    /**
     * Start search replace job
     * @param string $new
     * @param string $old
     */
    private function startReplace( $table ) {
        $rows = $this->options->job->start + $this->settings->querySRLimit;
        $this->log(
                "DB Processing:  Table {$table} {$this->options->job->start} to {$rows} records"
        );

        // Search & Replace
        $this->searchReplace( $table, $rows, array() );

        // Set new offset
        $this->options->job->start += $this->settings->querySRLimit;
    }

    /**
     * Returns the number of pages in a table.
     * @access public
     * @return int
     */
    private function get_pages_in_table( $table ) {

        // Table does not exist
        $result = $this->db->query( "SHOW TABLES LIKE '{$table}'" );
        if( !$result || 0 === $result ) {
            return 0;
        }

        $table = esc_sql( $table );
        $rows  = $this->db->get_var( "SELECT COUNT(*) FROM $table" );
        $pages = ceil( $rows / $this->settings->querySRLimit );
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
        $columns     = array();
        $fields      = $this->db->get_results( 'DESCRIBE ' . $table );
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
     * Return absolute destination path
     * @return string
     */
    private function getAbsDestination() {
        if( empty( $this->options->cloneDir ) ) {
            return \WPStaging\WPStaging::getWPpath();
        }
        return trailingslashit( $this->options->cloneDir );
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
     * @param  array 	$args         An associative array containing arguments for this run.
     * @return array
     */
    private function searchReplace( $table, $page, $args ) {

        if( $this->thirdParty->isSearchReplaceExcluded( $table ) ) {
            $this->log( "DB Processing: Skip {$table}", \WPStaging\Utils\Logger::TYPE_INFO );
            return true;
        }

        // Load up the default settings for this chunk.
        $table        = esc_sql( $table );
        $current_page = $this->options->job->start + $this->settings->querySRLimit;
        $pages        = $this->get_pages_in_table( $table );


        // Search URL example.com/staging and root path to staging site /var/www/htdocs/staging
        $args['search_for'] = array(
            '//' . $this->getSourceHostname(),
            ABSPATH,
            '\/\/' . str_replace( '/', '\/', $this->getSourceHostname() ), // Used by revslider and several visual editors
            '%2F%2F' . str_replace( '/', '%2F', $this->getSourceHostname() ), // HTML entitity for WP Backery Page Builder Plugin
                //$this->getImagePathLive()
        );


        $args['replace_with'] = array(
            '//' . $this->getDestinationHostname(),
            $this->options->destinationDir,
            '\/\/' . str_replace( '/', '\/', $this->getDestinationHostname() ), // Used by revslider and several visual editors
            '%2F%2F' . str_replace( '/', '%2F', $this->getDestinationHostname() ), // HTML entitity for WP Backery Page Builder Plugin
                //$this->getImagePathStaging()
        );

        $this->debugLog( "DB Processing: Search: {$args['search_for'][0]}", \WPStaging\Utils\Logger::TYPE_INFO );
        $this->debugLog( "DB Processing: Replace: {$args['replace_with'][0]}", \WPStaging\Utils\Logger::TYPE_INFO );



        $args['replace_guids']    = 'off';
        $args['dry_run']          = 'off';
        $args['case_insensitive'] = false;
        //$args['replace_mails']    = 'off';
        $args['skip_transients']  = 'on';


        // Allow filtering of search & replace parameters
        $args = apply_filters( 'wpstg_clone_searchreplace_params', $args );

        // Get a list of columns in this table.
        list( $primary_key, $columns ) = $this->get_columns( $table );

        // Bail out early if there isn't a primary key.
        // We commented this to search & replace through tables which have no primary keys like wp_revslider_slides
        // @todo test this carefully. If it causes (performance) issues we need to activate it again!
        // @since 2.4.4
        //      if( null === $primary_key ) {
        //         return false;
        //      }

        $current_row = 0;
        $start       = $this->options->job->start;
        $end         = $this->settings->querySRLimit;

        // Grab the content of the table.
        $data = $this->db->get_results( "SELECT * FROM $table LIMIT $start, $end", ARRAY_A );

        // Filter certain rows option_name in wpstg_options
        $filter = array(
            'Admin_custome_login_Slidshow',
            'Admin_custome_login_Social',
            'Admin_custome_login_logo',
            'Admin_custome_login_text',
            'Admin_custome_login_login',
            'Admin_custome_login_top',
            'Admin_custome_login_dashboard',
            'Admin_custome_login_Version',
            'upload_path',
        );

        $filter = apply_filters( 'wpstg_clone_searchreplace_excl_rows', $filter );

        // Loop through the data.
        foreach ( $data as $row ) {
            $current_row++;
            $update_sql = array();
            $where_sql  = array();
            $upd        = false;

            // Skip rows below
            if( isset( $row['option_name'] ) && in_array( $row['option_name'], $filter ) ) {
                continue;
            }

            // Skip rows with transients (They can store huge data and we need to save memory)
            if( isset( $row['option_name'] ) && 'on' === $args['skip_transients'] && false !== strpos( $row['option_name'], '_transient' ) ) {
                continue;
            }
            // Skip rows with more than 5MB to save memory
            if( isset( $row['option_value'] ) && strlen( $row['option_value'] ) >= 5000000 ) {
                continue;
            }


            foreach ( $columns as $column ) {

                $dataRow = $row[$column];

                // Skip rows larger than 5MB
                $size = strlen( $dataRow );
                if( $size >= 5000000 ) {
                    continue;
                }

                // Skip Primary key
                if( $column == $primary_key ) {
                    $where_sql[] = $column . ' = "' . $this->mysql_escape_mimic( $dataRow ) . '"';
                    continue;
                }

                // Skip GUIDs by default.
                if( 'on' !== $args['replace_guids'] && 'guid' == $column ) {
                    continue;
                }

                // Skip mail addresses
//                if( 'off' === $args['replace_mails'] && false !== strpos( $dataRow, '@' . $this->multisiteDomainWithoutScheme ) ) {
//                    continue;
//                }
                // Check options table
                if( $this->options->prefix . 'options' === $table ) {

                    // Skip certain options
//                    if( isset( $should_skip ) && true === $should_skip ) {
//                        $should_skip = false;
//                        continue;
//                    }
                    // Skip this row
                    if( 'wpstg_existing_clones_beta' === $dataRow ||
                            'wpstg_existing_clones' === $dataRow ||
                            'wpstg_settings' === $dataRow ||
                            'wpstg_license_status' === $dataRow ||
                            'siteurl' === $dataRow ||
                            'home' === $dataRow
                    ) {
                        //$should_skip = true;
                        continue;
                    }
                }

                // Check the path delimiter for / or \/ and remove one of those which prevents from resulting in wrong syntax like domain.com/staging\/.
                // 1. local.wordpress.test -> local.wordpress.test/staging
                // 2. local.wordpress.test\/ -> local.wordpress.test\/staging\/
                $tmp = $args;
                if( false === strpos( $dataRow, $tmp['search_for'][0] ) ) {
                    array_shift( $tmp['search_for'] ); // rtrim( $this->homeUrl, '/' ),
                    array_shift( $tmp['replace_with'] ); // rtrim( $this->homeUrl, '/' ) . '/' . $this->options->cloneDirectoryName,
                } else {
                    unset( $tmp['search_for'][1] );
                    unset( $tmp['replace_with'][1] );
                    // recount array
                    $tmp['search_for']   = array_values( $tmp['search_for'] );
                    $tmp['replace_with'] = array_values( $tmp['replace_with'] );
                }

                // Run a search replace on the data row and respect the serialisation.
                $i = 0;
                foreach ( $tmp['search_for'] as $replace ) {
                    $dataRow = $this->recursive_unserialize_replace( $tmp['search_for'][$i], $tmp['replace_with'][$i], $dataRow, false, $args['case_insensitive'] );
                    $i++;
                }
                unset( $replace );
                unset( $i );
                unset( $tmp );

                // Something was changed
                if( $row[$column] != $dataRow ) {
                    $update_sql[] = $column . ' = "' . $this->mysql_escape_mimic( $dataRow ) . '"';
                    $upd          = true;
                }
            }

            // Determine what to do with updates.
            if( $args['dry_run'] === 'on' ) {
                // Don't do anything if a dry run
            } elseif( $upd && !empty( $where_sql ) ) {
                // If there are changes to make, run the query.
                $sql    = 'UPDATE ' . $table . ' SET ' . implode( ', ', $update_sql ) . ' WHERE ' . implode( ' AND ', array_filter( $where_sql ) );
                $result = $this->db->query( $sql );

                if( !$result ) {
                    $this->log( "Error updating row {$current_row} SQL: {$sql}", \WPStaging\Utils\Logger::TYPE_ERROR );
                }
            }
        } // end row loop
        unset( $row );
        unset( $update_sql );
        unset( $where_sql );
        unset( $sql );


        // DB Flush
        $this->db->flush();
        return true;
    }

    /**
     * Get path to multisite image folder e.g. wp-content/blogs.dir/ID/files or wp-content/uploads/sites/ID
     * @return string
     */
    private function getImagePathLive() {
        // Check first which structure is used 
        $uploads = wp_upload_dir();
        $basedir = $uploads['basedir'];
        $blogId  = get_current_blog_id();

        if( false === strpos( $basedir, 'blogs.dir' ) ) {
            // Since WP 3.5
            $path = $blogId > 1 ?
                    'wp-content' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR :
                    'wp-content' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        } else {
            // old blog structure
            $path = $blogId > 1 ?
                    'wp-content' . DIRECTORY_SEPARATOR . 'blogs.dir' . DIRECTORY_SEPARATOR . get_current_blog_id() . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR :
                    'wp-content' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
        }
        return $path;
    }

    /**
     * Get path to staging site image path wp-content/uploads
     * @return string
     */
    private function getImagePathStaging() {
        return 'wp-content' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
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
     * @param  boolean 			$serialized 		Does the array passed via $data need serialising.
     * @param  sting|boolean              $case_insensitive 	Set to 'on' if we should ignore case, false otherwise.
     *
     * @return string|array	The original array with all elements replaced as needed.
     */
    private function recursive_unserialize_replace( $from = '', $to = '', $data = '', $serialized = false, $case_insensitive = false ) {
        try {
            // Some unserialized data cannot be re-serialized eg. SimpleXMLElements
            if( is_serialized( $data ) && ( $unserialized = @unserialize( $data ) ) !== false ) {
                $data = $this->recursive_unserialize_replace( $from, $to, $unserialized, true, $case_insensitive );
            } elseif( is_array( $data ) ) {
                $tmp = array();
                foreach ( $data as $key => $value ) {
                    $tmp[$key] = $this->recursive_unserialize_replace( $from, $to, $value, false, $case_insensitive );
                }

                $data = $tmp;
                unset( $tmp );
            } elseif( is_object( $data ) ) {
                $tmp   = $data;
                $props = get_object_vars( $data );

                // Do not continue if class contains __PHP_Incomplete_Class_Name
                if( !empty( $props['__PHP_Incomplete_Class_Name'] ) ) {
                    return $data;
                    
                }

                // Do a search & replace
                foreach ( $props as $key => $value ) {
                    if( $key === '' || ord( $key[0] ) === 0 ) {
                        continue;
                    }
                    $tmp->$key = $this->recursive_unserialize_replace( $from, $to, $value, false, $case_insensitive );
                }

                $data = $tmp;
                unset( $tmp );
                unset( $props );
            } else {
                if( is_string( $data ) ) {
                    if( !empty( $from ) && !empty( $to ) ) {
                        $data = $this->str_replace( $from, $to, $data, $case_insensitive );
                    }
                }
            }

            if( $serialized ) {
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
//   private function isValidObject( $data ) {
//      if( !is_object( $data ) || gettype( $data ) != 'object' ) {
//         return false;
//      }
//
//      $invalid_class_props = get_object_vars( $data );
//
//      if (!isset($invalid_class_props['__PHP_Incomplete_Class_Name'])){
//         // Assume it must be an valid object
//         return true;
//      }
//
//      $invalid_object_class = $invalid_class_props['__PHP_Incomplete_Class_Name'];
//
//      if( !empty( $invalid_object_class ) ) {
//         return false;
//      }
//
//      // Assume it must be an valid object
//      return true;
//   }

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

        $serialized_string   = trim( $serialized_string );
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

        // Add filter
        $excludes = apply_filters( 'wpstg_clone_searchreplace_excl', array() );

        // Build pattern
        $regexExclude = '';
        foreach ( $excludes as $exclude ) {
            $regexExclude .= $exclude . '(*SKIP)(FAIL)|';
        }

        if( 'on' === $case_insensitive ) {
            //$data = str_ireplace( $from, $to, $data );
            $data = preg_replace( '#' . $regexExclude . preg_quote( $from ) . '#i', $to, $data );
        } else {
            //$data = str_replace( $from, $to, $data );
            $data = preg_replace( '#' . $regexExclude . preg_quote( $from ) . '#', $to, $data );
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
        $this->options->job->start   = 0;
    }

    /**
     * Start Job
     * @param string $new
     * @param string $old
     * @return bool
     */
    private function startJob( $new, $old ) {

        $this->options->job->total = 0;

        if( 0 != $this->options->job->start ) {
            return true;
        }

        // Table does not exist
        $result = $this->db->query( "SHOW TABLES LIKE '{$old}'" );
        if( !$result || 0 === $result ) {
            return false;
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
    private function isSubDir() {
        // Compare names without scheme to bypass cases where siteurl and home have different schemes http / https
        // This is happening much more often than you would expect
        $siteurl = preg_replace( '#^https?://#', '', rtrim( get_option( 'siteurl' ), '/' ) );
        $home    = preg_replace( '#^https?://#', '', rtrim( get_option( 'home' ), '/' ) );

        if( $home !== $siteurl ) {
            return true;
        }
        return false;
    }

}
