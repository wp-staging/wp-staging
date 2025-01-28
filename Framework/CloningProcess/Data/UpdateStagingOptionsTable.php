<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Job as MainJob;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Framework\CloningProcess\ExcludedPlugins;
use WPStaging\Staging\CloneOptions;
use WPStaging\Staging\FirstRun;
use WPStaging\Core\Utils\Logger;
use WPStaging\Staging\Sites;
use WPStaging\Framework\ThirdParty\FreemiusScript;
use WPStaging\Pro\Staging\NetworkClone;
use WPStaging\Backup\BackupRetentionHandler;

class UpdateStagingOptionsTable extends DBCloningService
{
    const FILTER_CLONING_UPDATE_ACTIVE_PLUGINS = 'wpstg.cloning.update_active_plugins';

    /**
     * @inheritDoc
     */
    protected function internalExecute()
    {
        if ($this->isNetworkClone()) {
            return $this->updateAllOptionsTables();
        }

        if ($this->skipOptionsTable()) {
            return true;
        }

        return $this->updateOptionsTable();
    }

    /**
     * @return bool
     */
    private function updateAllOptionsTables()
    {
        foreach (get_sites() as $site) {
            $tableName = $this->getOptionTableWithoutBasePrefix($site->blog_id);
            $this->setOptionTable($tableName);

            $this->log("Updating {$this->dto->getPrefix()}{$tableName} {$this->dto->getStagingDb()->last_error}");
            if ($this->skipOptionsTable()) {
                continue;
            }

            $this->updateOptionsTable(is_main_site($site->blog_id));
        }

        return true;
    }

    /**
     * @param bool $isMainSite
     *
     * @return bool
     */
    private function updateOptionsTable($isMainSite = false)
    {
        $updateOrInsert = [
            'wpstg_is_staging_site' => 'true',
            'wpstg_rmpermalinks_executed' => ' ',
            'blog_public' => 0,
            FirstRun::FIRST_RUN_KEY => 'true',
        ];

        $cloneOptions = [
            FirstRun::MAILS_DISABLED_KEY          => !((bool) $this->dto->getJob()->getOptions()->emailsAllowed),
            ExcludedPlugins::EXCLUDED_PLUGINS_KEY => (new ExcludedPlugins())->getFilteredPluginsToExclude(),
            FirstRun::WOO_SCHEDULER_DISABLED_KEY  => (bool) $this->dto->getJob()->getOptions()->wooSchedulerDisabled,
        ];

        // Add the base directory path and is network clone when cloning into network
        // Required to generate .htaccess file on the staging network.
        if ($this->dto->getJob()->isNetworkClone() && $isMainSite) {
            $cloneOptions[NetworkClone::NEW_NETWORK_CLONE_KEY] = 'true';
            $cloneOptions[NetworkClone::NETWORK_BASE_DIR_KEY]  = $this->dto->getStagingSitePath();
        }

        // only insert or update clone option if job is not updating
        // during update this data will be preserved
        if ($this->dto->getMainJob() !== MainJob::UPDATE) {
            $updateOrInsert[CloneOptions::WPSTG_CLONE_SETTINGS_KEY] = serialize((object) $cloneOptions);
        }

        if (!$this->keepPermalinks()) {
            $updateOrInsert['rewrite_rules'] = null;
            $updateOrInsert['permalink_structure'] = ' ';
        } else {
            /**
             * if staging site is created with keep permalinks setting off,
             * The below code make sure permalinks settings are kept during update,
             * when later production site has keep permalinks setting on,
             * without the need to also keep permalinks setting on staging site too.
             */
            $updateOrInsert['wpstg_rmpermalinks_executed'] = 'true';
        }

        $freemiusHelper = new FreemiusScript();
        // Only show freemius notice if freemius options exists on the productions site
        // These freemius options will be deleted from option table, see below.
        if (!$this->isNetworkClone() && $freemiusHelper->hasFreemiusOptions()) {
            $updateOrInsert[FreemiusScript::NOTICE_OPTION] = true;
        }

        if (isset($this->dto->getJob()->getOptions()->tmpExcludedFilesFullPath)) {
            $updateOrInsert[Sites::STAGING_EXCLUDED_FILES_OPTION] = serialize(array_unique((array)$this->dto->getJob()->getOptions()->tmpExcludedFilesFullPath));
        }

        if (isset($this->dto->getJob()->getOptions()->tmpExcludedGoDaddyFiles)) {
            $updateOrInsert[Sites::STAGING_EXCLUDED_GD_FILES_OPTION] = serialize(array_unique((array)$this->dto->getJob()->getOptions()->tmpExcludedGoDaddyFiles));
        }

        $this->updateOrInsertOptions($updateOrInsert);

        $update = [
            'upload_path' => '',
            'wpstg_connection' => json_encode(['prodHostname' => get_site_url()]),
        ];
        if ($this->dto->getMainJob() !== MainJob::UPDATE) {
            $update[Sites::STAGING_SITES_OPTION] = serialize([]);
        }

        if ($this->dto->getMainJob() === MainJob::STAGING) {
            $activePluginsToUpdate = $this->getActivePluginsToUpdate();
            if (is_array($activePluginsToUpdate)) {
                $update['active_plugins'] = serialize($activePluginsToUpdate);
            }
        }

        $this->updateOptions($update);

        // Options to delete on the staging site
        $toDelete = [
            '_transient_wp_core_block_css_files' // Transient that breaks the css on staging site for Twenty Twenty Three theme
        ];

        if (!$this->isNetworkClone() && $freemiusHelper->hasFreemiusOptions()) {
            $toDelete = array_merge($toDelete, $freemiusHelper->getFreemiusOptions());
        }

        // Delete options for new clone or reset job
        if ($this->dto->getMainJob() !== MainJob::UPDATE) {
            // @see WPStaging\Pro\Backup\Storage\Storages\GoogleDrive\Auth::getOptionName for option name
            $toDelete[] = 'wpstg_googledrive';
            $toDelete[] = 'wpstg_dropbox';
            $toDelete[] = 'wpstg_one-drive';
            // Should we delete other cloud storage options too?
            $toDelete[] = FinishBackupTask::OPTION_LAST_BACKUP;
        }

        if ($this->dto->getMainJob() === MainJob::STAGING) {
            $toDelete[] = BackupRetentionHandler::OPTION_BACKUPS_RETENTION;
        }

        $this->deleteOptions($toDelete);

        return true;
    }

