<?php

namespace WPStaging\Backend\Modules\Jobs;


use WPStaging\WPStaging;

abstract class CloningProcess extends JobExecutable
{
    /**
     * Can be local or external \wpdb object
     * @var \wpdb
     */
    protected $stagingDb;

    /**
     * Always be the local \wpdb object
     * @var \wpdb
     */
    protected $productionDb;

    protected function initializeDbObjects()
    {
        $this->productionDb = WPStaging::getInstance()->get("wpdb");
        if ($this->isExternal()) {
            $this->stagingDb = new \wpdb($this->options->databaseUser, $this->options->databasePassword, $this->options->databaseDatabase, $this->options->databaseServer);
            // Can not connect to mysql
            if (!empty($this->stagingDb->error->errors['db_connect_fail']['0'])) {
                $this->returnException("Can not connect to external database {$this->options->databaseDatabase}");
                return false;
            }
            // Can not connect to database
            $this->stagingDb->select($this->options->databaseDatabase);
            if (!$this->stagingDb->ready) {
                $error = isset($db->error->errors['db_select_fail']) ? $db->error->errors['db_select_fail'] : "Error: Can't select {database} Either it does not exist or you don't have privileges to access it.";
                $this->returnException($error);
                exit;
            }
        } else {
            $this->stagingDb = WPStaging::getInstance()->get("wpdb");
        }
    }

    protected function isExternal()
    {
        return !(empty($this->options->databaseUser) && empty($this->options->databasePassword));
    }

    protected function isMultisiteAndPro()
    {
        return defined('WPSTGPRO_VERSION') && is_multisite();
    }
}
