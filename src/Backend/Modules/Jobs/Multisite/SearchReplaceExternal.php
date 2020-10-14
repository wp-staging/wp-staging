<?php

namespace WPStaging\Backend\Modules\Jobs\Multisite;

use Exception;
use WPStaging\WPStaging;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Backend\Modules\Jobs\JobExecutable;
use WPStaging\Utils\Helper;

/**
 * Class Database
 * @package WPStaging\Backend\Modules\Jobs
 */
class SearchReplaceExternal extends JobExecutable
{

    /**
     * @var int
     */
    private $total = 0;


    /**
     * @var \WPDB
     */
    private $productionDb;


    /**
     * @var \WPDB
     */
    private $stagingDb;

    /**
     *
     * @var string
     */
    private $sourceHostname;

    /**
     *
     * @var string
     */
    private $destinationHostname;

    /**
     *
     * @var Obj
     */
    private $strings;

    /**
     * @var Obj
     */
    private $helper;

    /**
     * The prefix of the new database tables which are used for the live site after updating tables
     * @var string
     */
    public $tmpPrefix;

    /**
     * Initialize
     */
    public function initialize()
    {
        $this->helper = new Helper();
        $this->total = count($this->options->tables);
        $this->stagingDb = $this->getStagingDB();
        $this->productionDb = WPStaging::getInstance()->get("wpdb");
        $this->tmpPrefix = $this->options->prefix;
        $this->strings = new Strings();
        $this->sourceHostname = $this->getSourceHostname();
        $this->destinationHostname = $this->getDestinationHostname();

    }

    /**
     * Get database object to interact with
     */
    private function getStagingDB()
    {
        return new \wpdb($this->options->databaseUser, $this->options->databasePassword, $this->options->databaseDatabase, $this->options->databaseServer);
    }

