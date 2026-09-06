<?php

namespace WPStaging\Backend\Modules\Jobs;

use stdClass;
use wpdb;
use WPStaging\Core\WPStaging;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\Utils\Multisite;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Traits\DatabaseSearchReplaceTrait;
use WPStaging\Framework\Traits\DbRowsGeneratorTrait;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Utils\Escape;











class SearchReplace extends CloningProcess
{
    use TotalStepsAreNumberOfTables;
    use DbRowsGeneratorTrait;
    use DatabaseSearchReplaceTrait;

 
    const FILTER_CLONE_SEARCH_REPLACE_EXCLUDED_ROWS = 'wpstg_clone_searchreplace_excl_rows';

 
    const FILTER_CLONE_SEARCH_REPLACE_EXCLUDED = 'wpstg_clone_searchreplace_excl';

 
    const FILTER_CLONE_SEARCH_REPLACE_PARAMS = 'wpstg_clone_searchreplace_params';

 
    const MAX_CELL_SIZE = 5000000;






    protected $maxFailedAttempts = 10;






    protected $processed;




    private $total = 0;





    private $sourceHostname;





    private $destinationHostname;





    private $strings;





    public $tmpPrefix;




    public function initialize()
    {
        $this->setupMemoryExhaustFile();
        $this->initializeDbObjects();
        $this->total = count($this->options->tables);
        $this->tmpPrefix = $this->options->prefix;
        $this->strings = new Strings();
        $this->sourceHostname = $this->getSourceHostname();
        $this->destinationHostname = $this->getDestinationHostname();
    }

    public function start()
    {
 
        if ($this->options->totalSteps === 0) {
            $this->prepareResponse(true, false);
        }

        $this->run();

 
        $this->saveOptions();

        return (object)$this->response;
    }






    protected function execute()
    {
 
        if ($this->isOverThreshold()) {
 
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

 
        if ($this->options->currentStep > $this->total || !isset($this->options->tables[$this->options->currentStep])) {
            $this->prepareResponse(true, false);
            return false;
        }

 
        if (in_array($this->options->tables[$this->options->currentStep], $this->options->excludedTables)) {
            $this->prepareResponse();
            return true;
        }

 
        if (!$this->updateTable($this->options->tables[$this->options->currentStep])) {
 
            $this->prepareResponse(false, false);

 
            return true;
        }


 
        $this->prepareResponse();

 
        return true;
    }






    private function updateTable($tableName)
    {
        $strings      = new Strings();
        $table        = $strings->strReplaceFirst(WPStaging::getTablePrefix(), '', $tableName);
        $newTableName = $this->tmpPrefix . $table;

 
        $this->setJob($newTableName);

 
        if (!$this->startJob($newTableName, $tableName)) {
            return true;
        }

 
        $this->startReplace($newTableName);

 
        return $this->finishStep();
    }












    private function getDestinationHostname()
    {
 
        if ($this->options->mainJob === Job::UPDATE) {
 
            if (!empty($this->options->cloneHostname)) {
                return $this->strings->getUrlWithoutScheme($this->options->cloneHostname);
            }

            return $this->strings->getUrlWithoutScheme($this->options->destinationHostname);
        }

 
        if (!empty($this->options->cloneHostname)) {
            return $this->strings->getUrlWithoutScheme($this->options->cloneHostname);
        }

 
        if ($this->isSubDir()) {
            return $this->strings->getUrlWithoutScheme(trailingslashit($this->options->destinationHostname) . $this->getSubDir() . '/' . $this->options->cloneDirectoryName);
        }

        if ($this->isMultisiteAndPro()) {
            $multisiteHostname = (new Multisite())->getHomeDomainWithoutScheme();
 
            $multisitePath = defined('PATH_CURRENT_SITE') ? PATH_CURRENT_SITE : '/';

            return rtrim($multisiteHostname, '/\\') . $multisitePath . $this->options->cloneDirectoryName;
        }

 
        return $this->strings->getUrlWithoutScheme(trailingslashit($this->options->destinationHostname) . $this->options->cloneDirectoryName);
    }





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

 
        $this->searchReplace($table, []);

        if ($this->isSearchReplaceGeneratorDisabled()) {
            $this->options->job->start += $this->settings->querySRLimit;
        }
    }








