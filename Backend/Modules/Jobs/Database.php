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
use WPStaging\Framework\Filesystem\Filesystem;

/**
 * Class Database
 * @package WPStaging\Backend\Modules\Jobs
 */
class Database extends CloningProcess
{
    use TotalStepsAreNumberOfTables;

    /**
     * @var DatabaseCloningService
     */
    private $databaseCloningService;

    /**
     * @var int
     */
    private $total = 0;

    /**
     * Initialize
     * @throws \Exception
     */
    public function initialize()
    {
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
        // if mainJob is 'Reset', add one extra pre step for deleting all tables
        if ($this->options->mainJob === 'resetting') {
            $this->total++;
        }
    }

    /**
     *
     */
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

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     * @throws \Exception
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
        if ($this->options->currentStep > $this->total || !$this->isRunning()) {
            $this->prepareResponse(true, false);
            return false;
        }

        if (!$this->deleteAllTables()) {
            // Prepare Response
            $this->prepareResponse(false, false);

            // Not finished
            return true;
        }

        // decrement the tableIndex if mainJob is 'resetting'
        $tableIndex = $this->options->currentStep;
        if ($this->options->mainJob === 'resetting') {
            $tableIndex--;
        }

        // Copy table
        if (isset($this->options->tables[$tableIndex]) && !$this->copyTable($this->options->tables[$tableIndex])) {
            // Prepare Response
            $this->prepareResponse(false, false);

            // Not finished
            return true;
        }

        $this->prepareResponse();

        // Not finished
        return true;
    }

    /**
     * Delete all tables in staging site if the mainJob is 'resetting'
     *
     * @return bool
     * @throws \Exception
     */
    private function deleteAllTables()
    {
        if ($this->options->mainJob !== 'resetting') {
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
            $this->log(__('DB: Removing all clone database tables.', 'wp-staging'));
            $this->options->databaseResettingStatus = 'processing';
            $this->saveOptions();
        }

        // TODO: inject using DI
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

    /**
     * Check if table already exists
     * @param string $name
     * @return bool
     */
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

    /**
     * Check if table already exists and the main job is not updating
     * @param string $name
     * @return bool
     */
    private function shouldAbortIfTableExist($name)
    {
        return isset($this->options->mainJob) && $this->options->mainJob !== 'updating' && $this->isTableExist($name);
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
        $this->options->clonedTables[] = isset($this->options->tables[$this->options->currentStep]) ? $this->options->tables[$this->options->currentStep] : false;

        // Reset job
        $this->options->job = new stdClass();

        return true;
    }

    /**
     * Check if external database is used and if It's not pro version
     * @return bool
     */
    private function abortIfExternalButNotPro()
    {
        if (defined('WPSTGPRO_VERSION')) {
            return false;
        }

        $this->returnException(__("This staging site is located in another database and needs to be edited with <a href='https://wp-staging.com' target='_blank'>WP STAGING Pro</a>", "wp-staging"));

        return true;
    }

    /**
     * Set the job
     * @param string $table
     */
    private function setJob($table)
    {
        if (isset($this->options->job->current)) {
            return;
        }

        $this->options->job->current = $table;
        $this->options->job->start   = 0;
    }

    /**
     * @param $srcTableName string|object
     * @return bool
     * @throws \Exception
     */
    private function copyTable($srcTableName)
    {
        $srcTableName = is_object($srcTableName) ? $srcTableName->name : $srcTableName;

        $destTableName = $this->getStagingPrefix() . $this->databaseCloningService->removeDBPrefix($srcTableName);

        if ($this->isMultisiteAndPro()) {
            // Build full name of table 'users' from main site e.g. wp_users
            if ($this->databaseCloningService->removeDBPrefix($srcTableName) === 'users') {
                $srcTableName = $this->productionDb->base_prefix . 'users';
            }
            // Build full name of table 'usermeta' from main site e.g. wp_usermeta
            if ($this->databaseCloningService->removeDBPrefix($srcTableName) === 'usermeta') {
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

        $this->copyData($destTableName, $srcTableName);

        return $this->finishStep();
    }

    /**
     * Copy data from old table to new table
     * @param string $destTableName
     * @param string $srcTableName
     */
    private function copyData($destTableName, $srcTableName)
    {
        $this->databaseCloningService->copyData($srcTableName, $destTableName, $this->options->job->start, $this->settings->queryLimit);
        // Set new offset
        $this->options->job->start += $this->settings->queryLimit;
    }

    /**
     * Is table excluded from database copying processing?
     * @param string $table
     * @return bool
     */
    private function isExcludedTable($table)
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

    /**
     * Start Job and create tables
     * @param string $destinationTable
     * @param string $sourceTable
     * @return bool
     * @throws \Exception
     */
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

    /**
     * Add wp_users and wp_usermeta to the tables if they do not exist
     * because they are not available in MU installation, but we need them on the staging site
     *
     * return void
     * @throws \Exception
     */
    private function addMissingTables()
    {
        $dbPrefix = WPStaging::getTablePrefix();
        // Early bail: if updating
        if (isset($this->options->mainJob) && $this->options->mainJob === 'updating') {
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

    /**
     * @return bool
     */
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

    /**
     * Get new prefix for the staging site
     * @return string
     */
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

    /**
     * Return fatal error and stops here if sub folder already exists
     * and mainJob is not updating and resetting the clone
     * @return bool
     */
    private function abortIfDirectoryNotEmpty()
    {
        $path = trailingslashit($this->options->cloneDir);
        if (isset($this->options->mainJob) && $this->options->mainJob !== 'resetting' && $this->options->mainJob !== 'updating' && is_dir($path) && !wpstg_is_empty_dir($path)) {
            $this->returnException(" Can not continue for security purposes. Directory {$path} is not empty! Use FTP or a file manager plugin and make sure it does not contain any files. ");
            return true;
        }

        return false;
    }

    /**
     * Return fatal error, if unable to create staging site directory
     * @return bool
     */
    private function abortIfDirectoryNotCreated()
    {
        // Early bail if not a new staging site
        if (isset($this->options->mainJob) && ($this->options->mainJob === 'resetting' || $this->options->mainJob === 'updating')) {
            return false;
        }

        // Early bail if directory already exists
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

    /**
     * Stop cloning if database prefix contains hyphen
     * @return bool
     */
    private function abortIfPrefixContainsInvalidCharacter()
    {
        // make sure prefix doesn't contain any invalid character
        // same condition as in WordPress wpdb::set_prefix() method
        if (preg_match('|[^a-z0-9_]|i', $this->options->databasePrefix)) {
            $this->returnException(__("Table prefix contains invalid character(s). Use different prefix with valid characters.", 'wp-staging'));
            return true;
        }

        return false;
    }

    /**
     * Check if the copy process started or not.
     * @return bool
     */
    private function isCopyProcessStarted()
    {
        return isset($this->options->job->copyProcessStarted) && $this->options->job->copyProcessStarted === true;
    }
}
