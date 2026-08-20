<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Backup\Storage\Providers;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\SourceDatabase;
use WPStaging\Staging\CloneOptions;
use WPStaging\Staging\Sites;
use WPStaging\Backend\Modules\Jobs\Job as MainJob;







class PreserveDataFirstStep extends JobExecutable
{
 
    private $stagingDb;

 
    private $productionDb;

 
    private $stagingPrefix;

 
    private $backupSchedulesOption;

    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = 1;

        if (class_exists('\WPStaging\Backup\BackupScheduler')) {
            $this->backupSchedulesOption = \WPStaging\Backup\BackupScheduler::OPTION_BACKUP_SCHEDULES;
        } else {
 
 
            $this->backupSchedulesOption = 'wpstg_backup_schedules';
        }
    }

 
    public function start()
    {
        $db = new SourceDatabase($this->options);

        $this->stagingDb     = $db->getDatabase();
        $this->productionDb  = WPStaging::getInstance()->get("wpdb");
        $this->stagingPrefix = $this->options->prefix;

        if ($db->isExternalDatabase()) {
            $this->stagingPrefix = $this->options->databasePrefix;
        }

        $this->run();
        $this->saveOptions();

        return (object)$this->response;
    }

 
    protected function execute()
    {
        $this->copyToTmp();
        $this->prepareResponse(true, true);

        return false;
    }

 
    public function copyToTmp()
    {
 
        $delete = $this->productionDb->query(
            $this->productionDb->prepare("DELETE FROM " . $this->productionDb->prefix . "options WHERE `option_name` = %s", "wpstg_tmp_data")
        );

        if (!$this->tableExists($this->stagingPrefix . "options")) {
            return true;
        }

 
        $stagingSites = $this->getStagingSiteOption(Sites::STAGING_SITES_OPTION);

 
        if ($this->options->mainJob === MainJob::UPDATE) {
            $settings = $this->getStagingSiteOption("wpstg_settings");
        }

        $loginLinkSettings = $this->getStagingSiteOption("wpstg_login_link_settings");

 
        $cloneOptions = $this->getStagingSiteOption(CloneOptions::WPSTG_CLONE_SETTINGS_KEY);

 
        $backupSchedules = $this->getStagingSiteOption($this->backupSchedulesOption);

 
        $remoteStorages = $this->preserveRemoteStorages();

 
        if (!$stagingSites && !$settings && !$cloneOptions && !$backupSchedules && !$loginLinkSettings && empty($remoteStorages)) {
            return true;
        }

        $options = [
            'stagingSites'      => $stagingSites,
            'cloneOptions'      => $cloneOptions,
            'backupSchedules'   => $backupSchedules,
            'loginLinkSettings' => $loginLinkSettings,
        ];

 
        if ($this->options->mainJob === MainJob::UPDATE) {
            $options['settings'] = $settings;
        }

        $options = array_merge($options, $remoteStorages);
        $tmpData = serialize((object) $options);

 
        $insert = $this->productionDb->query(
            $this->productionDb->prepare(
                "INSERT INTO `" . $this->productionDb->prefix . "options` ( `option_id`, `option_name`, `option_value`, `autoload` ) VALUES ( NULL , %s, %s, %s )",
                "wpstg_tmp_data",
                $tmpData,
                "no"
            )
        );

        if ($delete === false) {
            $this->log("Preserve Data: Failed to delete wpstg_tmp_data");
        }

        if ($stagingSites === false) {
            $this->log("Preserve Data: Failed to get wpstg_staging_sites");
        }

        if ($settings === false) {
            $this->log("Preserve Data: Failed to get wpstg_settings");
        }

        if ($cloneOptions === false) {
            $this->log("Preserve Data: Failed to get wpstg_clone_options");
        }

        if ($loginLinkSettings === false) {
            $this->log("Preserve Data: Failed to get wpstg_login_link_settings");
        }

        if ($backupSchedules === false) {
            $this->log("Preserve Data: Failed to get " . $this->backupSchedulesOption);
        }

        if ($insert === false) {
            $this->log("Preserve Data: Failed to insert wpstg_staging_sites to wpstg_tmp_data");
        }

        return true;
    }

 
    protected function preserveRemoteStorages()
    {
        $storages = [];

        foreach (Providers::STORAGE_LABELS as $identifier => $label) {
            $value = $this->getStagingSiteStorageOption($identifier);
            if ($value === false) {
                $this->log("Preserve Data: Failed to get {$label} Settings");
            } else {
                $storages[$identifier] = $value;
            }
        }

        return $storages;
    }








    protected function getStagingSiteStorageOption($identifier)
    {
        $value = $this->getStagingSiteOption('wpstg_' . $identifier);

 
 
        if ($value !== null) {
            return $value;
        }

        if (isset(Providers::LEGACY_OPTION_MAP[$identifier])) {
            $legacyValue = $this->getStagingSiteOption(Providers::LEGACY_OPTION_MAP[$identifier]);
            if ($legacyValue !== null) {
                return $legacyValue;
            }
        }

        return $value;
    }






    protected function getStagingSiteOption($optionName)
    {
        return $this->stagingDb->get_var(
            $this->stagingDb->prepare(
                "SELECT `option_value` FROM " . $this->stagingPrefix . "options WHERE `option_name` = %s",
                $optionName
            )
        );
    }






    private function tableExists($table)
    {
        return !($table != $this->stagingDb->get_var("SHOW TABLES LIKE '{$table}'"));
    }
}
