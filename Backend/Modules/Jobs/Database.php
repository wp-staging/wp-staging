<?php

namespace WPStaging\Backend\Modules\Jobs;

use stdClass;
use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\CloningProcess\CloningDto;
use WPStaging\Framework\CloningProcess\Database\DatabaseCloningService;
use WPStaging\Framework\Adapter\Database as DatabaseAdapter;
use WPStaging\Framework\Database\TableService;

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
     */
    public function initialize()
    {
        $this->initializeDbObjects();
        $this->abortIfDirectoryNotEmpty();
        $this->abortIfPrefixContainsInvalidCharacter();
        if (!$this->isExternalDatabase()) {
            $this->abortIfStagingPrefixEqualsProdPrefix();
        } else {
            $this->abortIfExternalButNotPro();
        }

        $this->generateDto();
        $this->addMissingTables();
        $this->total = count($this->options->tables);
        // if mainJob is resetting add one extra pre step for deleting all tables
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
                $this->isExternalDatabase() ? $this->options->databaseDatabase : null
            )
        );
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

        // decrement the tableIndex if mainJob was resetting
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
     * Delete all tables in staging site if the mainJob is resetting
     *
     * @return bool
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
     * Check if external database is used and if its not pro version
     * @return boolean
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
        $this->options->job->start = 0;
    }

    /**
     * Copy data from old table to new table
     * @param string $new
     * @param string $old
     */
    private function copyData($new, $old)
    {
        $this->databaseCloningService->copyData($old, $new, $this->options->job->start, $this->settings->queryLimit);
        // Set new offset
        $this->options->job->start += $this->settings->queryLimit;
    }

    /**
     * @param mixed string|object $tableName
     * @return bool
     */
    private function copyTable($tableName)
    {
        $tableName = is_object($tableName) ? $tableName->name : $tableName;
        $newTableName = $this->getStagingPrefix() . $this->databaseCloningService->removeDBPrefix($tableName);

        if ($this->isMultisiteAndPro()) {
            // Get name table users from main site e.g. wp_users
            if ($this->databaseCloningService->removeDBPrefix($tableName) === 'users') {
                $tableName = $this->productionDb->base_prefix . 'users';
            }
            // Get name of table usermeta from main site e.g. wp_usermeta
            if ($this->databaseCloningService->removeDBPrefix($tableName) === 'usermeta') {
                $tableName = $this->productionDb->base_prefix . 'usermeta';
            }
        }

        if (!$this->isCopyProcessStarted() && $this->shouldAbortIfTableExist($newTableName)) {
            $this->returnException(sprintf(__("Can not proceed. Tables beginning with the prefix '%s' already exist in the database i.e. %s. Choose another table prefix and try again.", "wp-staging"), $this->getStagingPrefix(), $newTableName));
            return true;
        }

        if ($this->isTableExist($newTableName)) {
            $this->databaseCloningService->dropTable($newTableName);
        }

        $this->setJob($newTableName);

        if (!$this->startJob($newTableName, $tableName)) {
            return true;
        }

        $this->copyData($newTableName, $tableName);

        return $this->finishStep();
    }

    /**
     * Is table excluded from database copying processing?
     * @param string $table
     * @return boolean
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
                    $this->excludedTableService->getExcludedTables()
                )
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Start Job and create tables
     * @param string $new
     * @param string $old
     * @return bool
     */
    private function startJob($new, $old)
    {
        if ($this->isExcludedTable($new)) {
            return false;
        }

        if ($this->options->job->start !== 0) {
            return true;
        }

        if ($this->databaseCloningService->tableIsMissing($old)) {
            return true;
        }

        try {
            $this->options->job->total = 0;
            $this->options->job->total = $this->databaseCloningService->createTable($new, $old);
        } catch (FatalException $e) {
            $this->returnException($e->getMessage());
            return true;
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
     * because they are not available in MU installation but we need them on the staging site
     *
     * return void
     */
    private function addMissingTables()
    {
        $dbPrefix = WPStaging::getTablePrefix();
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
     * @return false
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
     * Return fatal error and stops here if subfolder already exists
     * and mainJob is not updating and resetting the clone
     * @return boolean
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
     * Stop cloning if database prefix contains hypen
     * @return boolean
     */
    private function abortIfPrefixContainsInvalidCharacter()
    {
        // make sure prefix doesn't contains any invalid character
        // same condition as in WordPress wpdb::set_prefix() method
        if (preg_match('|[^a-z0-9_]|i', $this->options->databasePrefix)) {
            $this->returnException(__("Table prefix contains invalid character(s). Use different prefix with valid characters.", 'wp-staging'));
            return true;
        }

        return false;
    }

    /**
     * Check if the copy process started or not.
     * @return boolean
     */
    private function isCopyProcessStarted()
    {
        return isset($this->options->job) && isset($this->options->job->copyProcessStarted) && $this->options->job->copyProcessStarted === true;
    }
}
