<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use Exception;
use RuntimeException;
use WPStaging\Backup\Dto\Task\Restore\RenameDatabaseTaskDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Traits\DatabaseTablesRenameTaskTrait;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\Settings\SettingsTable;




class RenameDatabaseTask extends RestoreTask
{
    use DatabaseTablesRenameTaskTrait;




    const HOOK_KEEP_OPTIONS = 'wpstg.backup.restore.keep_options';

 
    const FILTER_BACKUP_IMPORT_DATABASE_POST_DATABASE_RESTORE_ACTIONS = 'wpstg.backup.import.database.postDatabaseRestoreActions';




    const FILTER_EXCLUDE_TABLES_DURING_RESTORE = 'wpstg.backup.restore.exclude.tables';

 
    protected $currentTaskDto;

    public static function getTaskName(): string
    {
        return 'backup_restore_rename_database';
    }

    public static function getTaskTitle(): string
    {
        return 'Renaming Database Tables';
    }




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

 
    protected function getCurrentTaskType(): string
    {
        return RenameDatabaseTaskDto::class;
    }




    protected function setupTableRenamer()
    {
        $this->tablesRenamer->setTmpPrefix($this->jobDataDto->getTmpDatabasePrefix());
        $this->tablesRenamer->setProductionTablePrefix($this->tableService->getDatabase()->getPrefix());
        $this->tablesRenamer->setDropPrefix(DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP);
        $this->tablesRenamer->setShortNamedTablesToRename($this->jobDataDto->getShortNamesTablesToRestore());
        $this->tablesRenamer->setShortNamedTablesToDrop($this->jobDataDto->getShortNamesTablesToDrop());
        $this->tablesRenamer->setRenameViews(true);
        $this->tablesRenamer->setThresholdCallable([$this, 'isMaxExecutionThreshold']);
        $this->tablesRenamer->setStopOnRenameFailure(true);
        if ($this->logger instanceof Logger) {
            $this->tablesRenamer->setLogger($this->logger);
        }

 
        $excludedTables = [SettingsTable::TABLE_NAME, Queue::QUEUE_TABLE_NAME];
        $excludedTables = array_merge($excludedTables, Hooks::applyFilters(self::FILTER_EXCLUDE_TABLES_DURING_RESTORE, []));
        $this->tablesRenamer->setExcludedTables($excludedTables);

        $tablesToPreserve = [SettingsTable::TABLE_NAME, Queue::QUEUE_TABLE_NAME];
        $this->tablesRenamer->setDestinationSubsiteBlogIds([]);

        if ($this->isSubsiteRestore()) {
            $database   = $this->tableService->getDatabase();
            $basePrefix = $database->getBasePrefix();
            $this->tablesRenamer->setProductionTableBasePrefix($basePrefix);
            if (strcasecmp($database->getPrefix(), $basePrefix) === 0) {
                $this->tablesRenamer->setDestinationSubsiteBlogIds($this->getExistingDestinationSubsiteBlogIds());
            }

            $tablesToPreserve = array_merge($tablesToPreserve, [
                'blogs',
                'blogmeta',
                'blog_versions', 
                'registration_log',
                'signups',
                'site',
                'sitemeta',
            ]);
        }

        $this->tablesRenamer->setTablesToPreserve($tablesToPreserve);
    }







    protected function getLicenseOptionsToKeep(): array
    {
        return [
            'wpstg_license_key'    => get_option('wpstg_license_key'),
            'wpstg_license_status' => maybe_serialize(get_option('wpstg_license_status')),
        ];
    }





