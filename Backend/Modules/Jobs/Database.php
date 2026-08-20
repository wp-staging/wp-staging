<?php

namespace WPStaging\Backend\Modules\Jobs;

use stdClass;
use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\CloningProcess\CloningDto;
use WPStaging\Framework\CloningProcess\Database\DatabaseCloningService;
use WPStaging\Framework\Adapter\Database as DatabaseAdapter;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Traits\TablePrefixValidator;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Staging\Service\Database\RowsExporter;





class Database extends CloningProcess
{
    use TotalStepsAreNumberOfTables;
    use TablePrefixValidator;




    private $databaseCloningService;




    private $total = 0;





    public function initialize()
    {
        $this->setupMemoryExhaustFile();
        $this->initializeDbObjects();
        $this->abortIfDirectoryNotEmpty();
        $this->abortIfDirectoryNotCreated();
        $this->abortIfPrefixContainsInvalidCharacter();
        if (!$this->isExternalDatabase()) {
            $this->abortIfStagingPrefixEqualsProdPrefix();
        } else {
            $this->abortIfExternalButNotPro();
        }

        $this->generateDto();
        $this->addMissingTables();
        $this->total = count($this->options->tables);
 
        if ($this->options->mainJob === Job::RESET) {
            $this->total++;
        }
    }




    protected function generateDto()
    {
        $this->databaseCloningService = new DatabaseCloningService(
            new CloningDto(
                $this,
                $this->stagingDb,
                $this->productionDb,
                $this->isExternalDatabase(),
                $this->isMultisiteAndPro(),
                $this->isExternalDatabase() ? $this->options->databaseServer : null,
                $this->isExternalDatabase() ? $this->options->databaseUser : null,
                $this->isExternalDatabase() ? $this->options->databasePassword : null,
                $this->isExternalDatabase() ? $this->options->databaseDatabase : null,
                $this->isExternalDatabase() ? $this->options->databaseSsl : false
            )
        );
    }