    public function start()
    {
        // Skip job. Nothing to do
        if ($this->options->totalSteps === 0) {
            $this->prepareResponse(true, false);
        }

        $this->run();

        // Save option, progress
        $this->saveOptions();

        return ( object )$this->response;
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = $this->total;
    }

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     */
    protected function execute()
    {
        // Over limits threshold
        if ($this->isOverThreshold()) {
            // Prepare response and save current progress
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

        // No more steps, finished
        if ($this->options->currentStep > $this->total || !isset($this->options->tables[$this->options->currentStep])) {
            $this->prepareResponse(true, false);
            return false;
        }

        // Table is excluded
        if (in_array($this->options->tables[$this->options->currentStep], $this->options->excludedTables)) {
            $this->prepareResponse();
            return true;
        }

        // Search & Replace
        if (!$this->updateTable($this->options->tables[$this->options->currentStep])) {
            // Prepare Response
            $this->prepareResponse(false, false);

            // Not finished
            return true;
        }


        // Prepare Response
        $this->prepareResponse();

        // Not finished
        return true;
    }

    /**
     * Copy Tables
     * @param string $tableName
     * @return bool
     */
    private function updateTable($tableName)
    {
        $strings = new Strings();
        $table = $strings->str_replace_first($this->productionDb->prefix, '', $tableName);
        $newTableName = $this->tmpPrefix . $table;

        // Save current job
        $this->setJob($newTableName);

        // Beginning of the job
        if (!$this->startJob($newTableName, $tableName)) {
            return true;
        }
        // Copy data
        $this->startReplace($newTableName);

        // Finish the step
        return $this->finishStep();
    }

    /**
     * Get source home URL including sub dir if WP is installed in sub dir and not in root
     * @return type
     */
    private function getSourceHostname()
    {
        $homeUrlWithoutScheme = $this->helper->getHomeUrlWithoutScheme();

        if ($this->isSubDir()) {
            return trailingslashit($homeUrlWithoutScheme) . $this->getSubDir();
        }
        return $homeUrlWithoutScheme;
    }

    /**
     * Get destination hostname without scheme e.g example.com/staging or staging.example.com
     *
     * Conditions:
     * - Main job is 'update'
     * - WP installed in sub dir
     * - Target hostname in advanced settings defined (Pro version only)
     *
     * @todo Complex conditions. Might need refactor
     * @return string
     */
    private function getDestinationHostname()
    {

        // Update process: Neither 'push' nor 'clone'
        if ($this->options->mainJob === 'updating') {
            // Defined and created in advanced settings with pro version
            if (!empty($this->options->cloneHostname)) {
                return $this->strings->getUrlWithoutScheme($this->options->cloneHostname);
            } else {
                return $this->strings->getUrlWithoutScheme($this->options->destinationHostname);
            }
        }

        // Clone process: Defined and created in advanced settings with pro version
        if (!empty($this->options->cloneHostname)) {
            return $this->strings->getUrlWithoutScheme($this->options->cloneHostname);
        }

        // Clone process: WP installed in sub directory under root
        if ($this->isSubDir()) {
            return trailingslashit($this->strings->getUrlWithoutScheme(get_home_url())) . $this->getSubDir() . '/' . $this->options->cloneDirectoryName;
        }

        // Relative path to root of main multisite without leading or trailing slash e.g.: wordpress
        $multisitePath = defined('PATH_CURRENT_SITE') ? PATH_CURRENT_SITE : '/';

        return rtrim($this->helper->getBaseUrlWithoutScheme(), '/\\') . $multisitePath . $this->options->cloneDirectoryName;
    }

    /**
     * Get the install sub directory if WP is installed in sub directory
     * @return string
     */
    private function getSubDir()
    {
        $home = get_option('home');
        $siteurl = get_option('siteurl');

        if (empty($home) || empty($siteurl)) {
            return '';
        }

        $dir = str_replace($home, '', $siteurl);
        return str_replace('/', '', $dir);
    }

    /**
     * Start search replace job
     * @param string $new
     * @param string $old
     */
    private function startReplace($table)
    {
        $rows = $this->options->job->start + $this->settings->querySRLimit;
        $this->log(
            "DB Search & Replace:  Table {$table} {$this->options->job->start} to {$rows} records"
        );

        // Search & Replace
        $this->searchReplace($table, $rows, array());

        // Set new offset
        $this->options->job->start += $this->settings->querySRLimit;
    }

    /**
     * Gets the columns in a table.
     * @access public
     * @param string $table The table to check.
     * @return array
     */
    private function get_columns($table)
    {
        $primary_key = null;
        $columns = array();
        $fields = $this->stagingDb->get_results('DESCRIBE ' . $table);
        if (is_array($fields)) {
            foreach ($fields as $column) {
                $columns[] = $column->Field;
                if ($column->Key == 'PRI') {
                    $primary_key = $column->Field;
                }
            }
        }
        return array($primary_key, $columns);
    }

    /**
     * Adapted from interconnect/it's search/replace script, adapted from Better Search Replace
     *
     * Modified to use WordPress wpdb functions instead of PHP's native mysql/pdo functions,
     * and to be compatible with batch processing.
     *
     * @link https://interconnectit.com/products/search-and-replace-for-wordpress-databases/
     *
     * @access public
     * @param string $table The table to run the replacement on.
     * @param int $page The page/block to begin the query on.
     * @param array $args An associative array containing arguments for this run.
     * @return array
     */
    private function searchReplace($table, $page, $args)
    {

        if ($this->thirdParty->isSearchReplaceExcluded($table)) {
            $this->log("DB Search & Replace: Skip {$table}", \WPStaging\Utils\Logger::TYPE_INFO);
            return true;
        }

        $table = esc_sql($table);

        $args['search_for'] = array(
            '%2F%2F' . str_replace('/', '%2F', $this->sourceHostname), // HTML entitity for WP Backery Page Builder Plugin
            '\/\/' . str_replace('/', '\/', $this->sourceHostname), // Escaped \/ used by revslider and several visual editors
            '//' . $this->sourceHostname, // //example.com
            ABSPATH
        );

        $args['replace_with'] = array(
            '%2F%2F' . str_replace('/', '%2F', $this->destinationHostname),
            '\/\/' . str_replace('/', '\/', $this->destinationHostname),
            '//' . $this->destinationHostname,
            $this->options->destinationDir
        );

        $this->debugLog("DB Search & Replace: Search: {$args['search_for'][0]}", \WPStaging\Utils\Logger::TYPE_INFO);
        $this->debugLog("DB Search & Replace: Replace: {$args['replace_with'][0]}", \WPStaging\Utils\Logger::TYPE_INFO);


        $args['replace_guids'] = 'off';
        $args['dry_run'] = 'off';
        $args['case_insensitive'] = false;
        $args['skip_transients'] = 'on';

        // Allow filtering of search & replace parameters
        $args = apply_filters('wpstg_clone_searchreplace_params', $args);

        // Get columns and primary keys
        list($primary_key, $columns) = $this->get_columns($table);

        $current_row = 0;
        $start = $this->options->job->start;
        $end = $this->settings->querySRLimit;

        $data = $this->stagingDb->get_results("SELECT * FROM $table LIMIT $start, $end", ARRAY_A);

        // Filter certain rows (of other plugins)
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
            'wpstg_existing_clones_beta',
            'wpstg_existing_clones',
            'wpstg_settings',
            'wpstg_license_status',
            'siteurl',
            'home'
        );

        $filter = apply_filters('wpstg_clone_searchreplace_excl_rows', $filter);

        // Go through the table rows
        foreach ($data as $row) {
            $current_row++;
            $update_sql = array();
            $where_sql = array();
            $upd = false;

            // Skip rows
            if (isset($row['option_name']) && in_array($row['option_name'], $filter)) {
                continue;
            }

            // Skip transients (There can be thousands of them. Save memory and increase performance)
            if (isset($row['option_name']) && 'on' === $args['skip_transients'] && false
                !== strpos($row['option_name'], '_transient')) {
                continue;
            }
            // Skip rows with more than 5MB to save memory. These rows contain log data or something similiar but never site relevant data
            if (isset($row['option_value']) && strlen($row['option_value']) >= 5000000) {
                continue;
            }

            // Go through the columns
            foreach ($columns as $column) {

                $dataRow = $row[$column];

                // Skip column larger than 5MB
                $size = strlen($dataRow);
                if ($size >= 5000000) {
                    continue;
                }

                // Skip primary key column
                if ($column == $primary_key) {
                    $where_sql[] = $column . ' = "' . $this->mysql_escape_mimic($dataRow) . '"';
                    continue;
                }

                // Skip GUIDs by default.
                if ('on' !== $args['replace_guids'] && 'guid' == $column) {
                    continue;
                }


                $i = 0;
                foreach ($args['search_for'] as $replace) {
                    $dataRow = $this->recursive_unserialize_replace($args['search_for'][$i], $args['replace_with'][$i], $dataRow, false, $args['case_insensitive']);
                    $i++;
                }
                unset($replace, $i);

                // Something was changed
                if ($row[$column] != $dataRow) {
                    $update_sql[] = $column . ' = "' . $this->mysql_escape_mimic($dataRow) . '"';
                    $upd = true;
                }
            }

            // Determine what to do with updates.
            if ($args['dry_run'] === 'on') {
                // Don't do anything if a dry run
            } elseif ($upd && !empty($where_sql)) {
                // If there are changes to make, run the query.
                $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $update_sql) . ' WHERE ' . implode(' AND ', array_filter($where_sql));
                $result = $this->stagingDb->query($sql);

                if (!$result) {
                    $this->log("Error updating row {$current_row} SQL: {$sql}", \WPStaging\Utils\Logger::TYPE_ERROR);
                }
            }
        } // end row loop
        unset($row);
        unset($update_sql);
        unset($where_sql);
        unset($sql);
        unset($current_row);

