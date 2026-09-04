<?php

namespace WPStaging\Framework\Database;

use WPStaging\Backup\Dto\Task\Restore\RenameDatabaseTaskDto;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Adapter\PhpAdapter;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Traits\SerializeTrait;





class TablesRenamer
{
    use SerializeTrait;

 
    const OPTION_ACTIVE_PLUGINS = 'active_plugins';

 
    const OPTION_ACTIVE_SITEWIDE_PLUGINS = 'active_sitewide_plugins';

 
    const PLUGIN_BASE_SLUG = 'wp-staging';

 
    const PLUGIN_FILE_NAMES = ['wp-staging.php', 'wp-staging-pro.php'];

 
    private $tableService;









    protected $tablesBeingRenamed = [];









    protected $tablesBeingRenamedUnprefixed = [];









    protected $existingTables = [];









    protected $existingTablesUnprefixed = [];

 
    protected $customTablesBeingRenamed = [];

 
    protected $shortNamedTablesToRename = [];

 
    protected $shortNamedTablesToDrop = [];

 
    protected $excludedTables = [];

 
    protected $tablesToBeDropped = [];

 
    protected $tablesToPreserve = [];

 
    protected $destinationSubsiteBlogIds = [];

 
    protected $totalTables = 0;





    protected $tablesRenamed = 0;

 
    protected $tablesRemainingToBeDropped = 0;

 
    protected $productionTablePrefix = '';

 
    protected $productionTableBasePrefix = '';

 
    protected $tmpPrefix = '';

 
    protected $customTableTmpPrefix = '';

 
    protected $dropPrefix = '';

 
    protected $renameViews = false;

 
    protected $renameCustomTables = false;

 
    protected $logEachRename = false;

 
    protected $stopOnRenameFailure = false;

 
    protected $logger = null;

 
    protected $phpAdapter;

 
    protected $thresholdCallable = null;

 
    protected $conflictingTablesRenamed = 0;

 
    protected $nonConflictingTablesRenamed = 0;

 
    protected $customTablesRenamed = 0;

 
    protected $isNonConflictingTablesRenamingTaskExecuted = false;

 
    protected $isRenamingForSubsite = false;

 
    protected $errors = [];

    public function __construct(TableService $tableService, PhpAdapter $phpAdapter)
    {
        $this->tableService = $tableService;
        $this->phpAdapter   = $phpAdapter;
    }





    public function setProductionTablePrefix(string $productionTablePrefix): TablesRenamer
    {
        $this->productionTablePrefix = $productionTablePrefix;
        return $this;
    }






    public function setProductionTableBasePrefix(string $productionTableBasePrefix, bool $isRenamingForSubsite = true): TablesRenamer
    {
        $this->productionTableBasePrefix = $productionTableBasePrefix;
        $this->isRenamingForSubsite      = $isRenamingForSubsite;
        return $this;
    }





    public function setTmpPrefix(string $tmpPrefix): TablesRenamer
    {
        $this->tmpPrefix = $tmpPrefix;
        return $this;
    }





    public function setCustomTableTmpPrefix(string $customTableTmpPrefix): TablesRenamer
    {
        $this->customTableTmpPrefix = $customTableTmpPrefix;
        return $this;
    }





    public function setDropPrefix(string $dropPrefix): TablesRenamer
    {
        $this->dropPrefix = $dropPrefix;
        return $this;
    }





    public function setRenameViews(bool $renameViews): TablesRenamer
    {
        $this->renameViews = $renameViews;
        return $this;
    }





    public function setRenameCustomTables(bool $renameCustomTables): TablesRenamer
    {
        $this->renameCustomTables = $renameCustomTables;
        return $this;
    }





    public function setLogEachRename(bool $logEachRename): TablesRenamer
    {
        $this->logEachRename = $logEachRename;
        return $this;
    }





    public function setStopOnRenameFailure(bool $stopOnRenameFailure): TablesRenamer
    {
        $this->stopOnRenameFailure = $stopOnRenameFailure;
        return $this;
    }





    public function setLogger(Logger $logger): TablesRenamer
    {
        $this->logger = $logger;
        return $this;
    }





    public function setThresholdCallable($thresholdCallable): TablesRenamer
    {
        $this->thresholdCallable = $thresholdCallable;
        return $this;
    }





    public function setShortNamedTablesToRename(array $shortNamedTablesToRename): TablesRenamer
    {
        $this->shortNamedTablesToRename = $shortNamedTablesToRename;
        return $this;
    }





