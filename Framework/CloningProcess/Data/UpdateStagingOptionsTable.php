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
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Framework\Adapter\WpAdapter;

class UpdateStagingOptionsTable extends DBCloningService
{
 
    const FILTER_CLONING_UPDATE_ACTIVE_PLUGINS = 'wpstg.cloning.update_active_plugins';

 
    const FILTER_CLONING_PRESERVE_UPLOAD_PATH = 'wpstg.cloning.preserve_upload_path';




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




    private function updateAllOptionsTables()
    {
        foreach (get_sites() as $site) {
            $tableName = $this->getOptionTableWithoutBasePrefix($site->blog_id);
            $this->setOptionTable($tableName);

            $this->log("Updating {$this->dto->getPrefix()}{$tableName} {$this->dto->getStagingDb()->last_error}");
            if ($this->skipOptionsTable()) {
                continue;
            }

            $this->updateOptionsTable(is_main_site($site->blog_id), (int)$site->blog_id);
        }

        return true;
    }







    private function updateOptionsTable($isMainSite = false, $sourceBlogId = null)
    {
        $updateOrInsert = [
            'wpstg_is_staging_site'       => 'true',
            'wpstg_rmpermalinks_executed' => ' ',
            'blog_public'                 => 0,
            FirstRun::FIRST_RUN_KEY       => 'true',
        ];

        $cloneOptions = [
            FirstRun::MAILS_DISABLED_KEY          => !((bool) $this->dto->getJob()->getOptions()->isEmailsAllowed),
            ExcludedPlugins::EXCLUDED_PLUGINS_KEY => (new ExcludedPlugins())->getFilteredPluginsToExclude(),
            FirstRun::WOO_SCHEDULER_ENABLED_KEY   => (bool) $this->dto->getJob()->getOptions()->isWooSchedulerEnabled,
        ];

 
 
        if ($this->dto->getJob()->isNetworkClone() && $isMainSite) {
            $cloneOptions[NetworkClone::NEW_NETWORK_CLONE_KEY] = 'true';
            $cloneOptions[NetworkClone::NETWORK_BASE_DIR_KEY]  = $this->dto->getStagingSitePath();
        }

 
 
        if ($this->dto->getMainJob() !== MainJob::UPDATE) {
            $updateOrInsert[CloneOptions::WPSTG_CLONE_SETTINGS_KEY] = serialize((object) $cloneOptions);
        }

        if (!$this->keepPermalinks()) {
            $updateOrInsert['rewrite_rules'] = null;
            $updateOrInsert['permalink_structure'] = ' ';
        } else {






            $updateOrInsert['wpstg_rmpermalinks_executed'] = 'true';
        }

        $freemiusHelper = new FreemiusScript();
 
 
        if (!$this->isNetworkClone() && $freemiusHelper->hasFreemiusOptions()) {
            $updateOrInsert[FreemiusScript::NOTICE_OPTION] = true;
        }

        if (isset($this->dto->getJob()->getOptions()->tmpExcludedFilesFullPath)) {
            $updateOrInsert[Sites::STAGING_EXCLUDED_FILES_OPTION] = serialize(array_unique((array)$this->dto->getJob()->getOptions()->tmpExcludedFilesFullPath));
        }

        if (isset($this->dto->getJob()->getOptions()->tmpExcludedHostingFiles)) {
            $updateOrInsert[Sites::STAGING_EXCLUDED_HOSTING_FILES_OPTION] = serialize(array_unique((array)$this->dto->getJob()->getOptions()->tmpExcludedHostingFiles));
        }

        $this->updateOrInsertOptions($updateOrInsert);

        $update = [
            'wpstg_connection' => json_encode(['prodHostname' => get_site_url()]),
        ];

 
        if (Hooks::applyFilters(self::FILTER_CLONING_PRESERVE_UPLOAD_PATH, false) === false) {
            $update['upload_path'] = '';
        }

        if ($this->dto->getMainJob() !== MainJob::UPDATE) {
            $update[Sites::STAGING_SITES_OPTION] = serialize([]);
        }

        if ($this->dto->getMainJob() === MainJob::STAGING) {
            $activePluginsToUpdate = $this->getActivePluginsToUpdate($sourceBlogId);
            if (is_array($activePluginsToUpdate)) {
                $update['active_plugins'] = serialize($activePluginsToUpdate);
            }
        }

        $this->updateOptions($update);

 
        $toDelete = [
            '_transient_wp_core_block_css_files' 
        ];

        if (!$this->isNetworkClone() && $freemiusHelper->hasFreemiusOptions()) {
            $toDelete = array_merge($toDelete, $freemiusHelper->getFreemiusOptions());
        }

 
        if ($this->dto->getMainJob() !== MainJob::UPDATE) {
 
            $toDelete[] = 'wpstg_google-drive';
            $toDelete[] = 'wpstg_googledrive'; 
            $toDelete[] = 'wpstg_dropbox';
            $toDelete[] = 'wpstg_one-drive';
            $toDelete[] = 'wpstg_pcloud';
            $toDelete[] = BackupScheduler::OPTION_BACKUP_SCHEDULES;
 
            $toDelete[] = 'wpstg_current_site_login_links';
 
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






    protected function deleteOptions($options)
    {
        foreach ($options as $option) {
            $this->debugLog("Deleting $option");
            if ($this->deleteDbOption($option) === false) {
                $this->log("Failed to delete $option {$this->dto->getStagingDb()->last_error}", Logger::TYPE_WARNING);
            }
        }
    }




    protected function getActivePluginsToUpdate($sourceBlogId = null)
    {
        $excludedTable = $this->dto->getPrefix() . 'options';
        if (!in_array($excludedTable, $this->dto->getTables())) {
            return;
        }

 
        remove_all_filters(WpAdapter::FILTER_OPTION_ACTIVE_PLUGINS);

        if ($sourceBlogId === null) {
            $activePlugins = get_option('active_plugins');
        } else {
            $activePlugins = get_blog_option($sourceBlogId, 'active_plugins');
        }
        if (!is_array($activePlugins)) {
            $activePlugins = [];
        }

        $activePlugins = Hooks::applyFilters(self::FILTER_CLONING_UPDATE_ACTIVE_PLUGINS, $activePlugins);

        return $activePlugins;
    }
}
