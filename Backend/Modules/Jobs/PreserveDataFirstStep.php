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
        $stagingSites = $this->getStagingSiteOption(Sites::STAGING_SITES_OPTION);

        // Get wpstg_settings from staging database
        $settings = $this->getStagingSiteOption("wpstg_settings");

        // Get wpstg_clone_options from staging database
        $cloneOptions = $this->getStagingSiteOption(CloneOptions::WPSTG_CLONE_SETTINGS_KEY);

        // Automated backup schedules
        $backupSchedules = $this->getStagingSiteOption($this->backupSchedulesOption);

        // All remote storages for backup
        $remoteStorages = $this->preserveRemoteStorages();

        // Nothing to do
        if (!$stagingSites && !$settings && !$cloneOptions && !$backupSchedules && empty($remoteStorages)) {
            return true;
        }

        $options = [
            'stagingSites' => $stagingSites,
            'settings' => $settings,
            'cloneOptions' => $cloneOptions,
            'backupSchedules' => $backupSchedules,
        ];

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

        if ($backupSchedules === false) {
            $this->log("Preserve Data: Failed to get " . $this->backupSchedulesOption);
        }

        if ($insert === false) {
            $this->log("Preserve Data: Failed to insert wpstg_staging_sites to wpstg_tmp_data");
        }

        return true;
    }

    /**
     * @return array
     */
    protected function preserveRemoteStorages()
    {
        $storages = [];

        /**
         * Google Drive Options
         * @see WPStaging\Pro\Backup\Storage\Storages\GoogleDrive\Auth::getOptionName for option name
         */
        $googleDrive = $this->getStagingSiteOption('wpstg_googledrive');

        /**
         * Amazon S3 Options
         * @see WPStaging\Pro\Backup\Storage\Storages\Amazon\S3::getOptionName for option name
         */
        $amazonS3 = $this->getStagingSiteOption('wpstg_amazons3');

        /**
         * sFTP/FTP Options
         * @see WPStaging\Pro\Backup\Storage\Storages\SFTP\Auth::getOptionName for option name
         */
        $sftp = $this->getStagingSiteOption('wpstg_sftp');

        /**
         * Digital Ocean Spaces Options
         * @see WPStaging\Pro\Backup\Storage\Storages\DigitalOceanSpaces\Auth::getOptionName for option name
         */
        $digitalOceanSpaces = $this->getStagingSiteOption('wpstg_digitalocean-spaces');

        /**
         * Wasabi S3 Options
         * @see WPStaging\Pro\Backup\Storage\Storages\Wasabi\Auth::getOptionName for option name
         */
        $wasabiS3 = $this->getStagingSiteOption('wpstg_wasabi');

        /**
         * Generic S3 / Other S3 Compat Options
         * @see WPStaging\Pro\Backup\Storage\Storages\GenericS3\Auth::getOptionName for option name
         */
        $genericS3 = $this->getStagingSiteOption('wpstg_generic-s3');

        if ($googleDrive === false) {
            $this->log("Preserve Data: Failed to get Google Drive Settings");
        } else {
            $storages['googleDrive'] = $googleDrive;
        }

        if ($amazonS3 === false) {
            $this->log("Preserve Data: Failed to get Amazon S3 Settings");
        } else {
            $storages['amazonS3'] = $amazonS3;
        }

        if ($sftp === false) {
            $this->log("Preserve Data: Failed to get sFTP/FTP Settings");
        } else {
            $storages['sftp'] = $sftp;
        }

        if ($digitalOceanSpaces === false) {
            $this->log("Preserve Data: Failed to get Digital Ocean Spaces Settings");
        } else {
            $storages['digitalOceanSpaces'] = $digitalOceanSpaces;
        }

        if ($wasabiS3 === false) {
            $this->log("Preserve Data: Failed to get Wasabi S3 Settings");
        } else {
            $storages['wasabiS3'] = $wasabiS3;
        }

        if ($genericS3 === false) {
            $this->log("Preserve Data: Failed to get Generic S3 Settings");
        } else {
            $storages['genericS3'] = $genericS3;
        }

        return $storages;
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