    public function setShortNamedTablesToDrop(array $shortNamedTablesToDrop): TablesRenamer
    {
        $this->shortNamedTablesToDrop = $shortNamedTablesToDrop;
        return $this;
    }





    public function setExcludedTables(array $excludedTables): TablesRenamer
    {
        $this->excludedTables = $excludedTables;
        return $this;
    }





    public function setTablesToPreserve(array $tablesToPreserve): TablesRenamer
    {
        $this->tablesToPreserve = $tablesToPreserve;
        return $this;
    }





    public function setDestinationSubsiteBlogIds(array $blogIds): TablesRenamer
    {
        $this->destinationSubsiteBlogIds = [];
        foreach ($blogIds as $blogId) {
            $blogId = (int) $blogId;
            if ($blogId > 1) {
                $this->destinationSubsiteBlogIds[$blogId] = true;
            }
        }

        return $this;
    }

 
    public function getRenamedTables(): int
    {
        return $this->tablesRenamed;
    }

 
    public function getTotalTables(): int
    {
        return $this->totalTables;
    }

 
    public function getTablesRemainingToBeDropped(): int
    {
        return $this->tablesRemainingToBeDropped;
    }

 
    public function getViewsToBeRenamed(): array
    {
        return $this->tablesBeingRenamedUnprefixed['views'];
    }




    public function getConflictingTablesRenamed(): int
    {
        return $this->conflictingTablesRenamed;
    }




    public function getNonConflictingTablesRenamed(): int
    {
        return $this->nonConflictingTablesRenamed;
    }




    public function getCustomTablesRenamed(): int
    {
        return $this->customTablesRenamed;
    }




    public function getIsNonConflictingTablesRenamingTaskExecuted(): bool
    {
        return $this->isNonConflictingTablesRenamingTaskExecuted;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }




    public function resetErrors()
    {
        $this->errors = [];
    }




    public function setupRenamer(): RenameDatabaseTaskDto
    {
        $taskDto                     = new RenameDatabaseTaskDto();
        $taskDto->tablesBeingRenamed = $this->tableService->findTableNamesStartWith($this->tmpPrefix) ?: [];
        $taskDto->viewsBeingRenamed  = [];
        if ($this->renameViews) {
            $taskDto->viewsBeingRenamed = $this->tableService->findViewsNamesStartWith($this->tmpPrefix) ?: [];
        }

        $taskDto->customTablesBeingRenamed = [];
        if ($this->renameCustomTables) {
            $taskDto->customTablesBeingRenamed = $this->tableService->findTableNamesStartWith($this->customTableTmpPrefix) ?: [];
        }

        $taskDto->existingTables = $this->tableService->findTableNamesStartWith($this->productionTablePrefix) ?: [];
        if ($this->isRenamingForSubsite && !in_array($this->productionTableBasePrefix . 'users', $taskDto->existingTables)) {
            $taskDto->existingTables[] = $this->productionTableBasePrefix . 'users';
            $taskDto->existingTables[] = $this->productionTableBasePrefix . 'usermeta';
        }

        $taskDto->existingViews = [];
        if ($this->renameViews) {
            $taskDto->existingViews = $this->tableService->findViewsNamesStartWith($this->productionTablePrefix) ?: [];
        }

        $taskDto->conflictingTablesRenamed    = 0;
        $taskDto->nonConflictingTablesRenamed = 0;
        $taskDto->customTablesRenamed         = 0;
        $this->setTaskDto($taskDto);

        return $taskDto;
    }





