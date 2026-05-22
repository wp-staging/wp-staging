<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Backup\Storage\Providers;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\SourceDatabase;
use WPStaging\Staging\CloneOptions;
use WPStaging\Staging\Sites;
use WPStaging\Backend\Modules\Jobs\Job as MainJob;

/**
 * Preserve staging sites in wpstg_staging_sites in staging database while updating a site
 * While cloning, copy an existing entry from staging site to wpstg_tmp_data and after cloning restore that data.
 * Mainly used while an existing staging site is updated, not initially cloned
 * @package WPStaging\Backend\Modules\Jobs
 */
class PreserveDataFirstStep extends JobExecutable
{
    /** @var \wpdb */
    private $stagingDb;

    /** @var \wpdb */
    private $productionDb;

    /** @var string */
    private $stagingPrefix;

    /** @var string */
    private $backupSchedulesOption;

    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = 1;

        if (class_exists('\WPStaging\Backup\BackupScheduler')) {
            $this->backupSchedulesOption = \WPStaging\Backup\BackupScheduler::OPTION_BACKUP_SCHEDULES;
        } else {
            // Fallback if pro namespace is not available
            // @see \WPStaging\Backup\BackupScheduler::OPTION_BACKUP_SCHEDULES
            $this->backupSchedulesOption = 'wpstg_backup_schedules';
        }
    }

    /** @return object */
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

    /** @return false */
    protected function execute()
    {
        $this->copyToTmp();
        $this->prepareResponse(true, true);

        return false;
    }

    /** @return true */
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
        $stagingSites = $this->getStagingSiteOption(Sites::STAGING_SITES_OPTION);

        // Get wpstg_settings from staging database (preserved during update only, not during reset)
        if ($this->options->mainJob === MainJob::UPDATE) {
            $settings = $this->getStagingSiteOption("wpstg_settings");
        }

        $loginLinkSettings = $this->getStagingSiteOption("wpstg_login_link_settings");

        // Get wpstg_clone_options from staging database
        $cloneOptions = $this->getStagingSiteOption(CloneOptions::WPSTG_CLONE_SETTINGS_KEY);

        // Automated backup schedules
        $backupSchedules = $this->getStagingSiteOption($this->backupSchedulesOption);

        // All remote storages for backup
        $remoteStorages = $this->preserveRemoteStorages();

        // Nothing to do
        if (!$stagingSites && !$settings && !$cloneOptions && !$backupSchedules && !$loginLinkSettings && empty($remoteStorages)) {
            return true;
        }

        $options = [
            'stagingSites'      => $stagingSites,
            'cloneOptions'      => $cloneOptions,
            'backupSchedules'   => $backupSchedules,
            'loginLinkSettings' => $loginLinkSettings,
        ];

        // Only include settings if it is update operation
        if ($this->options->mainJob === MainJob::UPDATE) {
            $options['settings'] = $settings;
        }

        $options = array_merge($options, $remoteStorages);
        $tmpData = serialize((object) $options);

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

    /** @return array */
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

    /**
     * Get a storage option from the staging database, checking both the new hyphenated
     * and legacy option names for backward compatibility with older staging sites.
     *
     * @param string $identifier The storage identifier (e.g. 'google-drive')
     * @return string|null|false The option value, null if not found, false on error
     */
    protected function getStagingSiteStorageOption($identifier)
    {
        $value = $this->getStagingSiteOption('wpstg_' . $identifier);

        // Fall back to legacy names only when the new-format option is absent (null), not empty —
        // empty means the user cleared credentials intentionally.
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

    /**
     * @param string $optionName
     *
     * @return string|null
     */
    protected function getStagingSiteOption($optionName)
    {
        return $this->stagingDb->get_var(
            $this->stagingDb->prepare(
                "SELECT `option_value` FROM " . $this->stagingPrefix . "options WHERE `option_name` = %s",
                $optionName
            )
        );
    }

    /**
     * Check if table exists
     * @param string $table
     * @return bool
     */
    private function tableExists($table)
    {
        return !($table != $this->stagingDb->get_var("SHOW TABLES LIKE '{$table}'"));
    }
}
