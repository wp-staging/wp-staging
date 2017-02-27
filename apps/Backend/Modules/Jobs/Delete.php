<?php
namespace WPStaging\Backend\Modules\Jobs;


use WPStaging\Backend\Modules\Jobs\Exceptions\CloneNotFoundException;
use WPStaging\Utils\Directories;
use WPStaging\Utils\Logger;
use WPStaging\WPStaging;

/**
 * Class Delete
 * @package WPStaging\Backend\Modules\Jobs
 */
class Delete extends Job
{

    /**
     * @var false
     */
    private $clone = false;

    /**
     * @var null|object
     */
    private $tables = null;

    /**
     * Initialize
     */
    public function initialize()
    {
        $this->getCloneRecords();
        $this->getTables();

        $this->clone = (object) $this->clone;
    }

    /**
     * Get clone
     * @param null|string $name
     * @throws CloneNotFoundException
     */
    private function getCloneRecords($name = null)
    {
        if (null === $name && !isset($_POST["clone"]))
        {
            $this->log("Clone name is not set", Logger::TYPE_FATAL);
            throw new CloneNotFoundException();
        }

        if (null === $name)
        {
            $name = $_POST["clone"];
        }

        $clones = get_option("wpstg_existing_clones", array());

        if (!empty($clones) || !isset($clones[$name]))
        {
            $this->log("Couldn't find clone name {$name} or no existing clone", Logger::TYPE_FATAL);
            throw new CloneNotFoundException();
        }

        $this->clone            = $clones[$name];
        $this->clone["name"]    = $name;

        if (isset($this->settings->countDirectorySize) || '1' === $this->settings->countDirectorySize)
        {
            $directories = new Directories();
            $this->clone["size"] = $directories->size($this->clone);
            unset($directories);
        }

        unset($clones);
    }

    /**
     * Get Tables
     */
    private function getTableRecords()
    {
        $wpDB = WPStaging::getInstance()->get("wpdb");

        $tables = $wpDB->get_results("SHOW TABLE STATUS LIKE 'wpstg{$this->clone["number"]}_%'");

        $this->tables = array();

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
     * Format bytes into human readable form
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public function formatSize($bytes, $precision = 2)
    {
        if ((int) $bytes < 1)
        {
            return '';
        }

        $units  = array('B', "KB", "MB", "GB", "TB");

        $bytes  = (int) $bytes;
        $base   = log($bytes) / log(1000); // 1024 would be for MiB KiB etc
        $pow    = pow(1000, $base - floor($base)); // Same rule for 1000

        return round($pow, $precision) . ' ' . $units[(int) floor($base)];
    }

    /**
     * @return false
     */
    public function getClone()
    {
        return $this->clone;
    }

    /**
     * @return null|object
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * Start Module
     * @return object
     */
    public function start()
    {
        // TODO: Implement start() method.
    }
}