    public function setTaskDto(RenameDatabaseTaskDto $taskDto)
    {
        $this->tablesBeingRenamed           = [];
        $this->tablesBeingRenamed['tables'] = $taskDto->tablesBeingRenamed ?: [];
        $this->tablesBeingRenamed['views']  = $taskDto->viewsBeingRenamed ?: [];
        $this->tablesBeingRenamed['custom'] = $taskDto->customTablesBeingRenamed ?: [];
        $this->tablesBeingRenamed['all']    = array_merge($this->tablesBeingRenamed['tables'], $this->tablesBeingRenamed['views']);

        $this->existingTables           = [];
        $this->existingTables['tables'] = $taskDto->existingTables ?: [];
        $this->existingTables['views']  = $taskDto->existingViews ?: [];
        $this->existingTables['all']    = array_merge($this->existingTables['tables'], $this->existingTables['views']);

        $this->totalTables = count($this->tablesBeingRenamed['tables']) + count($this->tablesBeingRenamed['custom']);
        $tmpDatabasePrefix = $this->tmpPrefix;

        foreach ($this->tablesBeingRenamed as $viewsOrTables => $tableName) {
            $this->tablesBeingRenamedUnprefixed[$viewsOrTables] = array_map(function ($tableName) use ($tmpDatabasePrefix) {
                $tableName = $this->getFullNameTableFromShortName($tableName, $tmpDatabasePrefix);
                return substr($tableName, strlen($tmpDatabasePrefix));
            }, $this->tablesBeingRenamed[$viewsOrTables]);
        }

        $productionTablePrefix = $this->productionTablePrefix;
        $isSubsiteRestore      = $this->isRenamingForSubsite;
        $baseProdTablePrefix   = $this->productionTableBasePrefix;

        foreach ($this->existingTables as $viewsOrTables => $tableName) {
            $this->existingTablesUnprefixed[$viewsOrTables] = array_map(function ($tableName) use ($productionTablePrefix, $baseProdTablePrefix, $isSubsiteRestore) {
                if ($isSubsiteRestore && in_array($tableName, [ $baseProdTablePrefix . 'users', $baseProdTablePrefix . 'usermeta' ])) {
                    return substr($tableName, strlen($baseProdTablePrefix));
                }

                return substr($tableName, strlen($productionTablePrefix));
            }, $this->existingTables[$viewsOrTables]);
        }

        $this->conflictingTablesRenamed    = (int)$taskDto->conflictingTablesRenamed;
        $this->nonConflictingTablesRenamed = (int)$taskDto->nonConflictingTablesRenamed;
        $this->customTablesRenamed         = (int)$taskDto->customTablesRenamed;
    }







    public function getFullNameTableFromShortName(string $table, string $prefix): string
    {
        $shortTables = [];
        if ($prefix === $this->tmpPrefix) {
            $shortTables = $this->shortNamedTablesToRename;
        } elseif ($prefix === $this->dropPrefix) {
            $shortTables = $this->shortNamedTablesToDrop;
        }

        if (!array_key_exists($table, $shortTables)) {
            return $table;
        }

        return $shortTables[$table];
    }






    public function getTableShortName(string $table, string $prefix)
    {
        $shortTables = [];
        if ($prefix === $this->tmpPrefix) {
            $shortTables = $this->shortNamedTablesToRename;
        } elseif ($prefix === $this->dropPrefix) {
            $shortTables = $this->shortNamedTablesToDrop;
        }

        return array_search($table, $shortTables);
    }





    public function renameConflictingTables(): bool
    {
        $conflictingTablesWithoutPrefix = array_values($this->getTablesThatExistInBothExistingAndTempUnprefixed());
 
        if (empty($conflictingTablesWithoutPrefix)) {
            return true;
        }

 
        if (count($conflictingTablesWithoutPrefix) <= $this->conflictingTablesRenamed) {
            return true;
        }

        $database = $this->tableService->getDatabase();
        $database->exec('START TRANSACTION;');

        try {
            for ($i = $this->conflictingTablesRenamed; $i < count($conflictingTablesWithoutPrefix); $i++) {
                $conflictingTableWithoutPrefix = $conflictingTablesWithoutPrefix[$i];
                if ($this->isExcludedTable($conflictingTableWithoutPrefix)) {
                    $this->tablesRenamed++;
                    $this->conflictingTablesRenamed++;
                    continue;
                }

                if ($this->isTableToPreserve($conflictingTableWithoutPrefix)) {
                    $this->tablesRenamed++;
                    $this->nonConflictingTablesRenamed++;
                    continue;
                }

                if ($this->isAlreadyRenamed($conflictingTableWithoutPrefix)) {
                    $this->tablesRenamed++;
                    $this->conflictingTablesRenamed++;
                    continue;
                }

                $currentTable = $this->getCurrentSiteTable($conflictingTableWithoutPrefix);
                $tableToDrop  = $this->getTableShortName($currentTable, $this->dropPrefix);
                if ($tableToDrop === false) {
                    $tableToDrop = $this->dropPrefix . $conflictingTableWithoutPrefix;
                }

                if ($this->tableExists($currentTable)) {
                    $this->renameTableOrFail($currentTable, $tableToDrop);
                }

                $this->renameTable($conflictingTableWithoutPrefix, $this->conflictingTablesRenamed);

                if ($this->isThresholdReached()) {
                    return false;
                }
            }
        } finally {
            $database->exec('COMMIT;');
        }

        return true;
    }