    protected function preDatabaseRenameActions()
    {
        $tmpPrefix = $this->jobDataDto->getTmpDatabasePrefix();
        $this->setupTableRenamer();
        $this->setCurrentTaskDto($this->tablesRenamer->setupRenamer());

        $tablesWithForeignKeysLeft = $this->tablesRenamer->dropForeignKeysFromTmpTables();
        if (!empty($tablesWithForeignKeysLeft)) {
            $message = 'Restore stopped, the foreign keys of these tables could not be removed: ' . implode(', ', $tablesWithForeignKeysLeft) . '. ' . implode(' ', $this->tablesRenamer->getErrors());
            $this->logger->critical($message);
            throw new Exception($message);
        }

        $this->logDroppedForeignKeys();

 
        $accessToken              = $this->accessToken->getToken();
        $isNetworkActivatedPlugin = is_plugin_active_for_network(WPSTG_PLUGIN_FILE);

        $this->keepOptions();
        $this->preserveOptionsInTemporaryTable();
        $this->setupRemoveOptions();
        $this->preserveTransientOptions();

        $totalTablesToRename = $this->tablesRenamer->getTotalTables();

        if ($totalTablesToRename === 0) {
            $this->logger->critical('Could not find any database table to restore. Backup seems to be corrupt. Contact support@wp-staging.com.');
            throw new Exception("Could not find any database table to restore. Backup seems to be corrupt.");
        }

        $this->jobDataDto->setTotalTablesToRename($totalTablesToRename);
        $this->jobDataDto->setTotalTablesRenamed(0);

        $activePluginsToPreserve = $this->tablesRenamer->getActivePluginsToPreserve();
        if (empty($activePluginsToPreserve)) {
            throw new Exception("Could not find any active plugin to preserve. Database not restored properly.");
        }

        $this->tablesRenamer->resetErrors();
        $dataToPreserve = [
            'accessToken'              => $accessToken,
            'isNetworkActivatedPlugin' => $isNetworkActivatedPlugin,
            'productionTablePrefix'    => $this->tableService->getDatabase()->getPrefix(),
            'optionsToKeep'            => $this->optionsToKeep,
            'optionsToRemove'          => $this->optionsToRemove,
            'activePlugins'            => $activePluginsToPreserve,
        ];

        if (is_multisite() && !$this->isSubsiteRestore()) {
            $dataToPreserve['activeSitewidePlugins'] = $this->tablesRenamer->getActiveSitewidePluginsToPreserve();
        }

        $this->logTablesRenamerErrors();

        $this->jobDataDto->setDatabaseDataToPreserve($dataToPreserve);

        global $wpdb;
        $accessTokenOption = AccessToken::OPTION_NAME;
        $suppressErrors    = $wpdb->suppress_errors();
        $wpdb->query("UPDATE {$tmpPrefix}options SET option_value = '{$accessToken}' WHERE option_name = '{$accessTokenOption}'");
        $wpdb->suppress_errors($suppressErrors);

        $this->logger->info(sprintf('Found %d tables to restore.', $this->jobDataDto->getTotalTablesToRename()));
    }




    protected function logDroppedForeignKeys()
    {
        $droppedForeignKeys = $this->tablesRenamer->getDroppedForeignKeys();
        if (empty($droppedForeignKeys)) {
            return;
        }

        $tables = [];
        foreach ($droppedForeignKeys as $tableName => $constraintNames) {
            $tables[] = $tableName . ' (' . implode(', ', $constraintNames) . ')';
        }

        $this->logger->info('The restored site does not keep the foreign keys of these tables: ' . implode(', ', $tables) . '.');
    }





    protected function performDatabaseRename(): bool
    {
        if (!$this->renameImportedTables('restore', 'Restored', $this->jobDataDto->getTotalTablesToRename())) {
            return false;
        }

        $this->renameViewReferences($this->jobDataDto->getTmpDatabasePrefix());
        $this->tablesRenamer->renameTablesToDrop();

        return true;
    }





