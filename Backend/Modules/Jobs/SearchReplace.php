<?php

namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

use WPStaging\Framework\CloningProcess\SearchReplace\SearchReplaceService;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Core\Utils\Helper;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\Utils\Multisite;

/**
 * Class Database
 * @package WPStaging\Backend\Modules\Jobs
 */
class SearchReplace extends CloningProcess
{
    use TotalStepsAreNumberOfTables;

    /**
     * @var int
     */
    private $total = 0;

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
     * @var Strings
     */
    private $strings;

    /**
     * The prefix of the new database tables which are used for the live site after updating tables
     * @var string
     */
    public $tmpPrefix;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var SearchReplaceService
     */
    private $searchReplaceService;

    /**
     * Initialize
     */
    public function initialize()
    {
        $this->initializeDbObjects();
        $this->helper = new Helper();
        $this->total = count($this->options->tables);
        $this->tmpPrefix = $this->options->prefix;
        $this->strings = new Strings();
        $this->sourceHostname = $this->getSourceHostname();
        $this->destinationHostname = $this->getDestinationHostname();
        $this->searchReplaceService = new SearchReplaceService();
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

    private function getSourceHostname()
    {
        if ($this->isSubDir()) {
            return trailingslashit($this->helper->getHomeUrlWithoutScheme()) . $this->getSubDir();
        }
        return $this->helper->getHomeUrlWithoutScheme();
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
     * Get destination hostname without scheme e.g example.com/staging or staging.example.com
     *
     * Conditions:
     * - Main job is 'update'
     * - WP installed in sub dir
     * - Target hostname in advanced settings defined (Pro version only)
     *
     * @return string
     * @todo Complex conditions. Might need refactor
     */
    private function getDestinationHostname()
    {
        // Update process: Neither 'push' nor 'clone'
        if ($this->options->mainJob === 'updating') {
            // Defined and created in advanced settings with pro version
            if (!empty($this->options->cloneHostname)) {
                return $this->strings->getUrlWithoutScheme($this->options->cloneHostname);
            }
            return $this->strings->getUrlWithoutScheme($this->options->destinationHostname);
        }

        // Clone process: Defined and created in advanced settings with pro version
        if (!empty($this->options->cloneHostname)) {
            return $this->strings->getUrlWithoutScheme($this->options->cloneHostname);
        }

        // Clone process: WP installed in sub directory under root
        if ($this->isSubDir()) {
            return $this->strings->getUrlWithoutScheme(trailingslashit($this->options->destinationHostname) . $this->getSubDir() . '/' . $this->options->cloneDirectoryName);
        }

        if ($this->isMultisiteAndPro()) {
            $multisiteHostname = (new Multisite())->getHomeDomainWithoutScheme();
            // Relative path to root of main multisite without leading or trailing slash e.g.: wordpress
            $multisitePath = defined('PATH_CURRENT_SITE') ? PATH_CURRENT_SITE : '/';

            return rtrim($multisiteHostname, '/\\') . $multisitePath . $this->options->cloneDirectoryName;
        }

        // Clone process: Default
        return $this->strings->getUrlWithoutScheme(trailingslashit($this->options->destinationHostname) . $this->options->cloneDirectoryName);
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
        $this->searchReplace($table, []);

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
        $columns = [];
        $fields = $this->stagingDb->get_results('DESCRIBE ' . $table);
        if (is_array($fields)) {
            foreach ($fields as $column) {
                $columns[] = $column->Field;
                if ($column->Key === 'PRI') {
                    $primary_key = $column->Field;
                }
            }
        }
        return [$primary_key, $columns];
    }

    /**
     *
     * @param string $table The table to run the replacement on.
     * @param array $args An associative array containing arguments for this run.
     * @return bool
     */
    private function searchReplace($table, $args)
    {
        if ($this->thirdParty->isSearchReplaceExcluded($table)) {
            $this->log("DB Search & Replace: Skip {$table}", Logger::TYPE_INFO);
            return true;
        }

        $table = esc_sql($table);

        $args['search_for'] = $this->searchReplaceService->generateHostnamePatterns($this->sourceHostname);
        $args['search_for'][] = ABSPATH;

        $args['replace_with'] = $this->searchReplaceService->generateHostnamePatterns($this->destinationHostname);
        $args['replace_with'][] = $this->options->destinationDir;

        $this->debugLog("DB Search & Replace: Search: {$args['search_for'][0]}", Logger::TYPE_INFO);
        $this->debugLog("DB Search & Replace: Replace: {$args['replace_with'][0]}", Logger::TYPE_INFO);

        $args['replace_guids'] = 'off';
        $args['dry_run'] = 'off';
        $args['case_insensitive'] = false;
        $args['skip_transients'] = 'on';

        // Allow filtering of search & replace parameters
        $args = apply_filters('wpstg_clone_searchreplace_params', $args);

        // Get columns and primary keys
        list($primary_key, $columns) = $this->get_columns($table);

        $currentRow = 0;
        $start = $this->options->job->start;
        $end = $this->settings->querySRLimit;

        $data = $this->stagingDb->get_results("SELECT * FROM $table LIMIT $start, $end", ARRAY_A);

        // Filter certain rows (of other plugins)
        $filter = $this->searchReplaceService->excludedStrings();

        $filter = apply_filters('wpstg_clone_searchreplace_excl_rows', $filter);

        // Go through the table rows
        foreach ($data as $row) {
            $currentRow++;
            $updateSql = [];
            $whereSql = [];
            $doUpdate = false;

            // Skip rows
            if (isset($row['option_name']) && in_array($row['option_name'], $filter)) {
                continue;
            }

            // Skip transients (There can be thousands of them. Save memory and increase performance)
            if (isset($row['option_name']) && $args['skip_transients'] === 'on' && strpos($row['option_name'], '_transient')
                !== false) {
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
                    $whereSql[] = $column . ' = "' . wpstg_mysql_escape_mimic($dataRow) . '"';
                    continue;
                }

                // Skip GUIDs by default.
                if ($args['replace_guids'] !== 'on' && $column === 'guid') {
                    continue;
                }

                $excludes = apply_filters('wpstg_clone_searchreplace_excl', []);
                $searchReplace = new \WPStaging\Framework\Database\SearchReplace($args['search_for'], $args['replace_with'], $args['case_insensitive'], $excludes);
                $dataRow = $searchReplace->replace($dataRow);

                // Something was changed
                if ($row[$column] != $dataRow) {
                    $updateSql[] = $column . ' = "' . wpstg_mysql_escape_mimic($dataRow) . '"';
                    $doUpdate = true;
                }
            }

            // Determine what to do with updates.
            if ($args['dry_run'] === 'on') {
                // Don't do anything if a dry run
            } elseif ($doUpdate && !empty($whereSql)) {
                // If there are changes to make, run the query.
                $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $updateSql) . ' WHERE ' . implode(' AND ', array_filter($whereSql));
                $result = $this->stagingDb->query($sql);

                if ($result === false) {
                    $this->log(
                        "Error updating row {$currentRow} SQL: {$sql}",
                        Logger::TYPE_ERROR
                    );
                }
            }
        } // end row loop
        unset($row);
        unset($updateSql);
        unset($whereSql);
        unset($sql);
        unset($currentRow);

        // DB Flush
        $this->stagingDb->flush();
        return true;
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
        if (!$result || $result === 0) {
            return false;
        }

        if ($this->options->job->start != 0) {
            return true;
        }

        $this->options->job->total = ( int )$this->productionDb->get_var("SELECT COUNT(1) FROM {$old}");

        if ($this->options->job->total == 0) {
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
        $excludedCustomTables = apply_filters('wpstg_clone_searchreplace_tables_exclude', []);
        $excludedDefaultTables = ['blogs'];

        $tables = array_merge($excludedCustomTables, $excludedDefaultTables);

        $excludedAllTables = [];
        foreach ($tables as $key => $value) {
            $excludedAllTables[] = $this->options->prefix . $value;
        }

        if (in_array($table, $excludedAllTables)) {
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
     * Check if WP is installed in subdir
     * @return boolean
     */
    private function isSubDir()
    {
        // Compare names without scheme to bypass cases where siteurl and home have different schemes http / https
        // This is happening much more often than you would expect
        $siteurl = preg_replace('#^https?://#', '', rtrim(get_option('siteurl'), '/'));
        $home = preg_replace('#^https?://#', '', rtrim(get_option('home'), '/'));

        return $home !== $siteurl;
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

        return str_replace([$home, '/'], '', $siteurl);
    }
}