    public function renameNonConflictingTables(): bool
    {
        $nonConflictingTables = array_values($this->getTablesThatExistInTempButNotInSite());
 
        if (empty($nonConflictingTables)) {
            return true;
        }

 
        if (count($nonConflictingTables) <= $this->nonConflictingTablesRenamed) {
            return true;
        }

        $database = $this->tableService->getDatabase();
        $database->exec('START TRANSACTION;');

        try {
            for ($i = $this->nonConflictingTablesRenamed; $i < count($nonConflictingTables); $i++) {
                $nonConflictingTable = $nonConflictingTables[$i];
                if ($this->isExcludedTable($nonConflictingTable)) {
                    $this->tablesRenamed++;
                    $this->nonConflictingTablesRenamed++;
                    continue;
                }

                if ($this->isTableToPreserve($nonConflictingTable)) {
                    $this->tablesRenamed++;
                    $this->nonConflictingTablesRenamed++;
                    continue;
                }

                if ($this->isAlreadyRenamed($nonConflictingTable)) {
                    $this->tablesRenamed++;
                    $this->nonConflictingTablesRenamed++;
                    $this->isNonConflictingTablesRenamingTaskExecuted = true;
                    continue;
                }

                $this->renameTable($nonConflictingTable, $this->nonConflictingTablesRenamed);
                $this->isNonConflictingTablesRenamingTaskExecuted = true;

                if ($this->isThresholdReached()) {
                    return false;
                }
            }
        } finally {
            $database->exec('COMMIT;');
        }

        return true;
    }




    public function cleanTemporaryBackupTables(): bool
    {
 
        if ($this->nonConflictingTablesRenamed !== 0 || $this->conflictingTablesRenamed !== 0) {
            return true;
        }

        $this->tablesToBeDropped          = $this->tableService->findTableNamesStartWith($this->dropPrefix) ?: [];
        $this->tablesRemainingToBeDropped = count($this->tablesToBeDropped);

        $this->tableService->getDatabase()->exec('SET autocommit=0;');
        $this->tableService->getDatabase()->exec('SET FOREIGN_KEY_CHECKS=0;');
        $this->tableService->getDatabase()->exec('START TRANSACTION;');
        foreach ($this->tablesToBeDropped as $table) {
            $result = $this->tableService->getDatabase()->exec(sprintf(
                "DROP TABLE `%s`;",
                $table
            ));

 
            if ($result === false) {
                $this->tableService->getDatabase()->exec('COMMIT;');
                $this->tableService->getDatabase()->exec('SET autocommit=1;');
                return false;
            }

            $this->tablesRemainingToBeDropped--;
        }

        $this->tableService->getDatabase()->exec('COMMIT;');
        $this->tableService->getDatabase()->exec('SET autocommit=1;');
        return true;
    }




    public function renameTablesToDrop()
    {
        foreach ($this->getTablesThatExistInSiteButNotInTemp() as $table) {
            if ($this->isTableToPreserve($table) || $this->isExistingDestinationSubsiteTable($table)) {
                continue;
            }

            $fullTableName = $this->productionTablePrefix . $table;
            $tableToDrop   = $this->getTableShortName($fullTableName, $this->dropPrefix);
            if ($tableToDrop === false) {
                $tableToDrop = $this->dropPrefix . $table;
            }

            $result = $this->tableService->getDatabase()->exec(sprintf(
                "RENAME TABLE `%s` TO `%s`;",
                $fullTableName,
                $tableToDrop
            ));

            if ($result !== false) {
                continue;
            }

            if ($this->logger instanceof Logger) {
                $this->logger->warning(sprintf(
                    'DB Rename: Unable to move the table %s aside to %s. Error: %s. That table is not part of the backup and has been left in place.',
                    $fullTableName,
                    $tableToDrop,
                    $this->tableService->getLastWpdbError()
                ));
            }
        }
    }





    public function renameCustomTables(): bool
    {
        $customTablesToRename = $this->tablesBeingRenamed['custom'];
 
        if (empty($customTablesToRename)) {
            return true;
        }

        foreach ($customTablesToRename as $tmpCustomTable) {
            $customTable = substr($tmpCustomTable, strlen($this->customTableTmpPrefix));

            $result = true;
            if ($this->tableExists($customTable)) {
                $result = $this->renameQuery($customTable, $this->dropPrefix . $customTable);
            }

            if ($result === false) {
                throw new \RuntimeException("Unable to rename custom table {$customTable} to {$this->dropPrefix}{$customTable}.");
            }

            $result = $this->renameQuery($tmpCustomTable, $customTable);
            if ($result === false) {
                throw new \RuntimeException("Unable to rename custom table {$tmpCustomTable} to {$customTable}.");
            }

            $this->customTablesRenamed++;
            if ($this->isThresholdReached()) {
                return false;
            }
        }

        return true;
    }