    protected function postDatabaseRenameActions()
    {




        global $wpdb, $wp_object_cache;

        $databaseData = $this->jobDataDto->getDatabaseDataToPreserve();

        $this->optionsToKeep      = $databaseData['optionsToKeep'];
        $this->optionsToRemove    = $databaseData['optionsToRemove'];
        $originalAccessToken      = $databaseData['accessToken'];
        $isNetworkActivatedPlugin = $databaseData['isNetworkActivatedPlugin'];
        $productionTablePrefix = $databaseData['productionTablePrefix'] ?? $this->tableService->getDatabase()->getProductionPrefix();
        $this->tablesRenamer->setProductionTablePrefix($productionTablePrefix);

 
        if (!$this->siteInfo->isHostedOnWordPressCom()) {
 
            wp_cache_init();

 
            $wpdb->flush();
            $wp_object_cache->flush();
            wp_suspend_cache_addition(true);
        }

        $this->restoreKeptOptions('backup');

        $wpdb->flush();
        $wp_object_cache->flush();

        foreach ($this->optionsToRemove as $optionToRemove) {
            delete_option($optionToRemove);
        }

        update_option('wpstg.restore.justRestored', 'yes');
        update_option('wpstg.restore.justRestored.metadata', wp_json_encode($this->jobDataDto->getBackupMetadata()));

 
        $this->accessToken->setToken($originalAccessToken);

 
        $activeWpstgPlugin = plugin_basename(trim(WPSTG_PLUGIN_FILE));

        $this->tablesRenamer->resetErrors();
        $this->tablesRenamer->restorePreservedActivePlugins($databaseData['activePlugins'], $activeWpstgPlugin, $isNetworkActivatedPlugin);
        if ($isNetworkActivatedPlugin && !$this->isSubsiteRestore()) {
            $this->tablesRenamer->restorePreservedActiveSitewidePlugins($databaseData['activeSitewidePlugins'], $activeWpstgPlugin);
        } elseif (is_multisite() && !$this->isSubsiteRestore()) {
 
            $this->tablesRenamer->restorePreservedActiveSitewidePlugins($databaseData['activeSitewidePlugins'], $wpstgPluginToActivate = '');
        }

        $this->logTablesRenamerErrors();







        if (!$this->siteInfo->isHostedOnWordPressCom()) {
            $wp_object_cache->flush();
        }

        $this->upgradeWordPressDatabaseIfNeeded();

        $this->logger->info('Database restored successfully.');

        Hooks::doAction(self::FILTER_BACKUP_IMPORT_DATABASE_POST_DATABASE_RESTORE_ACTIONS);
    }




    protected function isSubsiteRestore(): bool
    {
        if (!is_multisite()) {
            return false;
        }

        return $this->jobDataDto->getBackupMetadata()->getBackupType() !== BackupMetadata::BACKUP_TYPE_MULTISITE;
    }




    protected function getExistingDestinationSubsiteBlogIds(): array
    {
        $database   = $this->tableService->getDatabase();
        $basePrefix = $database->getBasePrefix();
        $wpdb       = $database->getWpdba()->getClient();
        $rows       = $wpdb->get_results("SELECT blog_id FROM `{$basePrefix}blogs` WHERE blog_id > 1", ARRAY_A);
        if (!is_array($rows) || $wpdb->last_error !== '') {
            throw new RuntimeException('Unable to read destination subsite IDs before renaming database tables.');
        }

        $blogIds = [];
        foreach ($rows as $row) {
            if (isset($row['blog_id'])) {
                $blogIds[] = (int) $row['blog_id'];
            }
        }

        return $blogIds;
    }













    protected function preserveOptionsInTemporaryTable()
    {
        foreach ($this->optionsToKeep as &$optionToKeep) {
            if (empty($optionToKeep['name']) || !is_string($optionToKeep['name'])) {
                continue;
            }

            $productionOption       = $this->tablesRenamer->getProductionOptionData($optionToKeep['name']);
            $optionToKeep['exists'] = !empty($productionOption['exists']);
            if ($optionToKeep['exists']) {
                $optionToKeep['autoload'] = $productionOption['autoload'];
            }

            $this->tablesRenamer->preserveTmpOption($optionToKeep['name'], !empty($optionToKeep['autoload']));
        }

        unset($optionToKeep);
    }
}