    protected function execute()
    {
 
        if ($this->isOverThreshold()) {
 
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

 
        if ($this->options->currentStep > $this->total || !$this->isRunning()) {
            $this->prepareResponse(true, false);
            return false;
        }

        if (!$this->deleteAllTables()) {
 
            $this->prepareResponse(false, false);

 
            return true;
        }

 
        $tableIndex = $this->options->currentStep;
        if ($this->options->mainJob === Job::RESET) {
            $tableIndex--;
        }

 
        if (isset($this->options->tables[$tableIndex]) && !$this->copyTable($this->options->tables[$tableIndex])) {
 
            $this->prepareResponse(false, false);

 
            return true;
        }

        $this->prepareResponse();

 
        return true;
    }







    private function deleteAllTables()
    {
        if ($this->options->mainJob !== Job::RESET) {
            return true;
        }

        if ($this->options->currentStep !== 0) {
            return true;
        }

        if (!isset($this->options->databaseResettingStatus)) {
            $this->options->databaseResettingStatus = 'pending';
            $this->saveOptions();
        }

        if ($this->options->databaseResettingStatus === 'finished') {
            return true;
        }

        if ($this->options->databaseResettingStatus === 'pending') {
            $this->log('#################### Start Reset Job ####################');
            $this->log('DB: Removing all staging database tables.');
            $this->options->databaseResettingStatus = 'processing';
            $this->saveOptions();
        }

 
        $tableService = new TableService(new DatabaseAdapter($this->stagingDb));
        $tableService->setShouldStop([$this, 'isOverThreshold']);
        if (!$tableService->deleteTablesStartWith($this->getStagingPrefix())) {
            return false;
        }

        $this->options->databaseResettingStatus = 'finished';
        $this->saveOptions();

        $this->prepareResponse();
        return true;
    }






    private function isTableExist($name)
    {
        $old = $this->stagingDb->get_var($this->stagingDb->prepare("SHOW TABLES LIKE %s", $name));

        return (
            $old === $name &&
            (
                !isset($this->options->job->current, $this->options->job->start) || $this->options->job->start === 0
            )
        );
    }






    private function shouldAbortIfTableExist($name)
    {
        return isset($this->options->mainJob) && $this->options->mainJob !== Job::UPDATE && $this->isTableExist($name);
    }




    private function finishStep()
    {
 
        if ($this->options->job->total > $this->options->job->start) {
            return false;
        }

        $this->finishDataCopying();

        return true;
    }




    private function finishDataCopying()
    {
 
        $this->options->clonedTables[] = isset($this->options->tables[$this->options->currentStep]) ? $this->options->tables[$this->options->currentStep] : false;

 
        $this->options->job = new stdClass();
    }





    private function abortIfExternalButNotPro()
    {
        if (defined('WPSTGPRO_VERSION')) {
            return false;
        }

        $this->returnException(__("This staging site is located in another database and needs to be edited with <a href='https://wp-staging.com' target='_blank'>WP STAGING Pro</a>", "wp-staging"));

        return true;
    }





    private function setJob($table)
    {
        if (isset($this->options->job->current)) {
            return;
        }

 
        if (!is_object($this->options->job)) {
            $this->options->job = new stdClass();
        }

        $this->options->job->current = $table;
        $this->options->job->start   = 0;
    }






    private function copyTable($srcTableName)
    {
        $srcTableName = is_object($srcTableName) ? $srcTableName->name : $srcTableName;

        $tableWithoutPrefix = $this->databaseCloningService->removeDBPrefix($srcTableName);
        $destTableName      = $this->getStagingPrefix() . $tableWithoutPrefix;

        if ($this->isMultisiteAndPro()) {
 
            if ($tableWithoutPrefix === 'users') {
                $srcTableName = $this->productionDb->base_prefix . 'users';
            }

 
            if ($tableWithoutPrefix === 'usermeta') {
                $srcTableName = $this->productionDb->base_prefix . 'usermeta';
            }
        }

        if (!$this->isCopyProcessStarted() && $this->shouldAbortIfTableExist($destTableName)) {
            $this->returnException(sprintf(__("Can not proceed. Tables beginning with the prefix '%s' already exist in the database i.e. %s. Choose another table prefix and try again.", "wp-staging"), $this->getStagingPrefix(), $destTableName));
            return true;
        }

        $this->setJob($destTableName);

        if (!$this->startJob($destTableName, $srcTableName)) {
            return true;
        }

        $tablesToExcludeData = Hooks::applyFilters(RowsExporter::FILTER_EXCLUDE_TABLES_DATA, RowsExporter::TABLES_EXCLUDED_FROM_DATA_COPYING);
        if (in_array($tableWithoutPrefix, $tablesToExcludeData)) {
            $this->log("Skipping data copy for table {$srcTableName}", Logger::TYPE_INFO);
            $this->finishDataCopying();
            return true;
        }

        $this->copyData($destTableName, $srcTableName);

        return $this->finishStep();
    }






    private function copyData($destTableName, $srcTableName)
    {
        try {
            $this->databaseCloningService->copyData($srcTableName, $destTableName, $this->options->job->start, $this->settings->queryLimit);
        } catch (FatalException $e) {
            $this->returnException($e->getMessage());
            return;
        }

 
        $this->options->job->start += $this->settings->queryLimit;
    }






    private function isExcludedTable(string $table)
    {

        if (
            in_array(
                $table,
                array_map(
                    function ($tableName) {
                        return $this->options->prefix . $tableName;
                    },
                    $this->excludedTableService->getExcludedTables($this->isNetworkClone())
                )
            )
        ) {
            return true;
        }

        return false;
    }








    private function startJob($destinationTable, $sourceTable)
    {
        if ($this->isExcludedTable($destinationTable)) {
            return false;
        }

        if ($this->options->job->start !== 0) {
            return true;
        }

        if ($this->databaseCloningService->isMissingTable($sourceTable)) {
            return true;
        }

        try {
            $this->options->job->total = 0;
            $this->options->job->total = $this->databaseCloningService->createTable($sourceTable, $destinationTable);
        } catch (FatalException $e) {
            $this->log($e->getMessage(), Logger::TYPE_WARNING);
            $this->log(__('Skipping cloning table: ' . $sourceTable, 'wp-staging'), Logger::TYPE_WARNING);
            $this->finishStep();
            return false;
        }

        if ($this->options->job->total === 0) {
            $this->finishStep();
            return false;
        }

        $this->options->job->copyProcessStarted = true;
        $this->saveOptions();
        return true;
    }








    private function addMissingTables()
    {
        $dbPrefix = WPStaging::getTablePrefix();
 
        if (isset($this->options->mainJob) && $this->options->mainJob === Job::UPDATE) {
            return;
        }

        if (!in_array($dbPrefix . 'users', $this->options->tables)) {
            $this->options->tables[] = $dbPrefix . 'users';
            $this->saveOptions();
        }

        if (!in_array($dbPrefix . 'usermeta', $this->options->tables)) {
            $this->options->tables[] = $dbPrefix . 'usermeta';
            $this->saveOptions();
        }
    }




    private function abortIfStagingPrefixEqualsProdPrefix()
    {
        $dbPrefix = WPStaging::getTablePrefix();
        if ($dbPrefix === $this->getStagingPrefix()) {
            $error = 'Fatal error 7: The destination database table prefix ' . $this->getStagingPrefix() . ' is identical to the table prefix of the production site. Go to Sites > Actions > Edit Data and correct the table prefix or contact us.';
            $this->returnException($error);
            return true;
        }

        return false;
    }





    protected function getStagingPrefix()
    {
        if ($this->isExternalDatabase()) {
            $this->options->prefix = !empty($this->options->databasePrefix) ? $this->options->databasePrefix : $this->productionDb->prefix;
        }

        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            return strtolower($this->options->prefix);
        }

        return $this->options->prefix;
    }






    private function abortIfDirectoryNotEmpty()
    {
        $path = trailingslashit($this->options->cloneDir);
        if (isset($this->options->mainJob) && $this->options->mainJob !== Job::RESET && $this->options->mainJob !== Job::UPDATE && is_dir($path) && !wpstg_is_empty_dir($path)) {
            $this->returnException(" Can not continue for security purposes. Directory {$path} is not empty! Use FTP or a file manager plugin and make sure it does not contain any files. ");
            return true;
        }

        return false;
    }





    private function abortIfDirectoryNotCreated()
    {
 
        if (isset($this->options->mainJob) && ($this->isUpdateOrResetJob())) {
            return false;
        }

 
        $path = trailingslashit($this->options->cloneDir);
        if (is_dir($path)) {
            return false;
        }

        $fs = new Filesystem();
        if ($fs->mkdir($path)) {
            return false;
        }

        $this->returnException(" Unable to create the staging site directory $path " . $fs->getLogs()[0]);
        return true;
    }





    private function abortIfPrefixContainsInvalidCharacter()
    {
 
 
        if (preg_match('|[^a-z0-9_]|i', $this->options->databasePrefix)) {
            $this->returnException(__("Table prefix contains invalid character(s). Use different prefix with valid characters.", 'wp-staging'));
            return true;
        }

        if ($this->isWpStagingReservedPrefix($this->options->databasePrefix)) {
            $this->returnException($this->getReservedPrefixErrorMessage($this->options->databasePrefix));
            return true;
        }

        return false;
    }





    private function isCopyProcessStarted()
    {
        return isset($this->options->job->copyProcessStarted) && $this->options->job->copyProcessStarted === true;
    }
}
