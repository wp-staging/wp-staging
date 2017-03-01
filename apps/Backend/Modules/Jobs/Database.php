<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

use WPStaging\WPStaging;

/**
 * Class Database
 * @package WPStaging\Backend\Modules\Jobs
 */
class Database extends JobExecutable
{

    /**
     * @var int
     */
    private $total = 0;

    /**
     * Initialize
     */
    public function initialize()
    {
        // Variables
        $this->total                = count($this->options->tables);
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        $this->options->totalSteps  = $this->total;
    }

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     */
    protected function execute()
    {
        // Over limits threshold
        if ($this->isOverThreshold())
        {
            // Prepare response and save current progress
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

        // No more steps, finished
        if ($this->options->currentStep > $this->total || !isset($this->options->tables[$this->options->currentStep]))
        {
            $this->prepareResponse(true, false);
            return false;
        }

        // Table is excluded
        if (in_array($this->options->tables[$this->options->currentStep]->name, $this->options->excludedTables))
        {
            $this->prepareResponse();
            return true;
        }

        // Copy table
        $this->copyTable($this->options->tables[$this->options->currentStep]->name);

        // Prepare Response
        $this->prepareResponse();

        // Not finished
        return true;
    }

    /**
     * No worries, SQL queries don't eat from PHP execution time!
     * @param string $tableName
     * @return mixed
     */
    private function copyTable($tableName)
    {
        $wpDB = WPStaging::getInstance()->get("wpdb");

        $newTableName = "wpstg{$this->options->cloneNumber}_" . str_replace($wpDB->prefix, null, $tableName);

        // Drop table if necessary
        $currentNewTable = $wpDB->get_var(
            $wpDB->prepare("SHOW TABLES LIKE %s", $newTableName)
        );

        if ($currentNewTable === $newTableName)
        {
            $this->log("{$newTableName} already exists, dropping it first");
            $wpDB->query("DROP TABLE {$newTableName}");
        }

        $this->log("Copying {$tableName} as {$newTableName}");

        $wpDB->query(
        //"CREATE TABLE {$newTableName} LIKE {$tableName}; INSERT {$newTableName} SELECT * FROM {$tableName}"
            "CREATE TABLE {$newTableName} SELECT * FROM {$tableName}"
        );

        // Add it to cloned tables listing
        $this->options->clonedTables[] = $this->options->tables[$this->options->currentStep];
    }
}