<?php

namespace WPStaging\Staging\Tasks\StagingSite\DatabaseAdjustment;

use WPStaging\Backup\BackupRetentionHandler;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Framework\Adapter\Database;
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

/**
 * Replacement for WPStaging\Framework\CloningProcess\Data\UpdateStagingOptionsTable
 */
class UpdateOptionsInOptionsTableTask extends DatabaseAdjustmentTask
{
    /**
     * @var string
     */
    const FILTER_CLONING_UPDATE_ACTIVE_PLUGINS = 'wpstg.cloning.update_active_plugins';

    /**
     * @var ExcludedPlugins
     */
    protected $excludedPlugins;

    /**
     * @var FreemiusScript
     */
    protected $freemiusScript;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     * @param Urls $urls
     * @param Database $database
     */
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Urls $urls, Database $database, FreemiusScript $freemiusScript, ExcludedPlugins $excludedPlugins)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue, $urls, $database);
        $this->freemiusScript  = $freemiusScript;
        $this->excludedPlugins = $excludedPlugins;
    }

    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'staging_update_options';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Update options in options table';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute()
    {
        $this->setup();
        $this->updateOptionsTable();

        return $this->generateResponse();
    }

    protected function updateOptionsTable(): bool
    {
        $updateOrInsert = [
            'wpstg_is_staging_site' => 'true',
            'wpstg_rmpermalinks_executed' => ' ',
            'blog_public' => 0,
            FirstRun::FIRST_RUN_KEY => 'true',
        ];

        $jobType      = $this->jobDataDto->getJobType();
        $cloneOptions = [
            FirstRun::MAILS_DISABLED_KEY          => !((bool) $this->jobDataDto->getEmailsAllowed()),
            ExcludedPlugins::EXCLUDED_PLUGINS_KEY => $this->excludedPlugins->getFilteredPluginsToExclude(),
            FirstRun::WOO_SCHEDULER_DISABLED_KEY  => (bool) $this->jobDataDto->getWooSchedulerDisabled(),
        ];

        $this->adjustCloneOptions($cloneOptions);

        // only insert or update clone option if job is not updating
        // during update this data will be preserved
        if ($jobType !== StagingSetup::JOB_UPDATE) {
            $updateOrInsert[CloneOptions::WPSTG_CLONE_SETTINGS_KEY] = serialize((object) $cloneOptions);
        }

        if ($this->jobDataDto->getIsKeepPermalinks()) {
            /**
             * if staging site is created with keep permalinks setting off,
             * The below code make sure permalinks settings are kept during update,
             * when later production site has keep permalinks setting on,
             * without the need to also keep permalinks setting on staging site too.
             */
            $updateOrInsert['wpstg_rmpermalinks_executed'] = 'true';
        } else {
            $updateOrInsert['rewrite_rules'] = null;
            $updateOrInsert['permalink_structure'] = ' ';
        }

        // Only show freemius notice if freemius options exists on the productions site
        // These freemius options will be deleted from option table, see below.
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
            'upload_path'      => '',
            'wpstg_connection' => json_encode(['prodHostname' => get_site_url()]),
        ];

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

        // Options to delete on the staging site
        $toDelete = [
            '_transient_wp_core_block_css_files' // Transient that breaks the css on staging site for Twenty Twenty Three theme
        ];

        if (!$this->jobDataDto->getIsStagingNetwork() && $this->freemiusScript->hasFreemiusOptions()) {
            $toDelete = array_merge($toDelete, $this->freemiusScript->getFreemiusOptions());
        }

        // Delete options for new clone or reset job
        if ($jobType !== StagingSetup::JOB_UPDATE) {
            // @see WPStaging\Pro\Backup\Storage\GoogleDrive\Auth::getOptionName for option name
            $toDelete[] = 'wpstg_googledrive';
            $toDelete[] = 'wpstg_dropbox';
            $toDelete[] = 'wpstg_one-drive';
            // Should we delete other cloud storage options too?
            $toDelete[] = FinishBackupTask::OPTION_LAST_BACKUP;
        }

        if ($jobType === StagingSetup::JOB_NEW_STAGING_SITE) {
            $toDelete[] = BackupRetentionHandler::OPTION_BACKUPS_RETENTION;
        }

        $this->deleteOptions($toDelete);

        return true;
    }

    /**
     * @param array $options
     * @return void
     */
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

    /**
     * Delete given options
     *
     * @param array $options
     */
    protected function deleteOptions($options)
    {
        foreach ($options as $option) {
            $this->logger->debug("Deleting $option");
            if ($this->deleteOption($option) === false) {
                $this->logger->warning("Failed to delete $option. Error: {$this->lastError()}");
            }
        }
    }

    /**
     * @return array
     */
    protected function getActivePluginsToUpdate(): array
    {
        // Prevent filters tampering with the active plugins list, such as wpstg-optimizer.php itself.
        remove_all_filters('option_active_plugins');

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

    /**
     * @param array $cloneOptions
     */
    protected function adjustCloneOptions(array &$cloneOptions)
    {
        // used in pro
    }
}
