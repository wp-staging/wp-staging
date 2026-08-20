<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Backup\Storage\Providers;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\SourceDatabase;
use WPStaging\Staging\CloneOptions;
use WPStaging\Staging\Sites;
use WPStaging\Staging\FirstRun;
use WPStaging\Backend\Modules\Jobs\Job as MainJob;

use function WPStaging\functions\debug_log;





class PreserveDataSecondStep extends JobExecutable
{
 
    private $stagingDb;

 
    private $productionDb;

 
    private $stagingPrefix;

 
    private $preservedData;

    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = 1;
    }

 
    public function start()
    {
        $this->run();
        $this->saveOptions();

        return (object)$this->response;
    }

 
    protected function execute()
    {
        $db = new SourceDatabase($this->options);

        $this->stagingDb     = $db->getDatabase();
        $this->productionDb  = WPStaging::getInstance()->get("wpdb");
        $this->stagingPrefix = $this->options->prefix;

        if ($db->isExternalDatabase()) {
            $this->stagingPrefix = $this->options->databasePrefix;
        }

        $this->copyToStaging();
        $this->prepareResponse(true, true);

        return false;
    }

 
    public function copyToStaging()
    {
 
        if (!$this->tableExists($this->stagingPrefix . "options")) {
            return true;
        }

 
        $result = $this->productionDb->get_var(
            $this->productionDb->prepare(
                "SELECT `option_value` FROM " . $this->productionDb->prefix . "options WHERE `option_name` = %s",
                "wpstg_tmp_data"
            )
        );

 
        if (!$result) {
            return true;
        }

 
 
        $backupSchedulesOption = 'wpstg_backup_schedules';

 
        $deleteTmpData = $this->productionDb->query(
            $this->productionDb->prepare("DELETE FROM " . $this->productionDb->prefix . "options WHERE `option_name` = %s", "wpstg_tmp_data")
        );

        if ($deleteTmpData === false) {
            $this->log("Preserve Data Second Step: Failed to delete wpstg_tmp_data from the production site");
        }

        $this->preservedData = maybe_unserialize($result);

 
        $this->preserveStagingOption(Sites::STAGING_SITES_OPTION, $this->preservedData->stagingSites, 'existing clones');
 
        if ($this->options->mainJob === MainJob::UPDATE && isset($this->preservedData->settings)) {
            $this->preserveStagingOption("wpstg_settings", $this->preservedData->settings, 'settings');
        }

 
        $this->preserveStagingOption("wpstg_login_link_settings", $this->preservedData->loginLinkSettings, 'login settings');

 
        $this->updateCloneOptions();
        $this->preserveStagingOption(CloneOptions::WPSTG_CLONE_SETTINGS_KEY, $this->preservedData->cloneOptions, 'clone options');

 
        $this->preserveStagingOption($backupSchedulesOption, $this->preservedData->backupSchedules, 'backup schedules');

        foreach (Providers::STORAGE_LABELS as $identifier => $label) {
            $optionName = 'wpstg_' . $identifier;
            $value      = $this->getPreservedStorageValue($identifier);
            if ($value !== null) {
                $this->preserveStagingOption($optionName, $value, $label . ' settings');
            } else {
                $this->deleteStagingSiteOption($optionName);
            }
        }

        return true;
    }






    protected function preserveStagingOption($optionName, $optionValue, $logEntity, $autoload = false)
    {
        $isDeleted = $this->deleteStagingSiteOption($optionName);

        if ($isDeleted === false) {
            $this->log("Preserve Data Second Step: Failed to delete " . $optionName . " from the staging site");
        }

        $isInserted = $this->insertOptionIntoStagingSite($optionName, $optionValue, $autoload);

        if ($isInserted === false) {
            $this->log("Preserve Data Second Step: Failed to insert preserved " . $logEntity . " into " . $optionName . " of the staging site");
        }
    }






    protected function deleteStagingSiteOption($optionName)
    {
        return $this->stagingDb->query(
            $this->stagingDb->prepare("DELETE FROM " . $this->stagingPrefix . "options WHERE `option_name` = %s", $optionName)
        );
    }








    protected function insertOptionIntoStagingSite($optionName, $optionValue, $autoload = false)
    {
        $autoload = $autoload ? 'yes' : 'no';

        return $this->stagingDb->query(
            $this->stagingDb->prepare(
                "INSERT INTO `" . $this->stagingPrefix . "options` ( `option_id`, `option_name`, `option_value`, `autoload` ) VALUES ( NULL , %s, %s, %s )",
                $optionName,
                $optionValue,
                $autoload
            )
        );
    }








    protected function getPreservedStorageValue(string $identifier)
    {
        if ($this->propertyExists($identifier)) {
            return $this->preservedData->{$identifier};
        }

        $legacyProperty = isset(Providers::LEGACY_PROPERTY_MAP[$identifier]) ? Providers::LEGACY_PROPERTY_MAP[$identifier] : $identifier;
        if ($legacyProperty !== $identifier && $this->propertyExists($legacyProperty)) {
            return $this->preservedData->{$legacyProperty};
        }

        return null;
    }






    protected function propertyExists($property)
    {
        if (!is_object($this->preservedData)) {
            return false;
        }

        if (!property_exists($this->preservedData, $property)) {
            return false;
        }

        return !empty($this->preservedData->{$property});
    }






    private function tableExists($table)
    {
        return !($table != $this->stagingDb->get_var("SHOW TABLES LIKE '{$table}'"));
    }






    private function updateCloneOptions()
    {
        if ($this->getOptions()->mainJob !== MainJob::UPDATE) {
            return;
        }

        $cloneOptions = $this->preservedData->cloneOptions;

        $data = maybe_unserialize($cloneOptions);
        if (empty($cloneOptions)) {
            $data = new \stdClass();
        }

 
        if (!is_object($data)) {
            debug_log('Fail to update clone options before restore.');
            return;
        }

 
        if (property_exists($data, 'wpstg_woo_scheduler_disabled')) {
            unset($data->wpstg_woo_scheduler_disabled);
        }

        $schedulerKey                      = FirstRun::WOO_SCHEDULER_ENABLED_KEY;
        $data->{$schedulerKey}             = empty($this->getOptions()->isWooSchedulerEnabled) ? false : true;
        $this->preservedData->cloneOptions = maybe_serialize($data);
    }
}