    public function getActivePluginsToPreserve(): string
    {
        $tmpOptionsTable = $this->tmpPrefix . 'options';
        if (!$this->tableExists($tmpOptionsTable)) {
            return '';
        }

        $productionOptionsTable  = $this->productionTablePrefix . 'options';
        $activePluginsToPreserve = $this->getOptionValue($tmpOptionsTable, self::OPTION_ACTIVE_PLUGINS);
        $currentActivePlugins    = $this->getOptionValue($productionOptionsTable, self::OPTION_ACTIVE_PLUGINS);

        if (empty($activePluginsToPreserve)) {
            return $activePluginsToPreserve;
        }

 
        $wpstgActivePlugins = $this->safeMaybeUnserialize($currentActivePlugins);
 
        if (!is_array($wpstgActivePlugins)) {
 
            $this->insertOrUpdateOptionValue($productionOptionsTable, self::OPTION_ACTIVE_PLUGINS . '_bak', $currentActivePlugins);
            $this->errors[]     = 'The active plugins option in the database is corrupted in the current site. WP Staging has disabled all plugins during the restore process to avoid fatal errors. Nothing to worry about, the active plugins list is going to be replaced. However, the original value has been backed up in the options table with the name "active_plugins_bak".';
            $wpstgActivePlugins = [];
        }

        $wpstgActivePlugins = array_filter($wpstgActivePlugins, function ($pluginSlug) {
            return $this->isWpstgPluginSlug($pluginSlug);
        });

        $wpstgActivePlugins = serialize($wpstgActivePlugins);
        $this->updateOptionValue($tmpOptionsTable, self::OPTION_ACTIVE_PLUGINS, $wpstgActivePlugins);
        $this->updateOptionValue($productionOptionsTable, self::OPTION_ACTIVE_PLUGINS, $wpstgActivePlugins);

        return $activePluginsToPreserve;
    }






    public function getActiveSitewidePluginsToPreserve(): string
    {
        $tmpSiteMetaTable = $this->tmpPrefix . 'sitemeta';
        if (!$this->tableExists($tmpSiteMetaTable)) {
            return '';
        }

        $productionSiteMetaTable = $this->productionTablePrefix . 'sitemeta';
        $activePluginsToPreserve = $this->getNetworkOptionValue($tmpSiteMetaTable, self::OPTION_ACTIVE_SITEWIDE_PLUGINS);
        $currentActivePlugins    = $this->getNetworkOptionValue($productionSiteMetaTable, self::OPTION_ACTIVE_SITEWIDE_PLUGINS);

 
        $wpstgActivePlugins = $this->safeMaybeUnserialize($currentActivePlugins);
 
        if (!is_array($wpstgActivePlugins)) {
 
            $this->insertOrUpdateNetworkOptionValue($productionSiteMetaTable, self::OPTION_ACTIVE_SITEWIDE_PLUGINS . '_bak', $currentActivePlugins);
            $this->errors[]     = 'The active sitewide plugins option in the database is corrupted in the current site. WP Staging has disabled all sitewide plugins during the restore process to avoid fatal errors. Nothing to worry about, the active sitewide plugins option is going to be replaced anyway after the restore. However, the original value has been backed up in the sitemeta table with the name "active_sitewide_plugins_bak".';
            $wpstgActivePlugins = [];
        }

        $wpstgActivePlugins = array_filter($wpstgActivePlugins, function ($pluginSlug) {
            return $this->isWpstgPluginSlug($pluginSlug);
        }, ARRAY_FILTER_USE_KEY); 

        $wpstgActivePlugins = serialize($wpstgActivePlugins);
        $this->updateNetworkOptionValue($tmpSiteMetaTable, self::OPTION_ACTIVE_SITEWIDE_PLUGINS, $wpstgActivePlugins);
        $this->updateNetworkOptionValue($productionSiteMetaTable, self::OPTION_ACTIVE_SITEWIDE_PLUGINS, $wpstgActivePlugins);

        return $activePluginsToPreserve;
    }