    protected function getColumns($table)
    {
        $primaryKeys = [];
        $columns     = [];
        $fields      = $this->stagingDb->get_results('DESCRIBE ' . $table);

        if (empty($fields)) {
 
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

 
        $args = Hooks::applyFilters(self::FILTER_CLONE_SEARCH_REPLACE_PARAMS, $args);

 
        $primaryKeyAndColumns = $this->getColumns($table);

        if ($primaryKeyAndColumns === false) {
 
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

        if ($this->isSearchReplaceGeneratorDisabled()) {
            $data = $this->stagingDb->get_results("SELECT * FROM $table LIMIT $offset, $limit", ARRAY_A);
        } else {
            $this->lastFetchedPrimaryKeyValue = is_object($this->options->job) && property_exists($this->options->job, 'lastProcessedId') ? $this->options->job->lastProcessedId : false;
            $data = $this->rowsGenerator($table, $offset, $limit, $this->stagingDb);
        }

 
        $filter = $this->excludedStrings();

        $filter = apply_filters(self::FILTER_CLONE_SEARCH_REPLACE_EXCLUDED_ROWS, $filter);

        $processed = 0;

 
        foreach ($data as $row) {
            $processed++;
            $currentRow++;
            $updateSql = [];
            $whereSql = [];
            $doUpdate = false;

            if ($this->lastFetchedPrimaryKeyValue !== false) {
                $this->lastFetchedPrimaryKeyValue = $row[$this->numericPrimaryKey];
            }

 
            if (isset($row['option_name']) && in_array($row['option_name'], $filter)) {
                continue;
            }

 
            if (isset($row['option_name']) && $args['skip_transients'] === 'on' && strpos($row['option_name'], '_transient') !== false
            ) {
                continue;
            }

 
            if (isset($row['option_value']) && strlen($row['option_value']) >= self::MAX_CELL_SIZE) {
                continue;
            }

 
            foreach ($columns as $column) {
                $dataRow = $row[$column];

 
                if (is_null($dataRow)) {
                    continue;
                }

 
                $size = strlen($dataRow);
                if ($size >= self::MAX_CELL_SIZE) {
                    continue;
                }

 
                if (in_array($column, $primaryKeys)) {
                    $whereSql[] = $column . ' = "' . WPStaging::make(Escape::class)->mysqlRealEscapeString($dataRow) . '"';
                    continue;
                }

 
                if ($args['replace_guids'] !== 'on' && $column === 'guid') {
                    continue;
                }

                $excludes = Hooks::applyFilters(self::FILTER_CLONE_SEARCH_REPLACE_EXCLUDED, []);
                $searchReplace = new \WPStaging\Framework\Database\SearchReplace($args['search_for'], $args['replace_with'], $args['case_insensitive'], $excludes);
 
                $siteInfo = WPStaging::make(SiteInfo::class);
                $searchReplace->setWpBakeryActive($siteInfo->isWpBakeryActive());
                $dataRow = $searchReplace->replaceExtended($dataRow);

                $sizeAfterReplace = strlen($dataRow);
                if ($sizeAfterReplace >= self::MAX_CELL_SIZE) {
                    $this->log(
                        sprintf('Skipped column %s of row %d in %s: search & replace grew it to %d bytes', $column, $currentRow, $table, $sizeAfterReplace),
                        Logger::TYPE_WARNING
                    );
                    continue;
                }

 
                if ($row[$column] !== $dataRow) {
                    $updateSql[] = $column . " = '" . WPStaging::make(Escape::class)->mysqlRealEscapeString($dataRow) . "'";
                    $doUpdate = true;
                }
            }

 
            if ($args['dry_run'] === 'on') {
 
            } elseif ($doUpdate && !empty($whereSql)) {
 
                $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $updateSql) . ' WHERE ' . implode(' AND ', array_filter($whereSql));
                $result = $this->stagingDb->query($sql);

                if ($result === false) {
                    $this->log(
                        sprintf('Error updating row %d of table %s: %s', $currentRow, $table, $this->stagingDb->last_error),
                        Logger::TYPE_ERROR
                    );
                }
            }
        } 

        unset($row, $updateSql, $whereSql, $sql, $currentRow);

        if (!$this->isSearchReplaceGeneratorDisabled()) {
            $this->updateJobStart($processed, $this->stagingDb, $table);
        }

 
        $this->stagingDb->flush();
        return true;
    }





    private function setJob($table)
    {
        if (!empty($this->options->job->current)) {
            return;
        }

 
        if (!is_object($this->options->job)) {
            $this->options->job = new stdClass();
        }

        $this->options->job->current = $table;
        $this->options->job->start = 0;
    }







    private function startJob($newTableName, $oldTableName)
    {
        if ($this->isExcludedTable($newTableName)) {
            return false;
        }

 
        $result = $this->productionDb->query("SHOW TABLES LIKE '{$oldTableName}'");
        if (!$result || $result === 0) {
            return false;
        }

        if (!isset($this->options->job->failedAttempts)) {
            $this->options->job->failedAttempts = 0;
        }

        if ($this->options->job->start !== 0) {
 
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




    protected function finishStep()
    {
 
        if (!$this->noResultRows && ($this->options->job->total > $this->options->job->start)) {
            return false;
        }

 
        $this->options->clonedTables[] = $this->options->tables[$this->options->currentStep];

 
        $this->options->job = new stdClass();

        return true;
    }












    protected function updateJobStart($processed, wpdb $db, $table)
    {
        $this->processed = absint($processed);

 
 
        if ($this->executeNumericPrimaryKeyQuery && $this->lastFetchedPrimaryKeyValue !== false) {
            $this->options->job->lastProcessedId = $this->lastFetchedPrimaryKeyValue;
            $this->options->job->start += $this->processed;
            return;
        }

 
        $minimumProcessed = 1;






        if ($this->processed === 0) {
            $this->logDebug('SEARCH_REPLACE: Processed is zero');

            $totalRowsInTable = $db->get_var("SELECT COUNT(*) FROM $table");

            if (is_numeric($totalRowsInTable)) {
                $this->logDebug("SEARCH_REPLACE: Rows count is numeric: $totalRowsInTable");
 
                $minimumProcessed = min(max((int)$totalRowsInTable / 100, 1), $this->settings->querySRLimit);
            } else {
                $this->logDebug(sprintf("SEARCH_REPLACE: Rows count is not numeric. Type: %s. Json encoded value: %s", gettype($totalRowsInTable), wp_json_encode($totalRowsInTable)));
 
                $minimumProcessed = $this->settings->querySRLimit;
            }

            $this->logDebug("SEARCH_REPLACE: Minimum processed is: $minimumProcessed");
        }

        $this->options->job->start += max($processed, $minimumProcessed);
    }







    public function getProcessed()
    {
        return $this->processed;
    }

    protected function logDebug($message)
    {
        \WPStaging\functions\debug_log($message, 'debug');
    }




    protected function isSearchReplaceGeneratorDisabled(): bool
    {
        if (!defined('WPSTG_DISABLE_SEARCH_REPLACE_GENERATOR')) {
            return false;
        }

        return constant('WPSTG_DISABLE_SEARCH_REPLACE_GENERATOR');
    }
}
