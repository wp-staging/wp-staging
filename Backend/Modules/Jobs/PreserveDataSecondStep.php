<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\SourceDatabase;
use WPStaging\Staging\CloneOptions;
use WPStaging\Staging\Sites;
use WPStaging\Staging\FirstRun;
use WPStaging\Backend\Modules\Jobs\Job as MainJob;

use function WPStaging\functions\debug_log;

/**
 * Copy wpstg_tmp_data back to wpstg_staging_sites after cloning with class::PreserveDataSecondStep
 * @package WPStaging\Backend\Modules\Jobs
 */
class PreserveDataSecondStep extends JobExecutable
{
    /** @var \wpdb */
    private $stagingDb;

    /** @var \wpdb */
    private $productionDb;

    /** @var string */
    private $stagingPrefix;

    /** @var object */
    private $preservedData;

    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = 1;
    }

    /** @return object */
    public function start()
    {
        $this->run();
        $this->saveOptions();

        return (object)$this->response;
    }

    /** @return false */
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

    /** @return true */
    public function copyToStaging()
    {
        // Early bail if table doesn't exist
        if (!$this->tableExists($this->stagingPrefix . "options")) {
            return true;
        }

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

        // Make sure this is compatible with Free Version
        // @see \WPStaging\Backup\BackupScheduler::OPTION_BACKUP_SCHEDULES
        $backupSchedulesOption = 'wpstg_backup_schedules';

        // Delete wpstg_tmp_data from the production site
        $deleteTmpData = $this->productionDb->query(
            $this->productionDb->prepare("DELETE FROM " . $this->productionDb->prefix . "options WHERE `option_name` = %s", "wpstg_tmp_data")
        );

        if ($deleteTmpData === false) {
            $this->log("Preserve Data Second Step: Failed to delete wpstg_tmp_data from the production site");
        }

        $this->preservedData = maybe_unserialize($result);

        // Preserve wpstg_staging_sites in staging database
        $this->preserveStagingOption(Sites::STAGING_SITES_OPTION, $this->preservedData->stagingSites, 'existing clones');

        // Preserve wpstg_settings in staging database
        $this->preserveStagingOption("wpstg_settings", $this->preservedData->settings, 'settings');

        // Preserve wpstg_login_link_settings in staging database
        $this->preserveStagingOption("wpstg_login_link_settings", $this->preservedData->loginLinkSettings, 'login settings');

        // Preserve wpstg_clone_options in staging database
        $this->updateCloneOptions();
        $this->preserveStagingOption(CloneOptions::WPSTG_CLONE_SETTINGS_KEY, $this->preservedData->cloneOptions, 'clone options');

        // Preserve backup schedules
        $this->preserveStagingOption($backupSchedulesOption, $this->preservedData->backupSchedules, 'backup schedules');

        if ($this->propertyExists('googleDrive')) {
            $this->preserveStagingOption('wpstg_googledrive', $this->preservedData->googleDrive, 'Google Drive settings');
        } else {
            $this->deleteStagingSiteOption('wpstg_googledrive');
        }

        if ($this->propertyExists('amazonS3')) {
            $this->preserveStagingOption('wpstg_amazons3', $this->preservedData->amazonS3, 'Amazon S3 settings');
        } else {
            $this->deleteStagingSiteOption('wpstg_amazons3');
        }

        if ($this->propertyExists('sftp')) {
            $this->preserveStagingOption('wpstg_sftp', $this->preservedData->sftp, 'sFTP/FTP settings');
        } else {
            $this->deleteStagingSiteOption('wpstg_sftp');
        }

        if ($this->propertyExists('digitalOceanSpaces')) {
            $this->preserveStagingOption('wpstg_digitalocean-spaces', $this->preservedData->digitalOceanSpaces, 'Digital Ocean Spaces settings');
        } else {
            $this->deleteStagingSiteOption('wpstg_digitalocean-spaces');
        }

        if ($this->propertyExists('wasabiS3')) {
            $this->preserveStagingOption('wpstg_wasabi', $this->preservedData->wasabiS3, 'Wasabi S3 settings');
        } else {
            $this->deleteStagingSiteOption('wpstg_wasabi');
        }

        if ($this->propertyExists('genericS3')) {
            $this->preserveStagingOption('wpstg_generic-s3', $this->preservedData->genericS3, 'S3 Compat settings');
        } else {
            $this->deleteStagingSiteOption('wpstg_generic-s3');
        }

        if ($this->propertyExists('dropbox')) {
            $this->preserveStagingOption('wpstg_dropbox', $this->preservedData->dropbox, 'Dropbox settings');
        } else {
            $this->deleteStagingSiteOption('wpstg_dropbox');
        }

        if ($this->propertyExists('oneDrive')) {
            $this->preserveStagingOption('wpstg_one-drive', $this->preservedData->oneDrive, 'Microsoft OneDrive settings');
        } else {
            $this->deleteStagingSiteOption('wpstg_one-drive');
        }

        return true;
    }

    /**
     * @param string $optionName
     * @param string $optionValue
     * @param bool   $autoload
     */
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

    /**
     * @param string $optionName
     *
     * @return bool|int Number of rows affected. Boolean false on error
     */
    protected function deleteStagingSiteOption($optionName)
    {
        return $this->stagingDb->query(
            $this->stagingDb->prepare("DELETE FROM " . $this->stagingPrefix . "options WHERE `option_name` = %s", $optionName)
        );
    }

    /**
     * @param string $optionName
     * @param string $optionValue
     * @param bool   $autoload
     *
     * @return bool|int Number of rows affected. Boolean false on error
     */
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

    /**
     * @param string $property
     *
     * @return bool
     */
    protected function propertyExists($property)
    {
        if (!is_object($this->preservedData) && !is_string($property)) {
            return false;
        }

        if (!property_exists($this->preservedData, $property)) {
            return false;
        }

        return !empty($this->preservedData->{$property});
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

    /**
     * Update wpstg_clone_options before restoring it.
     *
     * @return void
     */
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

        // Should not happen, but if it happens just return earlier without changing anything.
        if (!is_object($data)) {
            debug_log('Fail to update clone options before restore.');
            return;
        }

        $schedulerKey                      = FirstRun::WOO_SCHEDULER_DISABLED_KEY;
        $data->{$schedulerKey}             = empty($this->getOptions()->wooSchedulerDisabled) ? false : true;
        $this->preservedData->cloneOptions = maybe_serialize($data);
    }
}