    public function restorePreservedActivePlugins(string $activePlugins, string $activeWpstgPlugin, bool $isNetworkActivatedPlugin): bool
    {
        $productionOptionsTable = $this->productionTablePrefix . 'options';
        $preservedPlugins       = $this->safeMaybeUnserialize($activePlugins);

        if ($isNetworkActivatedPlugin) {
            if (!is_array($preservedPlugins)) {
                $this->addRejectedActivePluginsError($activePlugins);
                $preservedPlugins = [];
            }

            $preservedPlugins = array_values(array_filter($preservedPlugins, 'is_string'));

            return $this->updateOptionValue($productionOptionsTable, self::OPTION_ACTIVE_PLUGINS, serialize($preservedPlugins));
        }

 
        if (!is_array($preservedPlugins)) {
 
            $this->insertOrUpdateOptionValue($productionOptionsTable, self::OPTION_ACTIVE_PLUGINS . '_bak', $activePlugins);
            $this->errors[]   = 'The active plugins option in the database is corrupted after the renamed table. WP Staging has disabled all plugins during the restore process to avoid fatal errors. You can re-activate your plugins from the WordPress admin dashboard after the restore is complete. The original value has been backed up in the options table with the name "active_plugins_bak".';
            $preservedPlugins = [];
        }

        $preservedPlugins = array_filter($preservedPlugins, function ($pluginSlug) {
            return is_string($pluginSlug) && !$this->isWpstgPluginSlug($pluginSlug);
        });

        $preservedPlugins = array_merge($preservedPlugins, $this->getWpstgPluginsToReactivate($activeWpstgPlugin));
        sort($preservedPlugins);

        return $this->updateOptionValue($productionOptionsTable, self::OPTION_ACTIVE_PLUGINS, serialize($preservedPlugins));
    }





    protected function addRejectedActivePluginsError(string $originalValue)
    {
        if ($originalValue === '') {
            return;
        }

        $this->errors[] = 'The active plugins option in the backup is corrupted or contains a serialized PHP object. WP Staging has disabled all plugins during the restore process to avoid fatal errors. You can re-activate your plugins from the WordPress admin dashboard after the restore is complete.';
    }







    public function restorePreservedActiveSitewidePlugins(string $activeSitewidePlugins, string $activeWpstgPlugin, $time = null): bool
    {
        $preservedSitewidePlugins = $this->safeMaybeUnserialize($activeSitewidePlugins);
 
        if (!is_array($preservedSitewidePlugins)) {
 
            $this->insertOrUpdateNetworkOptionValue($this->productionTablePrefix . 'sitemeta', self::OPTION_ACTIVE_SITEWIDE_PLUGINS . '_bak', $activeSitewidePlugins);
            $this->errors[]           = 'The active sitewide plugins option in the database is corrupted after the renamed table. WP Staging has disabled all sitewide plugins during the restore process to avoid fatal errors. You can re-activate your sitewide plugins from the WordPress admin dashboard after the restore is complete. The original value has been backed up in the sitemeta table with the name "active_sitewide_plugins_bak".';
            $preservedSitewidePlugins = [];
        }

        $activeSitewidePlugins = array_filter($preservedSitewidePlugins, function ($pluginSlug) {

 
            if (strpos($pluginSlug, self::PLUGIN_BASE_SLUG) !== false) {
                return false;
            }

            return true;
        });

        if (!empty($activeWpstgPlugin)) {
            $activeSitewidePlugins[$activeWpstgPlugin] = empty($time) ? time() : $time;
        }

        return $this->updateNetworkOptionValue($this->productionTablePrefix . 'sitemeta', self::OPTION_ACTIVE_SITEWIDE_PLUGINS, serialize($activeSitewidePlugins));
    }

    public function preserveTmpOption(string $optionName): bool
    {
        $tmpOptionsTable = $this->tmpPrefix . 'options';
        if (!$this->tableExists($tmpOptionsTable)) {
            return false;
        }

        $optionsTable = $this->productionTablePrefix . 'options';
        $optionValue  = $this->getOptionValue($optionsTable, $optionName);
        if (empty($optionValue)) {
            return false;
        }

        if ($this->getOptionValue($tmpOptionsTable, $optionName)) {
            return $this->updateOptionValue($tmpOptionsTable, $optionName, $optionValue);
        }

        return $this->insertOptionValue($tmpOptionsTable, $optionName, $optionValue);
    }





    protected function isExcludedTable(string $tableName): bool
    {
        return in_array($tableName, $this->excludedTables);
    }





    protected function isTableToPreserve(string $tableName): bool
    {
        return in_array($tableName, $this->tablesToPreserve, true);
    }





