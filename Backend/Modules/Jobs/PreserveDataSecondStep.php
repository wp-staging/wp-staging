<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\SourceDatabase;
use WPStaging\Framework\Staging\CloneOptions;
use WPStaging\Framework\Staging\Sites;

/**
 * Copy wpstg_tmp_data back to wpstg_staging_sites after cloning with class::PreserveDataSecondStep
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

        return (object)$this->response;
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

        if ($db->isExternalDatabase()) {
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
                "SELECT `option_value` FROM " . $this->productionDb->prefix . "options WHERE `option_name` = %s",
                "wpstg_tmp_data"
            )
        );

        // Nothing to do
        if (!$result) {
            return true;
        }

        // Delete wpstg_tmp_data from the production site
        $deleteTmpData = $this->productionDb->query(
            $this->productionDb->prepare("DELETE FROM " . $this->productionDb->prefix . "options WHERE `option_name` = %s", "wpstg_tmp_data")
        );

        // Delete wpstg_staging_sites from the staging site
        $deleteStagingSites = $this->stagingDb->query(
            $this->stagingDb->prepare("DELETE FROM " . $this->stagingPrefix . "options WHERE `option_name` = %s", Sites::STAGING_SITES_OPTION)
        );

        // Delete wpstg_settings from the staging site
        $deleteSettings = $this->stagingDb->query(
            $this->stagingDb->prepare("DELETE FROM " . $this->stagingPrefix . "options WHERE `option_name` = %s", "wpstg_settings")
        );

        // Delete wpstg_clone_options from the staging site
        $deleteCloneOptions = $this->stagingDb->query(
            $this->stagingDb->prepare("DELETE FROM " . $this->stagingPrefix . "options WHERE `option_name` = %s", CloneOptions::WPSTG_CLONE_SETTINGS_KEY)
        );

        $tempData = maybe_unserialize($result);

        // Insert wpstg_staging_sites in staging database
        $insertStagingSites = $this->stagingDb->query(
            $this->stagingDb->prepare(
                "INSERT INTO `" . $this->stagingPrefix . "options` ( `option_id`, `option_name`, `option_value`, `autoload` ) VALUES ( NULL , %s, %s, %s )",
                Sites::STAGING_SITES_OPTION,
                $tempData->stagingSites,
                "no"
            )
        );

        // Insert wpstg_settings in staging database
        $insertSettings = $this->stagingDb->query(
            $this->stagingDb->prepare(
                "INSERT INTO `" . $this->stagingPrefix . "options` ( `option_id`, `option_name`, `option_value`, `autoload` ) VALUES ( NULL , %s, %s, %s )",
                "wpstg_settings",
                $tempData->settings,
                "no"
            )
        );

        // Insert wpstg_clone_options in staging database
        $insertCloneOptions = $this->stagingDb->query(
            $this->stagingDb->prepare(
                "INSERT INTO `" . $this->stagingPrefix . "options` ( `option_id`, `option_name`, `option_value`, `autoload` ) VALUES ( NULL , %s, %s, %s )",
                CloneOptions::WPSTG_CLONE_SETTINGS_KEY,
                $tempData->cloneOptions,
                "no"
            )
        );

        if ($deleteTmpData === false) {
            $this->log("Preserve Data Second Step: Failed to delete wpstg_tmp_data from the production site");
        }

        if ($deleteStagingSites === false) {
            $this->log("Preserve Data Second Step: Failed to delete wpstg_staging_sites from the staging site");
        }

        if ($deleteSettings === false) {
            $this->log("Preserve Data Second Step: Failed to delete wpstg_settings from the staging site");
        }

        if ($deleteCloneOptions === false) {
            $this->log("Preserve Data Second Step: Failed to delete wpstg_clone_options from the staging site");
        }

        if ($result === false) {
            $this->log("Preserve Data Second Step: Failed to get wpstg_tmp_data from the production site");
        }

        if ($insertStagingSites === false) {
            $this->log("Preserve Data Second Step: Failed to insert preserved existing clones into wpstg_staging_sites of the staging site");
        }

        if ($insertSettings === false) {
            $this->log("Preserve Data Second Step: Failed to insert preserved settings into wpstg_settings of the staging site");
        }

        if ($insertCloneOptions === false) {
            $this->log("Preserve Data Second Step: Failed to insert preserved clone options into wpstg_settings of the staging site");
        }

        return true;
    }
}
