<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

use WPStaging\Backend\Modules\Jobs\Interfaces\JobInterface;
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
     * Initialize object
     */
    public function __construct()
    {
        $this->getStatus();
    }

    /**
     * Start Module
     * @return mixed
     */
    public function start()
    {
        if (!isset($this->tables[$this->position]))
        {
            return true;
        }

        $this->copyTable($this->tables[$this->position]);

        return false;
    }

    /**
     * Next part of the job
     */
    public function next()
    {
        ++$this->step;
    }

    /**
     * Get tables status
     */
    protected function getStatus()
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
     * No worries, SQL queries don't eat from PHP execution time!
     * @param string $tableName
     * @return mixed
     */
    private function copyTable($tableName)
    {
        $wpDB = WPStaging::getInstance()->get("wpdb");

        $newTableName = "wpstg1_" . str_replace($wpDB->prefix, null, $tableName);

        $wpDB->query(
            $wpDB->prepare(
                "CREATE TABLE {$newTableName} LIKE {$tableName}; ".
                "INSERT {$newTableName} SELECT * FROM {$tableName}"
            )
        );
    }
}