    protected function isExistingDestinationSubsiteTable(string $tableName): bool
    {
        if (!$this->isRenamingForSubsite || strcasecmp($this->productionTablePrefix, $this->productionTableBasePrefix) !== 0) {
            return false;
        }

        if (preg_match('/^(\d+)_/', $tableName, $matches) !== 1) {
            return false;
        }

        $blogId = (int) $matches[1];
        return (string) $blogId === $matches[1] && isset($this->destinationSubsiteBlogIds[$blogId]);
    }




    protected function getTablesThatExistInBothExistingAndTempUnprefixed(): array
    {
        return array_intersect($this->tablesBeingRenamedUnprefixed['all'], $this->existingTablesUnprefixed['all']);
    }




    protected function getTablesThatExistInSiteButNotInTemp(): array
    {
        return array_diff($this->existingTablesUnprefixed['all'], $this->tablesBeingRenamedUnprefixed['all']);
    }




    protected function getTablesThatExistInTempButNotInSite(): array
    {
        return array_diff($this->tablesBeingRenamedUnprefixed['all'], $this->existingTablesUnprefixed['all']);
    }






    protected function renameTable(string $tableWithoutPrefix, int &$tablesRenamed)
    {
        $tableToRename     = $this->getTmpTable($tableWithoutPrefix);
        $tableAfterRenamed = $this->getCurrentSiteTable($tableWithoutPrefix);

        if (!$this->renameTableOrFail($tableToRename, $tableAfterRenamed)) {
            return;
        }

        $this->tablesRenamed++;
        $tablesRenamed++;

        if ($this->logEachRename && $this->logger instanceof Logger) {
            $this->logger->info("DB Rename: Renamed table {$tableToRename} to {$tableAfterRenamed}.");
        }
    }







    protected function renameTableOrFail(string $tableToRename, string $tableAfterRenamed): bool
    {
        $result = $this->tableService->getDatabase()->exec(sprintf(
            "RENAME TABLE `%s` TO `%s`;",
            $tableToRename,
            $tableAfterRenamed
        ));

        if ($result !== false) {
            return true;
        }

        $message = sprintf(
            'DB Rename: Unable to rename table %s to %s. Error: %s',
            $tableToRename,
            $tableAfterRenamed,
            $this->tableService->getLastWpdbError()
        );

        if (!$this->stopOnRenameFailure) {
            if ($this->logEachRename && $this->logger instanceof Logger) {
                $this->logger->warning($message);
            }

            return false;
        }

        if ($this->logger instanceof Logger) {
            $this->logger->critical($message);
        }

        throw new \RuntimeException($message);
    }





    protected function isAlreadyRenamed(string $tableWithoutPrefix): bool
    {
        return !$this->tableExists($this->getTmpTable($tableWithoutPrefix));
    }





    protected function getTmpTable(string $tableWithoutPrefix): string
    {
        $tmpTable  = $this->tmpPrefix . $tableWithoutPrefix;
        $shortName = $this->getTableShortName($tmpTable, $this->tmpPrefix);

        return $shortName === false ? $tmpTable : $shortName;
    }





    protected function tableExists(string $tableName): bool
    {
        $database = $this->tableService->getDatabase()->getWpdba()->getClient();
        $result   = $database->get_results(
            $database->prepare("SHOW TABLES LIKE %s", $database->esc_like($tableName)),
            ARRAY_A
        );

        return !empty($result);
    }






    protected function getOptionValue(string $tableName, string $optionName): string
    {
        $database = $this->tableService->getDatabase()->getWpdba()->getClient();
        $result   = $database->get_results(
            $database->prepare(
                "SELECT option_value FROM `{$tableName}` WHERE option_name LIKE %s",
                $database->esc_like($optionName)
            ),
            ARRAY_A
        );
        if (empty($result)) {
            return '';
        }

        return $result[0]['option_value'];
    }







    protected function updateOptionValue(string $tableName, string $optionName, string $optionValue): bool
    {
        $database = $this->tableService->getDatabase()->getWpdba()->getClient();

        return $database->query(
            $database->prepare(
                "UPDATE `{$tableName}` SET option_value = %s WHERE option_name LIKE %s",
                $optionValue,
                $database->esc_like($optionName)
            )
        );
    }








    protected function insertOptionValue(string $tableName, string $optionName, string $optionValue, bool $autoload = false): bool
    {
        $database = $this->tableService->getDatabase()->getWpdba()->getClient();

        return $database->query(
            $database->prepare(
                "INSERT INTO `{$tableName}` (option_name, option_value, autoload) VALUES (%s, %s, %s)",
                $optionName,
                $optionValue,
                $autoload ? 'yes' : 'no'
            )
        );
    }








