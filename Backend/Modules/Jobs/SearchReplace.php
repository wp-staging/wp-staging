<?php

namespace WPStaging\Backend\Modules\Jobs;

use stdClass;
use wpdb;
use WPStaging\Core\WPStaging;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\Utils\Multisite;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Traits\DatabaseSearchReplaceTrait;
use WPStaging\Framework\Traits\DbRowsGeneratorTrait;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Utils\Escape;

/**
 * Class SearchReplace
 *
 * Used for CLONING
 * @see \WPStaging\Backend\Pro\Modules\Jobs\SearchReplace Used for PUSHING
 *
 * @todo Unify those
 *
 * @package WPStaging\Backend\Modules\Jobs
 */
class SearchReplace extends CloningProcess
{
    use TotalStepsAreNumberOfTables;
    use DbRowsGeneratorTrait;
    use DatabaseSearchReplaceTrait;

    /**
     * The maximum number of failed attempts after which the Job should just move on.
     *
     * @var int
     */
    protected $maxFailedAttempts = 10;

    /**
     * The number of processed items, or `null` if the job did not run yet.
     *
     * @var int|null
     */
    protected $processed;

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
     * Initialize
     */
    public function initialize()
    {
        $this->initializeDbObjects();
        $this->total = count($this->options->tables);
        $this->tmpPrefix = $this->options->prefix;
        $this->strings = new Strings();
        $this->sourceHostname = $this->getSourceHostname();
        $this->destinationHostname = $this->getDestinationHostname();
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

        return (object)$this->response;
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
        $strings      = new Strings();
        $table        = $strings->str_replace_first(WPStaging::getTablePrefix(), '', $tableName);
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
     * @param string $table
     */
    private function startReplace($table)
    {
        $rows = $this->options->job->start + $this->settings->querySRLimit;

        if ((int)$this->settings->querySRLimit <= 1) {
            $this->logDebug(sprintf('%s - $this->settings->querySRLimit is too low. Typeof: %s. JSON Encoded Value: %s', __METHOD__, gettype($this->settings->querySRLimit), wp_json_encode($this->settings->querySRLimit)));
        }

        if ((int)$rows <= 1) {
            $this->logDebug(sprintf('%s - $rows is too low.', __METHOD__));
        }

        $this->log(
            "DB Search & Replace:  Table {$table} {$this->options->job->start} to {$rows} records"
        );

        // Search & Replace
        $this->searchReplace($table, []);

        if (defined('WPSTG_DISABLE_SEARCH_REPLACE_GENERATOR') && WPSTG_DISABLE_SEARCH_REPLACE_GENERATOR) {
            $this->options->job->start += $this->settings->querySRLimit;
        }
    }

    /**
     * Gets the columns in a table.
     * @access public
     * @param string $table The table to check.
     * @return array|false Either the primary key and columns structures, or `false` to indicate the query
     *                     failed or the table is not describe-able.
     */
    protected function getColumns($table)
    {
        $primaryKeys = [];
        $columns     = [];
        $fields      = $this->stagingDb->get_results('DESCRIBE ' . $table);

        if (empty($fields)) {
            // Either there was an error or the table has no columns.
            return false;
        }

        if (is_array($fields)) {
            foreach ($fields as $column) {
                $columns[] = $column->Field;
                if ($column->Key === 'PRI') {
                    $primaryKeys[] = $column->Field;
                }
            }
        }

        return [$primaryKeys, $columns];
    }

    /**
     *
     * @param string $table The table to run the replacement on.
     * @param array $args An associative array containing arguments for this run.
     * @return bool Whether the search-replace operation was successful or not.
     */
    private function searchReplace($table, $args)
    {
        $table = esc_sql($table);

        $args['search_for'] = $this->generateHostnamePatterns($this->sourceHostname);
        $args['search_for'][] = ABSPATH;

        $args['replace_with'] = $this->generateHostnamePatterns($this->destinationHostname);
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
        $primaryKeyAndColumns = $this->getColumns($table);

        if (false === $primaryKeyAndColumns) {
            // Stop here: for some reason the table cannot be described or there was an error.
            ++$this->options->job->failedAttempts;
            return false;
        }

        list($primaryKeys, $columns) = $primaryKeyAndColumns;

        if ($this->options->job->current !== $table) {
            $this->logDebug(sprintf('We are using the LIMITS of a table different than the table we are parsing now. Table being parsed: %s. Table that we are using "start" from: %s. Start: %s', $table, $this->options->job->current, $this->options->job->start));
        }

        $currentRow = 0;
        $offset = $this->options->job->start;
        $limit = $this->settings->querySRLimit;

        /// DEBUG
/*        $this->logDebug(
            sprintf(
                'SearchReplace-beforeRowsGenerator: max-memory-limit=%s; script-memory-limit=%s; memory-usage=%s; execution-time-limit=%s; running-time=%s; is-threshold=%s',
                $this->getMaxMemoryLimit(),
                $this->getScriptMemoryLimit(),
                $this->getMemoryUsage(),
                $this->findExecutionTimeLimit(),
                $this->getRunningTime(),
                ($this->isThreshold() ? 'yes' : 'no')
            )
        );*/
        /// DEBUG

        if (defined('WPSTG_DISABLE_SEARCH_REPLACE_GENERATOR') && WPSTG_DISABLE_SEARCH_REPLACE_GENERATOR) {
            $data = $this->stagingDb->get_results("SELECT * FROM $table LIMIT $offset, $limit", ARRAY_A);
        } else {
            $this->lastFetchedPrimaryKeyValue = property_exists($this->options->job, 'lastProcessedId') ? $this->options->job->lastProcessedId : false;
            $data = $this->rowsGenerator($table, $offset, $limit, $this->stagingDb);
        }

        // Filter certain rows (of other plugins)
        $filter = $this->excludedStrings();

        $filter = apply_filters('wpstg_clone_searchreplace_excl_rows', $filter);

        $processed = 0;

        /// DEBUG
        /*
        $this->logDebug(
            sprintf(
                'SearchReplace-beforeRowProcessing: max-memory-limit=%s; script-memory-limit=%s; memory-usage=%s; execution-time-limit=%s; running-time=%s; is-threshold=%s',
                $this->getMaxMemoryLimit(),
                $this->getScriptMemoryLimit(),
                $this->getMemoryUsage(),
                $this->findExecutionTimeLimit(),
                $this->getRunningTime(),
                ($this->isThreshold() ? 'yes' : 'no')
            )
        );
        */
        /// DEBUG

        // Go through the table rows
        foreach ($data as $row) {
            $processed++;
            $currentRow++;
            $updateSql = [];
            $whereSql = [];
            $doUpdate = false;

            if ($this->lastFetchedPrimaryKeyValue !== false) {
                $this->lastFetchedPrimaryKeyValue = $row[$this->numericPrimaryKey];
            }

            // Skip rows
            if (isset($row['option_name']) && in_array($row['option_name'], $filter)) {
                continue;
            }

            // Skip transients (There can be thousands of them. Save memory and increase performance)
            if (
                isset($row['option_name']) && $args['skip_transients'] === 'on' && strpos($row['option_name'], '_transient')
                !== false
            ) {
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
                if (in_array($column, $primaryKeys)) {
                    $whereSql[] = $column . ' = "' . WPStaging::make(Escape::class)->mysqlRealEscapeString($dataRow) . '"';
                    continue;
                }

                // Skip GUIDs by default.
                if ($args['replace_guids'] !== 'on' && $column === 'guid') {
                    continue;
                }

                $excludes = apply_filters('wpstg_clone_searchreplace_excl', []);
                $searchReplace = new \WPStaging\Framework\Database\SearchReplace($args['search_for'], $args['replace_with'], $args['case_insensitive'], $excludes);
                /** @var SiteInfo */
                $siteInfo = WPStaging::make(SiteInfo::class);
                $searchReplace->setWpBakeryActive($siteInfo->isWpBakeryActive());
                $dataRow = $searchReplace->replaceExtended($dataRow);

                // Something was changed
                if ($row[$column] !== $dataRow) {
                    $updateSql[] = $column . ' = "' . WPStaging::make(Escape::class)->mysqlRealEscapeString($dataRow) . '"';
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
                    $partialQuery = substr($sql, 0, 100);
                    $this->log(
                        "Error updating row {$currentRow} SQL: {$partialQuery}",
                        Logger::TYPE_ERROR
                    );
                }
            }
        } // end row loop

        /// DEBUG
/*        $this->logDebug(
            sprintf(
                'SearchReplace-afterRowsProcessing: processed=%s; max-memory-limit=%s; script-memory-limit=%s; memory-usage=%s; execution-time-limit=%s; running-time=%s; is-threshold=%s',
                $processed,
                $this->getMaxMemoryLimit(),
                $this->getScriptMemoryLimit(),
                $this->getMemoryUsage(),
                $this->findExecutionTimeLimit(),
                $this->getRunningTime(),
                ($this->isThreshold() ? 'yes' : 'no')
            )
        );*/
        /// DEBUG

        unset($row,$updateSql,$whereSql,$sql,$currentRow);

        if (
            !defined('WPSTG_DISABLE_SEARCH_REPLACE_GENERATOR') ||
            (defined('WPSTG_DISABLE_SEARCH_REPLACE_GENERATOR') && !WPSTG_DISABLE_SEARCH_REPLACE_GENERATOR)
        ) {
            $this->updateJobStart($processed, $this->stagingDb, $table);
        }

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
     * @param string $newTableName
     * @param string $oldTableName
     * @return bool
     */
    private function startJob($newTableName, $oldTableName)
    {
        if ($this->isExcludedTable($newTableName)) {
            return false;
        }

        // Table does not exist
        $result = $this->productionDb->query("SHOW TABLES LIKE '{$oldTableName}'");
        if (!$result || $result === 0) {
            return false;
        }

        if (!isset($this->options->job->failedAttempts)) {
            $this->options->job->failedAttempts = 0;
        }

        if ($this->options->job->start !== 0) {
            // The job was attempted too many times and should be skipped now.
            return !($this->options->job->failedAttempts > $this->maxFailedAttempts);
        }

        $this->options->job->total = (int)$this->productionDb->get_var("SELECT COUNT(1) FROM {$oldTableName}");
        $this->options->job->failedAttempts = 0;

        if ($this->options->job->total === 0) {
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

        $tables = $this->excludedTableService->getExcludedTablesForSearchReplace($this->isNetworkClone());

        $excludedAllTables = [];
        foreach ($tables as $key => $value) {
            $excludedAllTables[] = $this->options->prefix . ltrim($value, '_');
        }

        if (in_array($table, $excludedAllTables)) {
            $this->log("DB Search & Replace: Table {$table} excluded by WP STAGING", Logger::TYPE_INFO);
            return true;
        }

        return false;
    }

    /**
     * Finish the step
     */
    protected function finishStep()
    {
        // This job is not finished yet
        if (!$this->noResultRows && ($this->options->job->total > $this->options->job->start)) {
            return false;
        }

        // Add it to cloned tables listing
        $this->options->clonedTables[] = $this->options->tables[$this->options->currentStep];

        // Reset job
        $this->options->job = new stdClass();

        return true;
    }

    /**
     * Updates the (next) job start to reflect the number of actually processed rows.
     *
     * If nothing was processed, then the job start  will be ticked by 1.
     *
     * @param int $processed The  number of actually processed rows in this run.
     * @param wpdb $db The wpdb instance being used to process.
     * @param string $table The table being processed.
     *
     * @return void The method does not return any value.
     */
    protected function updateJobStart($processed, wpdb $db, $table)
    {
        $this->processed = absint($processed);

        // If it is a numeric primary key table execution,
        // Save the last processed primary key value for the next request
        if ($this->executeNumericPrimaryKeyQuery && $this->lastFetchedPrimaryKeyValue !== false) {
            $this->options->job->lastProcessedId = $this->lastFetchedPrimaryKeyValue;
            $this->options->job->start += $this->processed;
            return;
        }

        // We make sure to increment the offset at least in 1 to avoid infinite loops.
        $minimumProcessed = 1;

        /*
         * There are some scenarios where we couldn't process any rows in this request.
         * The exact causes of this is still under investigation, but to mitigate this
         * effect, we will smartly set the offset for the next job based on some context.
         */
        if ($this->processed === 0) {
            $this->logDebug('SEARCH_REPLACE: Processed is zero');

            $totalRowsInTable = $db->get_var("SELECT COUNT(*) FROM $table");

            if (is_numeric($totalRowsInTable)) {
                $this->logDebug("SEARCH_REPLACE: Rows count is numeric: $totalRowsInTable");
                // Skip 1% of the current table on each iteration, with a minimum of 1 and a maximum of the query limit.
                $minimumProcessed = min(max((int)$totalRowsInTable / 100, 1), $this->settings->querySRLimit);
            } else {
                $this->logDebug(sprintf("SEARCH_REPLACE: Rows count is not numeric. Type: %s. Json encoded value: %s", gettype($totalRowsInTable), wp_json_encode($totalRowsInTable)));
                // Unexpected result from query. Set the offset to the limit.
                $minimumProcessed = $this->settings->querySRLimit;
            }

            $this->logDebug("SEARCH_REPLACE: Minimum processed is: $minimumProcessed");
        }

        $this->options->job->start += max($processed, $minimumProcessed);
    }

    /**
     * Returns the number of rows processed by the job.
     *
     * @return int|null Either the number of rows processed by the Job, or `null` if the Job did
     *                  not run yet.
     */
    public function getProcessed()
    {
        return $this->processed;
    }

    protected function logDebug($message)
    {
        \WPStaging\functions\debug_log($message, 'debug');
    }
}