        // DB Flush
        $this->stagingDb->flush();
        return true;
    }

    /**
     * Get path to multisite image folder e.g. wp-content/blogs.dir/ID/files or wp-content/uploads/sites/ID
     * @return string
     */
/*    private function getImagePathLive()
    {
        // Check first which structure is used 
        $uploads = wp_upload_dir();
        $basedir = $uploads['basedir'];
        $blogId = get_current_blog_id();

        if (false === strpos($basedir, 'blogs.dir')) {
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
    }*/

    /**
     * Get path to staging site image path wp-content/uploads
     * @return string
     */
/*    private function getImagePathStaging()
    {
        return 'wp-content' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    }*/

    /**
     * Adapted from interconnect/it's search/replace script.
     *
     * @link https://interconnectit.com/products/search-and-replace-for-wordpress-databases/
     *
     * Take a serialised array and unserialise it replacing elements as needed and
     * unserialising any subordinate arrays and performing the replace on those too.
     *
     * @access private
     * @param string $from String we're looking to replace.
     * @param string $to What we want it to be replaced with
     * @param array $data Used to pass any subordinate arrays back to in.
     * @param boolean $serialized Does the array passed via $data need serialising.
     * @param sting|boolean $case_insensitive Set to 'on' if we should ignore case, false otherwise.
     *
     * @return string|array    The original array with all elements replaced as needed.
     */
    private function recursive_unserialize_replace($from = '', $to = '', $data = '', $serialized = false, $case_insensitive = false)
    {
        try {
            // PDO instances can not be serialized or unserialized
            if (is_serialized($data) && strpos($data, 'O:3:"PDO":0:') !== false) {
                return $data;
            }
            // DateTime object can not be unserialized.
            // Would throw PHP Fatal error:  Uncaught Error: Invalid serialization data for DateTime object in
            // Bug PHP https://bugs.php.net/bug.php?id=68889&thanks=6 and https://github.com/WP-Staging/wp-staging-pro/issues/74
            if (is_serialized($data) && strpos($data, 'O:8:"DateTime":0:') !== false) {
                return $data;
            }
            // Some unserialized data cannot be re-serialized eg. SimpleXMLElements
            if (is_serialized($data) && ($unserialized = @unserialize($data)) !== false) {
                $data = $this->recursive_unserialize_replace($from, $to, $unserialized, true, $case_insensitive);
            } elseif (is_array($data)) {
                $tmp = array();
                foreach ($data as $key => $value) {
                    $tmp[$key] = $this->recursive_unserialize_replace($from, $to, $value, false, $case_insensitive);
                }

                $data = $tmp;
                unset($tmp);
            } elseif (is_object($data)) {
                $props = get_object_vars($data);

                // Do a search & replace
                if (empty($props['__PHP_Incomplete_Class_Name'])) {
                    $tmp = $data;
                    foreach ($props as $key => $value) {
                        if ($key === '' || ord($key[0]) === 0) {
                            continue;
                        }
                        $tmp->$key = $this->recursive_unserialize_replace($from, $to, $value, false, $case_insensitive);
                    }
                    $data = $tmp;
                    $tmp = '';
                    $props = '';
                    unset($tmp);
                    unset($props);
                }
            } else {
                if (is_string($data)) {
                    if (!empty($from) && !empty($to)) {
                        $data = $this->str_replace($from, $to, $data, $case_insensitive);
                    }
                }
            }

            if ($serialized) {
                return serialize($data);
            }
        } catch (Exception $error) {

        }

        return $data;
    }

    /**
     * Mimics the mysql_real_escape_string function. Adapted from a post by 'feedr' on php.net.
     * @link   http://php.net/manual/en/function.mysql-real-escape-string.php#101248
     * @access public
     * @param string $input The string to escape.
     * @return string
     */
    private function mysql_escape_mimic($input)
    {
        if (is_array($input)) {
            return array_map(__METHOD__, $input);
        }
        if (!empty($input) && is_string($input)) {
            return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $input);
        }

        return $input;
    }

    /**
     * Return unserialized object or array
     *
     * @param string $serialized_string Serialized string.
     * @param string $method The name of the caller method.
     *
     * @return mixed, false on failure
     */