    protected function insertOrUpdateOptionValue(string $tableName, string $optionName, string $optionValue, bool $autoload = false): bool
    {
        if ($this->getOptionValue($tableName, $optionName)) {
            return $this->updateOptionValue($tableName, $optionName, $optionValue);
        }

        return $this->insertOptionValue($tableName, $optionName, $optionValue, $autoload);
    }






    protected function getNetworkOptionValue(string $tableName, string $optionName): string
    {
        $database = $this->tableService->getDatabase()->getWpdba()->getClient();
        $result   = $database->get_results(
            $database->prepare(
                "SELECT meta_value FROM `{$tableName}` WHERE meta_key LIKE %s",
                $database->esc_like($optionName)
            ),
            ARRAY_A
        );
        if (empty($result)) {
            return '';
        }

        return $result[0]['meta_value'];
    }







    protected function insertNetworkOptionValue(string $tableName, string $optionName, string $optionValue): bool
    {
        $database = $this->tableService->getDatabase()->getWpdba()->getClient();

        return $database->query(
            $database->prepare(
                "INSERT INTO `{$tableName}` (meta_key, meta_value) VALUES (%s, %s)",
                $optionName,
                $optionValue
            )
        );
    }







    protected function updateNetworkOptionValue(string $tableName, string $optionName, string $optionValue): bool
    {
        $database = $this->tableService->getDatabase()->getWpdba()->getClient();

        return $database->query(
            $database->prepare(
                "UPDATE `{$tableName}` SET meta_value = %s WHERE meta_key LIKE %s",
                $optionValue,
                $database->esc_like($optionName)
            )
        );
    }







    protected function insertOrUpdateNetworkOptionValue(string $tableName, string $optionName, string $optionValue): bool
    {
        if ($this->getNetworkOptionValue($tableName, $optionName)) {
            return $this->updateNetworkOptionValue($tableName, $optionName, $optionValue);
        }

        return $this->insertNetworkOptionValue($tableName, $optionName, $optionValue);
    }




    protected function isThresholdReached(): bool
    {
        if (!$this->phpAdapter->isCallable($this->thresholdCallable)) {
            return $this->customThreshold(false);
        }

        $result = call_user_func($this->thresholdCallable);
        return $this->customThreshold($result);
    }





    private function customThreshold(bool $isThreshold): bool
    {
        return Hooks::applyFilters('wpstg.tests.tablesRenamingThreshold', $isThreshold);
    }






    private function renameQuery(string $tableToRename, string $tableAfterRenamed): bool
    {
        $result = $this->tableService->renameTable($tableToRename, $tableAfterRenamed);
        if ($result !== false) {
            return true;
        }

        if ($this->logEachRename && $this->logger instanceof Logger) {
 
            $error = $this->tableService->getLastWpdbError();
            $this->logger->warning(sprintf("DB Rename: Unable to rename table %s to %s. Error: %s", $tableToRename, $tableAfterRenamed, $error));
        }

        return false;
    }

    private function getCurrentSiteTable(string $tableWithoutPrefix): string
    {
        if ($tableWithoutPrefix !== 'users' && $tableWithoutPrefix !== 'usermeta') {
            return $this->productionTablePrefix . $tableWithoutPrefix;
        }

        if (!$this->isRenamingForSubsite) {
            return $this->productionTablePrefix . $tableWithoutPrefix;
        }

        return $this->productionTableBasePrefix . $tableWithoutPrefix;
    }








    private function isWpstgPluginSlug($pluginSlug): bool
    {
        return is_string($pluginSlug) && in_array(basename($pluginSlug), self::PLUGIN_FILE_NAMES, true);
    }







    private function getWpstgPluginsToReactivate(string $activeWpstgPlugin): array
    {
        $currentPlugins = $this->safeMaybeUnserialize(
            $this->getOptionValue($this->productionTablePrefix . 'options', self::OPTION_ACTIVE_PLUGINS)
        );

        $wpstgPlugins = [];
        if (is_array($currentPlugins)) {
            $wpstgPlugins = array_filter($currentPlugins, function ($pluginSlug) {
                return $this->isWpstgPluginSlug($pluginSlug);
            });
        }

        if ($activeWpstgPlugin !== '') {
            $wpstgPlugins[] = $activeWpstgPlugin;
        }

        return array_values(array_unique($wpstgPlugins));
    }
}
