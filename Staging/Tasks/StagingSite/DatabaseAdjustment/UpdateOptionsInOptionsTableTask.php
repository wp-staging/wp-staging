<?php

namespace WPStaging\Staging\Tasks\StagingSite\DatabaseAdjustment;

use WPStaging\Backup\BackupRetentionHandler;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\WpAdapter;
use WPStaging\Framework\CloningProcess\ExcludedPlugins;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\ThirdParty\FreemiusScript;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Staging\CloneOptions;
use WPStaging\Staging\FirstRun;
use WPStaging\Staging\Service\StagingSetup;
use WPStaging\Staging\Sites;
use WPStaging\Staging\Tasks\DatabaseAdjustmentTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;




class UpdateOptionsInOptionsTableTask extends DatabaseAdjustmentTask
{



    const FILTER_CLONING_UPDATE_ACTIVE_PLUGINS = 'wpstg.cloning.update_active_plugins';

 
    const FILTER_CLONING_PRESERVE_UPLOAD_PATH = 'wpstg.cloning.preserve_upload_path';




    protected $excludedPlugins;




    protected $freemiusScript;









    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Urls $urls, Database $database, FreemiusScript $freemiusScript, ExcludedPlugins $excludedPlugins)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue, $urls, $database);
        $this->freemiusScript  = $freemiusScript;
        $this->excludedPlugins = $excludedPlugins;
    }




    public static function getTaskName()
    {
        return 'staging_update_options';
    }




    public static function getTaskTitle()
    {
        return 'Update options in options table';
    }




    public function execute()
    {
        $this->setup();
        $this->updateOptionsTable();

        return $this->generateResponse();
    }

    protected function updateOptionsTable(): bool
    {
        $updateOrInsert = [
            'wpstg_is_staging_site'       => 'true',
            'wpstg_rmpermalinks_executed' => ' ',
            'blog_public'                 => 0,
            FirstRun::FIRST_RUN_KEY       => 'true',
        ];

        $jobType      = $this->jobDataDto->getJobType();
        $cloneOptions = [
            FirstRun::MAILS_DISABLED_KEY          => !((bool) $this->jobDataDto->getIsEmailsAllowed()),
            ExcludedPlugins::EXCLUDED_PLUGINS_KEY => $this->excludedPlugins->getFilteredPluginsToExclude(),
            FirstRun::WOO_SCHEDULER_ENABLED_KEY   => (bool) $this->jobDataDto->getIsWooSchedulerEnabled(),
        ];

        $this->adjustCloneOptions($cloneOptions);

 
 
        if ($jobType !== StagingSetup::JOB_UPDATE) {
            $updateOrInsert[CloneOptions::WPSTG_CLONE_SETTINGS_KEY] = serialize((object) $cloneOptions);
        }

        if ($this->jobDataDto->getIsKeepPermalinks()) {






            $updateOrInsert['wpstg_rmpermalinks_executed'] = 'true';
        } else {
            $updateOrInsert['rewrite_rules'] = null;
            $updateOrInsert['permalink_structure'] = ' ';
        }

 
 
        if (!$this->jobDataDto->getIsStagingNetwork() && $this->freemiusScript->hasFreemiusOptions()) {
            $updateOrInsert[FreemiusScript::NOTICE_OPTION] = true;
        }

        if (!empty($this->jobDataDto->getTmpExcludedFullPaths())) {
            $updateOrInsert[Sites::STAGING_EXCLUDED_FILES_OPTION] = serialize(array_unique($this->jobDataDto->getTmpExcludedFullPaths()));
        }

        if (!empty($this->jobDataDto->getTmpExcludedGoDaddyFiles())) {
            $updateOrInsert[Sites::STAGING_EXCLUDED_GD_FILES_OPTION] = serialize(array_unique($this->jobDataDto->getTmpExcludedGoDaddyFiles()));
        }

        $this->updateOrInsertOptions($updateOrInsert);

        $update = [
            'wpstg_connection' => json_encode(['prodHostname' => get_site_url()]),
        ];

        if ($this->shouldResetUploadPath()) {
            $update['upload_path'] = '';
        }

        if ($jobType !== StagingSetup::JOB_UPDATE) {
            $update[Sites::STAGING_SITES_OPTION] = serialize([]);
        }

        if ($jobType === StagingSetup::JOB_NEW_STAGING_SITE) {
            $activePluginsToUpdate = $this->getActivePluginsToUpdate();
            if (is_array($activePluginsToUpdate)) {
                $update['active_plugins'] = serialize($activePluginsToUpdate);
            }
        }

        $this->updateOptions($update);

 
        $toDelete = [
            '_transient_wp_core_block_css_files' 
        ];

        if (!$this->jobDataDto->getIsStagingNetwork() && $this->freemiusScript->hasFreemiusOptions()) {
            $toDelete = array_merge($toDelete, $this->freemiusScript->getFreemiusOptions());
        }

 
        if ($jobType !== StagingSetup::JOB_UPDATE) {
 
            $toDelete[] = 'wpstg_google-drive';
            $toDelete[] = 'wpstg_googledrive'; 
            $toDelete[] = 'wpstg_dropbox';
            $toDelete[] = 'wpstg_one-drive';
            $toDelete[] = 'wpstg_pcloud';
 
            $toDelete[] = FinishBackupTask::OPTION_LAST_BACKUP;
        }

        if ($jobType === StagingSetup::JOB_NEW_STAGING_SITE) {
            $toDelete[] = BackupRetentionHandler::OPTION_BACKUPS_RETENTION;
        }

        $this->deleteOptions($toDelete);

        return true;
    }

    protected function shouldResetUploadPath(): bool
    {
        return !(bool)Hooks::applyFilters(self::FILTER_CLONING_PRESERVE_UPLOAD_PATH, false);
    }





    protected function updateOrInsertOptions(array $options)
    {
        foreach ($options as $name => $value) {
            $this->logger->debug("Updating/inserting $name to $value");
            if ($this->insertOption($name, $value) === false) {
                $this->logger->warning("Failed to update/insert $name. Error: {$this->lastError()}");
            }
        }
    }

    protected function updateOptions($options)
    {
        foreach ($options as $name => $value) {
            $this->logger->debug("Updating $name to $value");
            if ($this->updateOption($name, $value) === false) {
                $this->logger->warning("Failed to update $name. Error: {$this->lastError()}");
            }
        }
    }






    protected function deleteOptions($options)
    {
        foreach ($options as $option) {
            $this->logger->debug("Deleting $option");
            if ($this->deleteOption($option) === false) {
                $this->logger->warning("Failed to delete $option. Error: {$this->lastError()}");
            }
        }
    }




    protected function getActivePluginsToUpdate(): array
    {
 
        remove_all_filters(WpAdapter::FILTER_OPTION_ACTIVE_PLUGINS);

        $activePlugins = get_option('active_plugins');
        if (!is_array($activePlugins)) {
            $activePlugins = [];
        }

        $activePlugins = Hooks::applyFilters(self::FILTER_CLONING_UPDATE_ACTIVE_PLUGINS, $activePlugins);
        if (!is_array($activePlugins)) {
            $activePlugins = [];
        }

        return $activePlugins;
    }




    protected function adjustCloneOptions(array &$cloneOptions)
    {
 
    }
}
