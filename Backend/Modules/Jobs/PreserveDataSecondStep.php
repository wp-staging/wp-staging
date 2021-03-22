<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\SourceDatabase;

/**
 * Copy wpstg_tmp_data back to wpstg_existing_clones_beta after cloning with class::PreserveDataSecondStep
 * @package WPStaging\Backend\Modules\Jobs
 */

class PreserveDataSecondStep extends JobExecutable
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
        $this->run();

        $this->saveOptions();

        return ( object )$this->response;
    }

    /**
     * @return bool
     */
    protected function execute()
    {
        $db = new SourceDatabase($this->options);

        $this->stagingDb = $db->getDatabase();

        $this->productionDb = WPStaging::getInstance()->get("wpdb");

        $this->stagingPrefix = $this->options->prefix;

        if($db->isExternalDatabase()){
            $this->stagingPrefix = $this->options->databasePrefix;
        }

        $this->copyToStaging();

        $this->prepareResponse(true, true);
        return false;
    }

    /**
     * @return bool
     */

    public function copyToStaging()
    {
        // Get wpstg_tmp_data from production database
        $result = $this->productionDb->get_var(
            $this->productionDb->prepare(
                "SELECT `option_value` FROM " . $this->productionDb->prefix . "options WHERE `option_name` = %s", "wpstg_tmp_data"
            )
        );

        // Nothing to do
        if (!$result){
            return true;
        }

        // Delete wpstg_tmp_data
        $delete = $this->stagingDb->query(
            $this->stagingDb->prepare("DELETE FROM " . $this->stagingPrefix . "options WHERE `option_name` = %s", "wpstg_existing_clones_beta"
            )
        );

        // Insert wpstg_existing_clones_beta in staging database
        $insert = $this->stagingDb->query(
            $this->stagingDb->prepare(
                "INSERT INTO `" . $this->stagingPrefix . "options` ( `option_id`, `option_name`, `option_value`, `autoload` ) VALUES ( NULL , %s, %s, %s )", "wpstg_existing_clones_beta", $result, "no"
            )
        );

        if ($delete === false) {
            $this->log("Preserve Data Second Step: Failed to delete wpstg_tmp_data");
        }
        if ($result === false) {
            $this->log("Preserve Data Second Step: Failed to get wpstg_existing_clones_beta");
        }
        if ($insert === false) {
            $this->log("Preserve Data Second Step: Failed to insert wpstg_tmp_data to wpstg_existing_clones_beta");
        }
        return true;
    }


}
