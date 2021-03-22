<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\SourceDatabase;

/**
 * Preserve staging sites in wpstg_existing_clones_beta in staging database while updating a site
 * While cloning, copy an existing entry from staging site to wpstg_tmp_data and after cloning restore that data.
 * Mainly used while an existing staging site is updated, not initially cloned
 * @package WPStaging\Backend\Modules\Jobs
 */

class PreserveDataFirstStep extends JobExecutable
{
    /** @var object */
    private $stagingDb;

    /** @var object */
    private $productionDb;

    /** @var string */
    private $stagingPrefix;

    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = 1;
    }

    /**
     * @return object
     */
    public function start()
    {
        $db = new SourceDatabase($this->options);

        $this->stagingDb = $db->getDatabase();

        $this->productionDb = WPStaging::getInstance()->get("wpdb");

        $this->stagingPrefix = $this->options->prefix;

        if($db->isExternalDatabase()){
            $this->stagingPrefix = $this->options->databasePrefix;
        }

        $this->run();

        $this->saveOptions();

        return ( object )$this->response;
    }

    /**
     * @return bool
     */
    protected function execute()
    {

        $this->copyToTmp();

        $this->prepareResponse(true, true);
        return false;
    }

    /**
     * @return bool
     */

    public function copyToTmp()
    {
        // Delete wpstg_tmp_data and reset it
        $delete = $this->productionDb->query(
            $this->productionDb->prepare("DELETE FROM " . $this->productionDb->prefix . "options WHERE `option_name` = %s", "wpstg_tmp_data"
            )
        );

        if(!$this->tableExists($this->stagingPrefix . "options")){
            return true;
        }

        // Get wpstg_existing_clones_beta from staging database
        $result = $this->stagingDb->get_var(
            $this->stagingDb->prepare(
                "SELECT `option_value` FROM " . $this->stagingPrefix . "options WHERE `option_name` = %s", "wpstg_existing_clones_beta"
            )
        );

        // Nothing to do
        if (!$result){
            return true;
        }

        // Insert wpstg_existing_clones_beta into wpstg_tmp_data in production database
        $insert = $this->productionDb->query(
            $this->productionDb->prepare(
                "INSERT INTO `" . $this->productionDb->prefix . "options` ( `option_id`, `option_name`, `option_value`, `autoload` ) VALUES ( NULL , %s, %s, %s )", "wpstg_tmp_data", $result, "no"
            )
        );

        if ($delete === false) {
            $this->log("Preserve Data: Failed to delete wpstg_tmp_data");
        }
        if ($result === false) {
            $this->log("Preserve Data: Failed to get wpstg_existing_clones_beta");
        }
        if ($insert === false) {
            $this->log("Preserve Data: Failed to insert wpstg_existing_clones_beta to wpstg_tmp_data");
        }
        return true;
    }

    /**
     * Check if table exists
     * @param string $table
     * @return boolean
     */
    private function tableExists($table)
    {
        return !($table != $this->stagingDb->get_var("SHOW TABLES LIKE '{$table}'"));
    }


}
