<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use Exception;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Staging\Sites;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Dto\Task\Restore\RenameDatabaseTaskDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Backup\Service\Database\Exporter\ViewDDLOrder;
use WPStaging\Backup\Service\Database\Importer\TableViewsRenamer;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Framework\Database\TablesRenamer;
use WPStaging\Framework\SiteInfo;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class RenameDatabaseTask extends RestoreTask
{
    /** @var TableService */
    private $tableService;

    /** @var TablesRenamer */
    private $tablesRenamer;

    /** @var TableViewsRenamer */
    private $tableViewsRenamer;

    /** @var AccessToken */
    private $accessToken;

    /** @var SiteInfo */
    private $siteInfo;

    /** @var array An structured array of options to keep */
    protected $optionsToKeep = [];

    /** @var array Options to remove */
    protected $optionsToRemove = [];

    /** @var ViewDDLOrder */
    protected $viewDDLOrder;

    /** @var RenameDatabaseTaskDto */
    protected $currentTaskDto;

    public function __construct(SiteInfo $siteinfo, TablesRenamer $tablesRenamer, ViewDDLOrder $viewDDLOrder, TableService $tableService, TableViewsRenamer $tableViewsRenamer, AccessToken $accessToken, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->tableService      = $tableService;
        $this->tablesRenamer     = $tablesRenamer;
        $this->accessToken       = $accessToken;
        $this->viewDDLOrder      = $viewDDLOrder;
        $this->siteInfo          = $siteinfo;
        $this->tableViewsRenamer = $tableViewsRenamer;
    }

    public static function getTaskName(): string
    {
        return 'backup_restore_rename_database';
    }

    public static function getTaskTitle(): string
    {
        return 'Renaming Database Tables';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute(): TaskResponseDto
    {
        if ($this->jobDataDto->getIsDatabaseRestoreSkipped()) {
            $this->stepsDto->finish();
            return $this->generateResponse();
        }

        $this->setupTask();

        if ($this->jobDataDto->getIsMissingDatabaseFile()) {
            $this->logger->warning('Skipped restoring database.');
            $this->stepsDto->finish();
            return $this->generateResponse();
        }

        if ($this->stepsDto->getCurrent() === 0) {
            $this->preDatabaseRenameActions();
            return $this->generateResponse(true);
        }

        if ($this->stepsDto->getCurrent() === 1) {
            $incrementStep = $this->performDatabaseRename();
            return $this->generateResponse($incrementStep);
        }

        if ($this->stepsDto->getCurrent() === 2) {
            $this->postDatabaseRenameActions();
            return $this->generateResponse(true);
        }

        $this->stepsDto->finish();
        return $this->generateResponse();
    }

    /** @return string */
    protected function getCurrentTaskType(): string
    {
        return RenameDatabaseTaskDto::class;
    }

    /**
     * @return void
     */
    protected function setupTableRenamer()
    {
        $this->tablesRenamer->setTmpPrefix($this->jobDataDto->getTmpDatabasePrefix());
        $this->tablesRenamer->setProductionTablePrefix($this->tableService->getDatabase()->getPrefix());
        $this->tablesRenamer->setDropPrefix(DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP);
        $this->tablesRenamer->setShortNamedTablesToRename($this->jobDataDto->getShortNamesTablesToRestore());
        $this->tablesRenamer->setShortNamedTablesToDrop($this->jobDataDto->getShortNamesTablesToDrop());
        $this->tablesRenamer->setRenameViews(true);
        $this->tablesRenamer->setThresholdCallable([$this, 'isMaxExecutionThreshold']);
        // Tables to not restore in the site
        $this->tablesRenamer->setExcludedTables([
            'wpstg_queue'
        ]);

        if ($this->isSubsiteRestore()) {
            $this->tablesRenamer->setProductionTableBasePrefix($this->tableService->getDatabase()->getBasePrefix());
            $this->tablesRenamer->setTablesToPreserve([
                'blogs',
                'blogmeta',
                'blog_versions', // old multisite table
                'registration_log',
                'signups',
                'site',
                'sitemeta',
            ]);
        }
    }

    /**
     * This is an adaptation of wp_load_alloptions(), the difference is that it
     * fetches only the "option_name" from the database, not the values, to save memory.
     *
     * @return array An array of option names that are autoloaded.
     * @see wp_load_alloptions()
     *
     */
    protected function getAutoloadedOptions()
    {
        global $wpdb;
        $suppress = $wpdb->suppress_errors();
        $allOptionsDb = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE autoload = 'yes'");
        $wpdb->suppress_errors($suppress);

        $allOptions = [];
        foreach ((array)$allOptionsDb as $o) {
            $allOptions[] = $o->option_name;
        }

        return $allOptions;
    }

    /**
     * @return void
     */
    protected function keepOptions()
    {
        $allOptions = $this->getAutoloadedOptions();

        // Backups do not include staging sites, so we need to keep the original ones after restoring.
        // For version 2.x to 4.0.2
        $this->optionsToKeep[] = [
            'name' => 'wpstg_existing_clones_beta',
            'value' => get_option('wpstg_existing_clones_beta'),
            'autoload' => in_array('wpstg_existing_clones_beta', $allOptions),
        ];

        // For version > 4.0.3
        $this->optionsToKeep[] = [
            'name' => Sites::STAGING_SITES_OPTION,
            'value' => get_option(Sites::STAGING_SITES_OPTION),
            'autoload' => in_array(Sites::STAGING_SITES_OPTION, $allOptions),
        ];

        // Keep the original WP STAGING settings intact upon restoring.
        $this->optionsToKeep[] = [
            'name' => 'wpstg_settings',
            'value' => get_option('wpstg_settings'),
            'autoload' => in_array('wpstg_settings', $allOptions),
        ];

        // If this is a staging site, keep the staging site status after restore.
        $this->optionsToKeep[] = [
            'name' => 'wpstg_is_staging_site',
            'value' => get_option('wpstg_is_staging_site'),
            'autoload' => in_array('wpstg_is_staging_site', $allOptions),
        ];

        // Preserve backup schedules
        $this->optionsToKeep[] = [
            'name' => BackupScheduler::OPTION_BACKUP_SCHEDULES,
            'value' => get_option(BackupScheduler::OPTION_BACKUP_SCHEDULES),
            'autoload' => in_array(BackupScheduler::OPTION_BACKUP_SCHEDULES, $allOptions),
        ];

        // Preserve existing blog_public value.
        $this->optionsToKeep[] = [
            'name' => 'blog_public',
            'value' => get_option('blog_public'),
            'autoload' => in_array('blog_public', $allOptions),
        ];

        // Last Backup option
        $this->optionsToKeep[] = [
            'name' => FinishBackupTask::OPTION_LAST_BACKUP,
            'value' => get_option(FinishBackupTask::OPTION_LAST_BACKUP),
            'autoload' => in_array(FinishBackupTask::OPTION_LAST_BACKUP, $allOptions),
        ];

        global $wpdb;

        $analyticsEvents = $wpdb->get_results("SELECT * FROM $wpdb->options WHERE `option_name` LIKE 'wpstg_analytics_event_%' LIMIT 0, 200");

        if (!empty($analyticsEvents)) {
            foreach ($analyticsEvents as $option) {
                $this->optionsToKeep[] = [
                    'name' => $option->option_name,
                    'value' => $option->option_value,
                    'autoload' => false,
                ];
            }
        }
    }

    /**
     * @return void
     */
    protected function setupRemoveOptions()
    {
        if (!$this->siteInfo->isStagingSite()) {
            $this->optionsToRemove[] = 'wpstg_is_staging_site';
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function preDatabaseRenameActions()
    {
        $tmpPrefix = $this->jobDataDto->getTmpDatabasePrefix();
        $this->setupTableRenamer();
        $this->setCurrentTaskDto($this->tablesRenamer->setupRenamer());

        // Store some information to re-add after we restore the database.
        $accessToken              = $this->accessToken->getToken();
        $isNetworkActivatedPlugin = is_plugin_active_for_network(WPSTG_PLUGIN_FILE);

        $this->keepOptions();
        $this->setupRemoveOptions();

        $totalTablesToRename = $this->tablesRenamer->getTotalTables();

        if ($totalTablesToRename === 0) {
            $this->logger->critical('Could not find any database table to restore. Backup seems to be corrupt. Contact support@wp-staging.com.');
            throw new Exception("Could not find any databse table to restore. Backup seems to be corrupt.");
        }

        $this->jobDataDto->setTotalTablesToRename($totalTablesToRename);
        $this->jobDataDto->setTotalTablesRenamed(0);

        $dataToPreserve = [
            'accessToken'              => $accessToken,
            'isNetworkActivatedPlugin' => $isNetworkActivatedPlugin,
            'optionsToKeep'            => $this->optionsToKeep,
            'optionsToRemove'          => $this->optionsToRemove,
            'activePlugins'            => $this->tablesRenamer->getActivePluginsToPreserve()
        ];

        if (is_multisite() && !$this->isSubsiteRestore()) {
            $dataToPreserve['activeSitewidePlugins'] = $this->tablesRenamer->getActiveSitewidePluginsToPreserve();
        }

        $this->jobDataDto->setDatabaseDataToPreserve($dataToPreserve);

        global $wpdb;
        $accessTokenOption = AccessToken::OPTION_NAME;
        $suppressErrors    = $wpdb->suppress_errors();
        $wpdb->query("UPDATE {$tmpPrefix}options SET option_value = '{$accessToken}' WHERE option_name = '{$accessTokenOption}'");
        $wpdb->suppress_errors($suppressErrors);

        $this->logger->info(sprintf('Found %d tables to restore', $this->jobDataDto->getTotalTablesToRename()));
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function performDatabaseRename(): bool
    {
        $this->setupTableRenamer();
        $this->tablesRenamer->setTaskDto($this->currentTaskDto);

        // We will try restoring non conflicting tables first,
        // If there are no non-conflicting tables renamed, we will stop the restore process.
        $result = $this->tablesRenamer->renameNonConflictingTables();
        if ($result === false) {
            if ($this->tablesRenamer->getRenamedTables() === 0) {
                $this->logger->critical('Could not restore non-conflicting tables. Contact support@wp-staging.com.');
                throw new Exception("Could not restore non-conflicting tables.");
            }

            $this->currentTaskDto->nonConflictingTablesRenamed = $this->tablesRenamer->getNonConflictingTablesRenamed();
            $this->logger->info(sprintf('Restored %d/%d tables', $this->currentTaskDto->nonConflictingTablesRenamed, $this->jobDataDto->getTotalTablesToRename()));
            $this->setCurrentTaskDto($this->currentTaskDto);
            return false;
        }

        // This condition is only fulfilled if all existing non-conflicting tables have been renamed in this current request and no other non-conflicting tables needs to be renamed.
        if ($this->tablesRenamer->getIsNonConflictingTablesRenamingTaskExecuted()) {
            $this->currentTaskDto->nonConflictingTablesRenamed = $this->tablesRenamer->getNonConflictingTablesRenamed();
            $this->logger->info(sprintf('Restored %d/%d tables', $this->currentTaskDto->nonConflictingTablesRenamed, $this->jobDataDto->getTotalTablesToRename()));
            $this->setCurrentTaskDto($this->currentTaskDto);
            return false;
        }

        $result = $this->tablesRenamer->renameConflictingTables();

        $this->currentTaskDto->conflictingTablesRenamed = $this->tablesRenamer->getConflictingTablesRenamed();
        $tablesRenamed = $this->currentTaskDto->nonConflictingTablesRenamed + $this->currentTaskDto->conflictingTablesRenamed;
        $this->logger->info(sprintf('Restored %d/%d tables', $tablesRenamed, $this->jobDataDto->getTotalTablesToRename()));
        $this->setCurrentTaskDto($this->currentTaskDto);

        if ($result === false) {
            if ($this->tablesRenamer->getRenamedTables() === 0) {
                $this->logger->critical('Could not rename any database table. Please contact support@wp-staging.com.');
                throw new Exception("Could not rename any database table.");
            }

            return false;
        }

        $this->renameViewReferences();
        $this->tablesRenamer->renameTablesToDrop();

        return true;
    }

    /**
     * Executes actions after a database has been restored.
     * @return void
     */
    protected function postDatabaseRenameActions()
    {
        /**
         * @var \wpdb $wpdb
         * @var \WP_Object_Cache $wp_object_cache
         */
        global $wpdb, $wp_object_cache;

        $databaseData = $this->jobDataDto->getDatabaseDataToPreserve();

        $this->optionsToKeep      = $databaseData['optionsToKeep'];
        $this->optionsToRemove    = $databaseData['optionsToRemove'];
        $originalAccessToken      = $databaseData['accessToken'];
        $isNetworkActivatedPlugin = $databaseData['isNetworkActivatedPlugin'];

        // Otherwise wp.com might throw a private site error, stopping restore to continue further.
        if (!$this->siteInfo->isHostedOnWordPressCom()) {
            // Reset cache
            wp_cache_init();

            // Make sure WordPress does not try to re-use any values fetched from the database thus far.
            $wpdb->flush();
            $wp_object_cache->flush();
            wp_suspend_cache_addition(true);
        }

        foreach ($this->optionsToKeep as $optionToKeep) {
            update_option($optionToKeep['name'], $optionToKeep['value'], $optionToKeep['autoload']);
        }

        foreach ($this->optionsToRemove as $optionToRemove) {
            delete_option($optionToRemove);
        }

        $this->tablesRenamer->setProductionTablePrefix($wpdb->prefix);

        update_option('wpstg.restore.justRestored', 'yes');
        update_option('wpstg.restore.justRestored.metadata', wp_json_encode($this->jobDataDto->getBackupMetadata()));

        // Re-set the Access Token as it was before restoring the database, so the requests remain authenticated
        $this->accessToken->setToken($originalAccessToken);

        // Force direct activation of this plugin in the database by bypassing activate_plugin at a low-level.
        $activeWpstgPlugin = plugin_basename(trim(WPSTG_PLUGIN_FILE));

        $this->tablesRenamer->restorePreservedActivePlugins($databaseData['activePlugins'], $activeWpstgPlugin, $isNetworkActivatedPlugin);
        if ($isNetworkActivatedPlugin && !$this->isSubsiteRestore()) {
            $this->tablesRenamer->restorePreservedActiveSitewidePlugins($databaseData['activeSitewidePlugins'], $activeWpstgPlugin);
        } elseif (is_multisite() && !$this->isSubsiteRestore()) {
            // Don't activate any wp staging plugin if it is not network activated on current site
            $this->tablesRenamer->restorePreservedActiveSitewidePlugins($databaseData['activeSitewidePlugins'], $wpstgPluginToActivate = '');
        }

        // Upgrade database if need be
        if (file_exists(trailingslashit(ABSPATH) . 'wp-admin/includes/upgrade.php')) {
            global $wpdb, $wp_db_version, $wp_current_db_version;
            require_once trailingslashit(ABSPATH) . 'wp-admin/includes/upgrade.php';

            $wp_current_db_version = (int)__get_option('db_version');
            if ($wp_db_version !== $wp_current_db_version) {
                // WP upgrade isn't too fussy about generating MySQL warnings such as "Duplicate key name" during an upgrade so suppress.
                $wpdb->suppress_errors();

                wp_upgrade();

                $this->logger->info(sprintf('WordPress database upgraded successfully from db version %s to %s.', $wp_current_db_version, $wp_db_version));
            }
        } else {
            $this->logger->warning('Could not upgrade WordPress database version as the wp-admin/includes/upgrade.php file does not exist.');
        }

        $this->logger->info('Database restored successfully.');

        do_action('wpstg.backup.import.database.postDatabaseRestoreActions');

        // Otherwise wp.com might throw a private site error, stopping restore to continue further.
        if (!$this->siteInfo->isHostedOnWordPressCom()) {
            // Logs the user out
            wp_logout();
        }
    }

    /**
     * @return void
     */
    protected function renameViewReferences()
    {
        $views = $this->tablesRenamer->getViewsToBeRenamed();
        foreach ($views as $view) {
            $query = $this->tableService->getCreateViewQuery($this->tableService->getDatabase()->getPrefix() . $view);
            $query = str_replace($this->jobDataDto->getTmpDatabasePrefix(), $this->tableService->getDatabase()->getPrefix(), $query);
            $this->viewDDLOrder->enqueueViewToBeWritten($this->tableService->getDatabase()->getPrefix() . $view, $query);
        }

        foreach ($this->viewDDLOrder->tryGetOrderedViews() as $tmpViewName => $viewQuery) {
            $this->tableViewsRenamer->renameViewReferences($viewQuery);
        }
    }

    /**
     * @return void
     */
    protected function setupTask()
    {
        if ($this->stepsDto->getTotal() > 0) {
            return;
        }

        $this->stepsDto->setTotal(3);
    }

    /**
     * @return bool
     */
    protected function isSubsiteRestore(): bool
    {
        if (!is_multisite()) {
            return false;
        }

        return $this->jobDataDto->getBackupMetadata()->getBackupType() !== BackupMetadata::BACKUP_TYPE_MULTISITE;
    }
}
