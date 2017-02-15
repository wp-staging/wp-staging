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
        // TODO: Implement start() method.
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
}