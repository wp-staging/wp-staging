<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

use WPStaging\Backend\Modules\Jobs\Interfaces\JobInterface;
use WPStaging\Backend\Modules\Jobs\Interfaces\ThresholdAwareInterface;
use WPStaging\WPStaging;

/**
 * Class Database
 * @package WPStaging\Backend\Modules\Jobs
 */
class Database implements JobInterface
{

    /**
     * @var array
     */
    private $tables = array();

    /**
     * @var int
     */
    private $step = 0;

    /**
     * @var array
     */
    private $excludes = array();

    /**
     * @var int
     */
    private $cloneNumber = 1;

    /**
     * Initialize object
     * @param int $cloneNumber
     */
    public function __construct($cloneNumber = 1)
    {
        $this->cloneNumber = 1;
    }

    /**
     * @param array $excludes
     */
    public function setExcludes($excludes)
    {
        $this->excludes = $excludes;
    }

    /**
     * @param int $step
     */
    public function setStep($step)
    {
        $this->step = $step;
    }

    /**
     * Start Module
     * @return mixed
     */
    public function start()
    {
        $total = count($this->tables);

        // No more steps, finished
        if ($this->step > $total || !isset($this->tables[$this->step]))
        {
            return array(
                "status"        => true,
                "percentage"    => round(($this->step / $total) * 100),
                "total"         => $total,
                "step"          => $this->step + 1
            );
        }

        // Table is excluded
        if (in_array($this->tables[$this->step]->name, $this->excludes))
        {
            return array(
                "status"        => false,
                "percentage"    => round(($this->step / $total) * 100),
                "total"         => $total,
                "step"          => $this->step + 1
            );
        }

        // Copy table
        $this->copyTable($this->tables[$this->step]->name);

        // Not finished
        return array(
            "status"        => false,
            "percentage"    => round(($this->step / $total) * 100),
            "total"         => $total,
            "step"          => $this->step + 1
        );
    }

    /**
     * Get tables status
     */
    public function getStatus()
    {
        $wpDB = WPStaging::getInstance()->get("wpdb");

        if (strlen($wpDB->prefix) > 0)
        {
            $sql = "SHOW TABLE STATUS LIKE '{$wpDB->prefix}%'";
        }
        else
        {
            $sql = "SHOW TABLE STATUS";
        }

        $tables = $wpDB->get_results($sql);

        foreach ($tables as $table)
        {
            $this->tables[] = array(
                "name"  => $table->Name,
                "size"  => ($table->Data_length + $table->Index_length)
            );
        }

        $this->tables = json_decode(json_encode($this->tables));
    }

    /**
     * @return array
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * @param object $tables
     */
    public function setTables($tables)
    {
        $this->tables = $tables;
    }

    /**
     * No worries, SQL queries don't eat from PHP execution time!
     * @param string $tableName
     * @return mixed
     */
    private function copyTable($tableName)
    {
        $wpDB = WPStaging::getInstance()->get("wpdb");

        $newTableName = "wpstg{$this->cloneNumber}_" . str_replace($wpDB->prefix, null, $tableName);

        // Drop table if necessary
        $currentNewTable = $wpDB->get_var(
            $wpDB->prepare("SHOW TABLES LIKE {$tableName}")
        );

        if ($currentNewTable === $newTableName)
        {
            $wpDB->query("DROP TABLE {$newTableName}");
        }

        $wpDB->query(
            //"CREATE TABLE {$newTableName} LIKE {$tableName}; INSERT {$newTableName} SELECT * FROM {$tableName}"
            "CREATE TABLE {$newTableName} SELECT * FROM {$tableName}"
        );
    }
}