    protected function updateOrInsertOptions($options)
    {
        foreach ($options as $name => $value) {
            $this->debugLog("Updating/inserting $name to $value");
            if ($this->insertDbOption($name, $value) === false) {
                $this->log("Failed to update/insert $name {$this->dto->getStagingDb()->last_error}", Logger::TYPE_WARNING);
            }
        }
    }

    protected function updateOptions($options)
    {
        foreach ($options as $name => $value) {
            $this->debugLog("Updating $name to $value");
            if ($this->updateDbOption($name, $value) === false) {
                $this->log("Failed to update $name {$this->dto->getStagingDb()->last_error}", Logger::TYPE_WARNING);
            }
        }
    }

    /**
     * Delete given options
     *
     * @param array $options
     */
    protected function deleteOptions($options)
    {
        foreach ($options as $option) {
            $this->debugLog("Deleting $option");
            if ($this->deleteDbOption($option) === false) {
                $this->log("Failed to delete $option {$this->dto->getStagingDb()->last_error}", Logger::TYPE_WARNING);
            }
        }
    }

    /**
     * @return void|array
     */
    protected function getActivePluginsToUpdate()
    {
        $excludedTable = $this->dto->getPrefix() . 'options';
        if (!in_array($excludedTable, $this->dto->getTables())) {
            return;
        }

        // Prevent filters tampering with the active plugins list, such as wpstg-optimizer.php itself.
        remove_all_filters('option_active_plugins');

        $activePlugins = get_option('active_plugins');
        if (!is_array($activePlugins)) {
            $activePlugins = [];
        }

        $activePlugins = apply_filters(self::FILTER_CLONING_UPDATE_ACTIVE_PLUGINS, $activePlugins);

        return $activePlugins;
    }
}
