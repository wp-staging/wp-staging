<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\SourceDatabase;
use WPStaging\Framework\Staging\CloneOptions;
use WPStaging\Framework\Staging\Sites;

/**
 * Preserve staging sites in wpstg_staging_sites in staging database while updating a site
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

    /** @var string */
    private $backupSchedulesOption;

    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = 1;

        if (class_exists('\WPStaging\Pro\Backup\BackupScheduler')) {
            $this->backupSchedulesOption = \WPStaging\Pro\Backup\BackupScheduler::OPTION_BACKUP_SCHEDULES;
        } else {
            // Fallback if pro namespace is not available
            // @see \WPStaging\Pro\Backup\BackupScheduler::OPTION_BACKUP_SCHEDULES
            $this->backupSchedulesOption = 'wpstg_backup_schedules';
        }
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

        if ($db->isExternalDatabase()) {
            $this->stagingPrefix = $this->options->databasePrefix;
        }

        $this->run();

        $this->saveOptions();

        return (object)$this->response;
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
            $this->productionDb->prepare("DELETE FROM " . $this->productionDb->prefix . "options WHERE `option_name` = %s", "wpstg_tmp_data")
        );

        if (!$this->tableExists($this->stagingPrefix . "options")) {
            return true;
        }

        // Get wpstg_staging_sites from staging database
        $stagingSites = $this->stagingDb->get_var(
            $this->stagingDb->prepare(
                "SELECT `option_value` FROM " . $this->stagingPrefix . "options WHERE `option_name` = %s",
                Sites::STAGING_SITES_OPTION
            )
        );

        // Get wpstg_settings from staging database
        $settings = $this->stagingDb->get_var(
            $this->stagingDb->prepare(
                "SELECT `option_value` FROM " . $this->stagingPrefix . "options WHERE `option_name` = %s",
                "wpstg_settings"
            )
        );

        // Get wpstg_clone_options from staging database
        $cloneOptions = $this->stagingDb->get_var(
            $this->stagingDb->prepare(
                "SELECT `option_value` FROM " . $this->stagingPrefix . "options WHERE `option_name` = %s",
                CloneOptions::WPSTG_CLONE_SETTINGS_KEY
            )
        );

        // Automated backup schedules
        $backupSchedules = $this->stagingDb->get_var(
            $this->stagingDb->prepare(
                "SELECT `option_value` FROM " . $this->stagingPrefix . "options WHERE `option_name` = %s",
                $this->backupSchedulesOption
            )
        );

        // Nothing to do
        if (!$stagingSites && !$settings && !$cloneOptions && !$backupSchedules) {
            return true;
        }

        $tmpData = serialize((object) [
            'stagingSites' => $stagingSites,
            'settings' => $settings,
            'cloneOptions' => $cloneOptions,
            'backupSchedules' => $backupSchedules,
        ]);

        // Insert staging site preserved data into wpstg_tmp_data in production database
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

        if ($backupSchedules === false) {
            $this->log("Preserve Data: Failed to get " . $this->backupSchedulesOption);
        }

        if ($insert === false) {
            $this->log("Preserve Data: Failed to insert wpstg_staging_sites to wpstg_tmp_data");
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