/*    private static function unserialize($serialized_string)
    {
        if (!is_serialized($serialized_string)) {
            return false;
        }

        $serialized_string = trim($serialized_string);
        $unserialized_string = @unserialize($serialized_string);

        return $unserialized_string;
    }*/

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
    private function str_replace($from, $to, $data, $case_insensitive = false)
    {

        // Add filter
        $excludes = apply_filters('wpstg_clone_searchreplace_excl', array());

        // Build pattern
        $regexExclude = '';
        foreach ($excludes as $exclude) {
            $regexExclude .= $exclude . '(*SKIP)(FAIL)|';
        }

        if ('on' === $case_insensitive) {
            $data = preg_replace('#' . $regexExclude . preg_quote($from) . '#i', $to, $data);
        } else {
            $data = preg_replace('#' . $regexExclude . preg_quote($from) . '#', $to, $data);
        }

        return $data;
    }

    /**
     * Set the job
     * @param string $table
     */
    private function setJob($table)
    {
        if (!empty($this->options->job->current)) {
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
    private function startJob($new, $old)
    {

        if ($this->isExcludedTable($new)) {
            return false;
        }

        // Table does not exist
        $result = $this->productionDb->query("SHOW TABLES LIKE '{$old}'");
        if (!$result || 0 === $result) {
            return false;
        }

        if (0 != $this->options->job->start) {
            return true;
        }

        $this->options->job->total = ( int )$this->productionDb->get_var("SELECT COUNT(1) FROM {$old}");

        if (0 == $this->options->job->total) {
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
    private function isExcludedTable($table)
    {

        $customTables = apply_filters('wpstg_clone_searchreplace_tables_exclude', array());
        $defaultTables = array('blogs');

        $tables = array_merge($customTables, $defaultTables);

        $excludedTables = array();
        foreach ($tables as $key => $value) {
            $excludedTables[] = $this->options->prefix . $value;
        }

        if (in_array($table, $excludedTables)) {
            return true;
        }
        return false;
    }

    /**
     * Finish the step
     */
    private function finishStep()
    {
        // This job is not finished yet
        if ($this->options->job->total > $this->options->job->start) {
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
/*    private function dropTable($new)
    {
        $old = $this->stagingDb->get_var($this->stagingDb->prepare("SHOW TABLES LIKE %s", $new));

        if (!$this->shouldDropTable($new, $old)) {
            return;
        }

        $this->log("DB Search & Replace: {$new} already exists, dropping it first");
        $this->stagingDb->query("DROP TABLE {$new}");
    }*/

    /**
     * Check if table needs to be dropped
     * @param string $new
     * @param string $old
     * @return bool
     */
/*    private function shouldDropTable($new, $old)
    {
        return (
            $old == $new &&
            (
                !isset($this->options->job->current) ||
                !isset($this->options->job->start) ||
                0 == $this->options->job->start
            )
        );
    }*/

    /**
     * Check if WP is installed in subdir
     * @return boolean
     */
    private function isSubDir()
    {
        // Compare names without scheme to bypass cases where siteurl and home have different schemes http / https
        // This is happening much more often than you would expect
        $siteurl = preg_replace('#^https?://#', '', rtrim(get_option('siteurl'), '/'));
        $home = preg_replace('#^https?://#', '', rtrim(get_option('home'), '/'));

        if ($home !== $siteurl) {
            return true;
        }
        return false;
    }

}
