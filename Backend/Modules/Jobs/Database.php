<?php

namespace WPStaging\Backend\Modules\Jobs;


use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Framework\CloningProcess\CloningDto;
use WPStaging\Framework\CloningProcess\Database\DatabaseCloningService;

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
        if (!$this->isExternalDatabase()) {
            $this->abortIfStagingPrefixEqualsProdPrefix();
        } else {
            $this->abortIfExternalButNotPro();
        }

        $this->generateDto();
        $this->addMissingTables();
        $this->total = count($this->options->tables);
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

        // Copy table
        if (isset($this->options->tables[$this->options->currentStep]) && !$this->copyTable($this->options->tables[$this->options->currentStep])) {
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
     * Check if table already exists
     * @param string $name
     * @return bool
     */
    private function shouldDropTable($name)
    {
        $old = $this->stagingDb->get_var($this->stagingDb->prepare("SHOW TABLES LIKE %s", $name));
        return (
            $old === $name &&
            (
                !isset($this->options->job->current, $this->options->job->start) || $this->options->job->start == 0
            )
        );
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
        $this->options->job = new \stdClass();

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

        if ($this->shouldDropTable($newTableName)) {
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
     * Is table excluded from search replace processing?
     * @param string $table
     * @return boolean
     */
    private function isExcludedTable($table)
    {
        $excludedCustomTables = apply_filters('wpstg_clone_database_tables_exclude', []);
        $excludedCoreTables = ['blogs', 'blog_versions'];

        $excludedtables = array_merge($excludedCustomTables, $excludedCoreTables);

        if (in_array(
            $table,
            array_map(
                function ($tableName) {
                    return $this->options->prefix . $tableName;
                },
                $excludedtables
            )
        )) {
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
        if ($this->options->job->start != 0) {
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
        if ($this->options->job->total == 0) {
            $this->finishStep();
            return false;
        }
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
        if (!in_array($this->productionDb->prefix . 'users', $this->options->tables)) {
            $this->options->tables[] = $this->productionDb->prefix . 'users';
            $this->saveOptions();
        }
        if (!in_array($this->productionDb->prefix . 'usermeta', $this->options->tables)) {
            $this->options->tables[] = $this->productionDb->prefix . 'usermeta';
            $this->saveOptions();
        }
    }

    /**
     * @return false
     */
    private function abortIfStagingPrefixEqualsProdPrefix()
    {
        if ($this->productionDb->prefix === $this->getStagingPrefix()) {
            $error = 'Fatal error 7: The destination database table prefix ' . $this->getStagingPrefix() . ' would be identical to the table prefix of the production site. Please open a support ticket at support@wp-staging.com';
            $this->returnException($error);
            return true;
        }

        return false;
    }

    /**
     * Get new prefix for the staging site
     * @return string
     */
    private function getStagingPrefix()
    {
        if ($this->isExternalDatabase()) {
            $this->options->prefix = !empty($this->options->databasePrefix) ? $this->options->databasePrefix : $this->productionDb->prefix;
            return $this->options->prefix;
        }

        return $this->options->prefix;
    }

    /**
     * Return fatal error and stops here if subfolder already exists
     * and mainJob is not updating the clone
     * @return boolean
     */
    private function abortIfDirectoryNotEmpty()
    {
        $path = trailingslashit($this->options->cloneDir);
        if (isset($this->options->mainJob) && $this->options->mainJob !== 'updating' && is_dir($path) && !wpstg_is_empty_dir($path)) {
            $this->returnException(" Can not continue for security purposes. Directory {$path} is not empty! Use FTP or a file manager plugin and make sure it does not contain any files. ");
            return true;
        }
        return false;
    }
}
