<?php

namespace WPStaging\Framework\Database;

use WPStaging\Backup\Dto\Task\Restore\RenameDatabaseTaskDto;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Adapter\PhpAdapter;
use WPStaging\Framework\Facades\Hooks;

class TablesRenamer
{
    /** @var string */
    const OPTION_ACTIVE_PLUGINS = 'active_plugins';

    /** @var string */
    const OPTION_ACTIVE_SITEWIDE_PLUGINS = 'active_sitewide_plugins';

    /** @var string */
    const PLUGIN_BASE_SLUG = 'wp-staging';

    /** @var TableService */
    private $tableService;

    // eg: ['wp123456_options']
    /** @var array */
    protected $tablesBeingRenamed = [];

    // eg: ['options']
    /** @var array */
    protected $tablesBeingRenamedUnprefixed = [];

    // eg: ['wp_options']
    /** @var array */
    protected $existingTables = [];

    // eg: ['options']
    /** @var array */
    protected $existingTablesUnprefixed = [];

    /** @var array */
    protected $shortNamedTablesToRename = [];

    /** @var array */
    protected $shortNamedTablesToDrop = [];

    /** @var array */
    protected $excludedTables = [];

    /** @var array */
    protected $tablesToBeDropped = [];

    /** @var int Total tables to be renamed */
    protected $totalTables = 0;

    /**
     * @var int How many tables renamed in current request
     *          If no tables renamed, we can use it to throw an error.
     */
    protected $tablesRenamed = 0;

    /** @var int How many tables left to be dropped */
    protected $tablesRemainingToBeDropped = 0;

    /** @var string */
    protected $productionTablePrefix;

    /** @var string */
    protected $tmpPrefix;

    /** @var string */
    protected $dropPrefix;

    /** @var bool */
    protected $renameViews;

    /** @var bool */
    protected $logEachRename = false;

    /** @var Logger */
    protected $logger = null;

    /** @var PhpAdapter */
    protected $phpAdapter;

    /** @var callable|null */
    protected $thresholdCallable = null;

    /** @var int */
    protected $conflictingTablesRenamed = 0;

    /** @var int */
    protected $nonConflictingTablesRenamed = 0;

    /** @var bool */
    protected $isNonConflictingTablesRenamingTaskExecuted = false;

    public function __construct(TableService $tableService, PhpAdapter $phpAdapter)
    {
        $this->tableService = $tableService;
        $this->phpAdapter   = $phpAdapter;
    }

    /**
     * @param string $productionTablePrefix
     * @return TablesRenamer
     */
    public function setProductionTablePrefix(string $productionTablePrefix): TablesRenamer
    {
        $this->productionTablePrefix = $productionTablePrefix;
        return $this;
    }

    /**
     * @param string $tmpPrefix
     * @return TablesRenamer
     */
    public function setTmpPrefix(string $tmpPrefix): TablesRenamer
    {
        $this->tmpPrefix = $tmpPrefix;
        return $this;
    }

    /**
     * @param string $dropPrefix
     * @return TablesRenamer
     */
    public function setDropPrefix(string $dropPrefix): TablesRenamer
    {
        $this->dropPrefix = $dropPrefix;
        return $this;
    }

    /**
     * @param bool $renameViews
     * @return TablesRenamer
     */
    public function setRenameViews(bool $renameViews): TablesRenamer
    {
        $this->renameViews = $renameViews;
        return $this;
    }

    /**
     * @param bool $logEachRename
     * @return TablesRenamer
     */
    public function setLogEachRename(bool $logEachRename): TablesRenamer
    {
        $this->logEachRename = $logEachRename;
        return $this;
    }

    /**
     * @param Logger $logger
     * @return TablesRenamer
     */
    public function setLogger(Logger $logger): TablesRenamer
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @param callable|null $thresholdCallable
     * @return TablesRenamer
     */
    public function setThresholdCallable($thresholdCallable): TablesRenamer
    {
        $this->thresholdCallable = $thresholdCallable;
        return $this;
    }

    /**
     * @param array $shortNamedTablesToRename
     * @return TablesRenamer
     */
    public function setShortNamedTablesToRename(array $shortNamedTablesToRename): TablesRenamer
    {
        $this->shortNamedTablesToRename = $shortNamedTablesToRename;
        return $this;
    }

    /**
     * @param array $shortNamedTablesToDrop
     * @return TablesRenamer
     */
    public function setShortNamedTablesToDrop(array $shortNamedTablesToDrop): TablesRenamer
    {
        $this->shortNamedTablesToDrop = $shortNamedTablesToDrop;
        return $this;
    }

    /**
     * @param array $excludedTables
     * @return TablesRenamer
     */
    public function setExcludedTables(array $excludedTables): TablesRenamer
    {
        $this->excludedTables = $excludedTables;
        return $this;
    }

    /** @return int */
    public function getRenamedTables(): int
    {
        return $this->tablesRenamed;
    }

    /** @return int */
    public function getTotalTables(): int
    {
        return $this->totalTables;
    }

    /** @return int */
    public function getTablesRemainingToBeDropped(): int
    {
        return $this->tablesRemainingToBeDropped;
    }

    /** @return array */
    public function getViewsToBeRenamed(): array
    {
        return $this->tablesBeingRenamedUnprefixed['views'];
    }

    /**
     * @return int
     */
    public function getConflictingTablesRenamed(): int
    {
        return $this->conflictingTablesRenamed;
    }

    /**
     * @return int
     */
    public function getNonConflictingTablesRenamed(): int
    {
        return $this->nonConflictingTablesRenamed;
    }

    /**
     * @return bool
     */
    public function getIsNonConflictingTablesRenamingTaskExecuted(): bool
    {
        return $this->isNonConflictingTablesRenamingTaskExecuted;
    }

    /**
     * @return RenameDatabaseTaskDto
     */
    public function setupRenamer(): RenameDatabaseTaskDto
    {
        $taskDto = new RenameDatabaseTaskDto();
        $taskDto->tablesBeingRenamed = $this->tableService->findTableNamesStartWith($this->tmpPrefix) ?: [];
        $taskDto->viewsBeingRenamed  = [];
        if ($this->renameViews) {
            $taskDto->viewsBeingRenamed = $this->tableService->findViewsNamesStartWith($this->tmpPrefix) ?: [];
        }

        $taskDto->existingTables = $this->tableService->findTableNamesStartWith($this->productionTablePrefix) ?: [];
        $taskDto->existingViews  = [];
        if ($this->renameViews) {
            $taskDto->existingViews  = $this->tableService->findViewsNamesStartWith($this->productionTablePrefix) ?: [];
        }

        $taskDto->conflictingTablesRenamed    = 0;
        $taskDto->nonConflictingTablesRenamed = 0;
        $this->setTaskDto($taskDto);

        return $taskDto;
    }

    /**
     * @param RenameDatabaseTaskDto $taskDto
     * @return void
     */
    public function setTaskDto(RenameDatabaseTaskDto $taskDto)
    {
        $this->tablesBeingRenamed           = [];
        $this->tablesBeingRenamed['tables'] = $taskDto->tablesBeingRenamed ?: [];
        $this->tablesBeingRenamed['views']  = $taskDto->viewsBeingRenamed ?: [];
        $this->tablesBeingRenamed['all']    = array_merge($this->tablesBeingRenamed['tables'], $this->tablesBeingRenamed['views']);

        $this->existingTables           = [];
        $this->existingTables['tables'] = $taskDto->existingTables ?: [];
        $this->existingTables['views']  = $taskDto->existingViews ?: [];
        $this->existingTables['all']    = array_merge($this->existingTables['tables'], $this->existingTables['views']);

        $this->totalTables = count($this->tablesBeingRenamed['tables']);
        $tmpDatabasePrefix = $this->tmpPrefix;

        foreach ($this->tablesBeingRenamed as $viewsOrTables => $tableName) {
            $this->tablesBeingRenamedUnprefixed[$viewsOrTables] = array_map(function ($tableName) use ($tmpDatabasePrefix) {
                $tableName = $this->getFullNameTableFromShortName($tableName, $tmpDatabasePrefix);
                return substr($tableName, strlen($tmpDatabasePrefix));
            }, $this->tablesBeingRenamed[$viewsOrTables]);
        }

        $productionTablePrefix = $this->productionTablePrefix;

        foreach ($this->existingTables as $viewsOrTables => $tableName) {
            $this->existingTablesUnprefixed[$viewsOrTables] = array_map(function ($tableName) use ($productionTablePrefix) {
                return substr($tableName, strlen($productionTablePrefix));
            }, $this->existingTables[$viewsOrTables]);
        }

        $this->conflictingTablesRenamed    = (int)$taskDto->conflictingTablesRenamed;
        $this->nonConflictingTablesRenamed = (int)$taskDto->nonConflictingTablesRenamed;
    }

    /**
     * @param string $table
     * @param string $prefix
     *
     * @return string
     */
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

    /**
     * @param string $table
     * @param string $prefix
     * @return false|string
     */
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

    /**
     * Return true if all conflicting tables renamed, false otherwise
     * @return bool
     */
    public function renameConflictingTables(): bool
    {
        $conflictingTablesWithoutPrefix = array_values($this->getTablesThatExistInBothExistingAndTempUnprefixed());
        // Early bail: if no tables to rename
        if (empty($conflictingTablesWithoutPrefix)) {
            return true;
        }

        // Early bail: if all tables renamed
        if (count($conflictingTablesWithoutPrefix) <= $this->conflictingTablesRenamed) {
            return true;
        }

        $this->tableService->getDatabase()->exec('START TRANSACTION;');
        for ($i = $this->conflictingTablesRenamed; $i < count($conflictingTablesWithoutPrefix); $i++) {
            $conflictingTableWithoutPrefix = $conflictingTablesWithoutPrefix[$i];
            if ($this->isExcludedTable($conflictingTableWithoutPrefix)) {
                $this->tablesRenamed++;
                $this->conflictingTablesRenamed++;
                continue;
            }

            $currentTable = $this->productionTablePrefix . $conflictingTableWithoutPrefix;
            $tableToDrop =  $this->getTableShortName($currentTable, $this->dropPrefix);
            if ($tableToDrop === false) {
                $tableToDrop = $this->dropPrefix . $conflictingTableWithoutPrefix;
            }

            // Prefix existing table with toDrop prefix
            $result = $this->tableService->getDatabase()->exec(sprintf(
                "RENAME TABLE `%s` TO `%s`;",
                $currentTable,
                $tableToDrop
            ));

            if ($result === false && ($this->logEachRename && $this->logger instanceof Logger)) {
                /** @var \wpdb */
                $wpdb  = $this->tableService->getDatabase()->getWpdba()->getClient();
                $error = $wpdb->last_error;
                $this->logger->warning("DB Rename: Unable to rename table {$currentTable} to {$tableToDrop}. Error: " . $error);
            }

            $this->renameTable($conflictingTableWithoutPrefix, $this->conflictingTablesRenamed);

            if ($this->isThresholdReached()) {
                $this->tableService->getDatabase()->exec('COMMIT;');
                return false;
            }
        }

        $this->tableService->getDatabase()->exec('COMMIT;');

        return true;
    }

    /**
     * Return true if all non-conflicting tables renamed, false otherwise
     * @return bool
     */
    public function renameNonConflictingTables(): bool
    {
        $nonConflictingTables = array_values($this->getTablesThatExistInTempButNotInSite());
        // Early bail: if no tables to rename
        if (empty($nonConflictingTables)) {
            return true;
        }

        // Early bail: if all tables renamed
        if (count($nonConflictingTables) <= $this->nonConflictingTablesRenamed) {
            return true;
        }

        $this->tableService->getDatabase()->exec('START TRANSACTION;');
        for ($i = $this->nonConflictingTablesRenamed; $i < count($nonConflictingTables); $i++) {
            $nonConflictingTable = $nonConflictingTables[$i];
            if ($this->isExcludedTable($nonConflictingTable)) {
                $this->tablesRenamed++;
                $this->nonConflictingTablesRenamed++;
                continue;
            }

            $this->renameTable($nonConflictingTable, $this->nonConflictingTablesRenamed);
            $this->isNonConflictingTablesRenamingTaskExecuted = true;

            if ($this->isThresholdReached()) {
                $this->tableService->getDatabase()->exec('COMMIT;');
                return false;
            }
        }

        $this->tableService->getDatabase()->exec('COMMIT;');

        return true;
    }

    /**
     * @return bool
     */
    public function cleanTemporaryBackupTables(): bool
    {
        // Early bail if tables cleaned already
        if ($this->nonConflictingTablesRenamed !== 0 || $this->conflictingTablesRenamed !== 0) {
            return true;
        }

        $this->tablesToBeDropped = $this->tableService->findTableNamesStartWith($this->dropPrefix) ?: [];
        $this->tablesRemainingToBeDropped = count($this->tablesToBeDropped);

        $this->tableService->getDatabase()->exec('SET autocommit=0;');
        $this->tableService->getDatabase()->exec('SET FOREIGN_KEY_CHECKS=0;');
        $this->tableService->getDatabase()->exec('START TRANSACTION;');
        foreach ($this->tablesToBeDropped as $table) {
            $result = $this->tableService->getDatabase()->exec(sprintf(
                "DROP TABLE `%s`;",
                $table
            ));

            // return false if drop table failed to try again
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

    /**
     * @return void
     */
    public function renameTablesToDrop()
    {
        foreach ($this->getTablesThatExistInSiteButNotInTemp() as $table) {
            $fullTableName = $this->productionTablePrefix . $table;
            $tableToDrop = $this->getTableShortName($fullTableName, $this->dropPrefix);
            if ($tableToDrop === false) {
                $tableToDrop = $this->dropPrefix . $table;
            }

            $this->tableService->getDatabase()->exec(sprintf(
                "RENAME TABLE `%s` TO `%s`;",
                $fullTableName,
                $tableToDrop
            ));
        }
    }

    /**
     * Get active plugins from tmp options table
     * Update tmp options table with active plugins from production options table to reduce fatal error during renaming process
     * @return string
     */
    public function getActivePluginsToPreserve(): string
    {
        $tmpOptionsTable = $this->tmpPrefix . 'options';
        if (!$this->tableExists($tmpOptionsTable)) {
            return '';
        }

        $productionOptionsTable  = $this->productionTablePrefix . 'options';
        $activePluginsToPreserve = $this->getOptionValue($tmpOptionsTable, self::OPTION_ACTIVE_PLUGINS);
        $currentActivePlugins    = $this->getOptionValue($productionOptionsTable, self::OPTION_ACTIVE_PLUGINS);

        // keep only active plugins that are wp staging plugins
        $currentActivePlugins = maybe_unserialize($currentActivePlugins);
        $currentActivePlugins = array_filter($currentActivePlugins, function ($pluginSlug) {
            return strpos($pluginSlug, self::PLUGIN_BASE_SLUG) === 0;
        });

        $currentActivePlugins = serialize($currentActivePlugins);
        $this->updateOptionValue($tmpOptionsTable, self::OPTION_ACTIVE_PLUGINS, $currentActivePlugins);
        $this->updateOptionValue($productionOptionsTable, self::OPTION_ACTIVE_PLUGINS, $currentActivePlugins);

        return $activePluginsToPreserve;
    }

    /**
     * Get active sitewide plugins from tmp sitemeta table
     * Update tmp sitemeta table with active plugins from production options table to reduce fatal error during renaming process
     * @return string
     */
    public function getActiveSitewidePluginsToPreserve(): string
    {
        $tmpSiteMetaTable = $this->tmpPrefix . 'sitemeta';
        if (!$this->tableExists($tmpSiteMetaTable)) {
            return '';
        }

        $productionSiteMetaTable = $this->productionTablePrefix . 'sitemeta';
        $activePluginsToPreserve = $this->getNetworkOptionValue($tmpSiteMetaTable, self::OPTION_ACTIVE_SITEWIDE_PLUGINS);
        $currentActivePlugins    = $this->getNetworkOptionValue($productionSiteMetaTable, self::OPTION_ACTIVE_SITEWIDE_PLUGINS);

        // keep only active plugins that are wp staging plugins
        $currentActivePlugins = maybe_unserialize($currentActivePlugins);
        $currentActivePlugins = array_filter($currentActivePlugins, function ($pluginSlug) {
            return strpos($pluginSlug, self::PLUGIN_BASE_SLUG) === 0;
        });

        $currentActivePlugins = serialize($currentActivePlugins);
        $this->updateNetworkOptionValue($tmpSiteMetaTable, self::OPTION_ACTIVE_SITEWIDE_PLUGINS, $currentActivePlugins);
        $this->updateNetworkOptionValue($productionSiteMetaTable, self::OPTION_ACTIVE_SITEWIDE_PLUGINS, $currentActivePlugins);

        return $activePluginsToPreserve;
    }

    /**
     * @param string $activePlugins
     * @param string $activeWpstgPlugin
     * @param bool   $isNetworkActivatedPlugin
     * @return bool
     */
    public function restorePreservedActivePlugins(string $activePlugins, string $activeWpstgPlugin, bool $isNetworkActivatedPlugin): bool
    {
        $productionOptionsTable = $this->productionTablePrefix . 'options';
        if ($isNetworkActivatedPlugin) {
            return $this->updateOptionValue($productionOptionsTable, self::OPTION_ACTIVE_PLUGINS, $activePlugins);
        }

        $activePlugins = maybe_unserialize($activePlugins);
        $activePlugins = array_filter($activePlugins, function ($pluginSlug) {

            // Disable all wp staging plugins, we will reactive current active wp staging plugin later
            if (strpos($pluginSlug, self::PLUGIN_BASE_SLUG) !== false) {
                return false;
            }

            return true;
        });

        // reactivating current active wp staging plugin
        $activePlugins[] = $activeWpstgPlugin;
        sort($activePlugins);

        $activePlugins = serialize($activePlugins);

        return $this->updateOptionValue($productionOptionsTable, self::OPTION_ACTIVE_PLUGINS, $activePlugins);
    }

    /**
     * @param string $activeSitewidePlugins
     * @param string $activeWpstgPlugin
     * @param int|null $time timestamp when the plugin was activated
     * @return bool
     */
    public function restorePreservedActiveSitewidePlugins(string $activeSitewidePlugins, string $activeWpstgPlugin, $time = null): bool
    {
        $activeSitewidePlugins = maybe_unserialize($activeSitewidePlugins);
        $activeSitewidePlugins = array_filter($activeSitewidePlugins, function ($pluginSlug) {

            // Disable all wp staging plugins, we will reactive current active wp staging plugin later
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

    /**
     * @param string $tableName
     * @return bool
     */
    protected function isExcludedTable(string $tableName): bool
    {
        return in_array($tableName, $this->excludedTables);
    }

    /**
     * @return array
     */
    protected function getTablesThatExistInBothExistingAndTempUnprefixed(): array
    {
        return array_intersect($this->tablesBeingRenamedUnprefixed['all'], $this->existingTablesUnprefixed['all']);
    }

    /**
     * @return array
     */
    protected function getTablesThatExistInSiteButNotInTemp(): array
    {
        return array_diff($this->existingTablesUnprefixed['all'], $this->tablesBeingRenamedUnprefixed['all']);
    }

    /**
     * @return array
     */
    protected function getTablesThatExistInTempButNotInSite(): array
    {
        return array_diff($this->tablesBeingRenamedUnprefixed['all'], $this->existingTablesUnprefixed['all']);
    }

    /**
     * @param string $tableWithoutPrefix
     * @param int $tablesRenamed
     * @return void
     */
    protected function renameTable(string $tableWithoutPrefix, int &$tablesRenamed)
    {
        $tmpDatabasePrefix = $this->tmpPrefix;
        $tableToRename     = $tmpDatabasePrefix . $tableWithoutPrefix;
        $tmpName           = $this->getTableShortName($tableToRename, $tmpDatabasePrefix);
        $tableAfterRenamed = $this->productionTablePrefix . $tableWithoutPrefix;
        if ($tmpName !== false) {
            $tableToRename = $tmpName;
        }

        // Rename restored table to existing table
        $database = $this->tableService->getDatabase();
        $result = $database->exec(sprintf(
            "RENAME TABLE `%s` TO `%s`;",
            $tableToRename,
            $tableAfterRenamed
        ));

        if ($result !== false) {
            $this->tablesRenamed++;
            $tablesRenamed++;
            if ($this->logEachRename && $this->logger instanceof Logger) {
                $this->logger->info("DB Rename: Renamed table {$tableToRename} to {$tableAfterRenamed}.");
            }

            return;
        }

        if ($this->logEachRename && $this->logger instanceof Logger) {
            /** @var \wpdb */
            $wpdb  = $database->getWpdba()->getClient();
            $error = $wpdb->last_error;
            $this->logger->warning("DB Rename: Unable to rename table {$tableToRename} to {$tableAfterRenamed}. Error: " . $error);
        }
    }

    /**
     * @param string $tableName
     * @return bool
     */
    protected function tableExists(string $tableName): bool
    {
        $database  = $this->tableService->getDatabase()->getWpdba()->getClient();
        $tableName = $database->esc_like($tableName);
        $sql       = "SHOW TABLES LIKE '{$tableName}'";
        $result    = $database->get_results($sql, ARRAY_A);

        return !empty($result);
    }

    /**
     * @param string $tableName
     * @param string $optionName
     * @return string
     */
    protected function getOptionValue(string $tableName, string $optionName): string
    {
        $database   = $this->tableService->getDatabase()->getWpdba()->getClient();
        $optionName = $database->esc_like($optionName);
        $sql        = "SELECT option_value FROM {$tableName} WHERE option_name LIKE '{$optionName}'";
        $result     = $database->get_results($sql, ARRAY_A);
        if (empty($result)) {
            return '';
        }

        return $result[0]['option_value'];
    }

    /**
     * @param string $tableName
     * @param string $optionName
     * @param string $optionValue
     * @return bool
     */
    protected function updateOptionValue(string $tableName, string $optionName, string $optionValue): bool
    {
        $database   = $this->tableService->getDatabase()->getWpdba()->getClient();
        $optionName = $database->esc_like($optionName);
        $sql        = "UPDATE {$tableName} SET option_value = '{$optionValue}' WHERE option_name LIKE '{$optionName}'";

        return $database->query($sql);
    }

    /**
     * @param string $tableName
     * @param string $optionName
     * @return string
     */
    protected function getNetworkOptionValue(string $tableName, string $optionName): string
    {
        $database   = $this->tableService->getDatabase()->getWpdba()->getClient();
        $optionName = $database->esc_like($optionName);
        $sql        = "SELECT meta_value FROM {$tableName} WHERE meta_key LIKE '{$optionName}'";
        $result     = $database->get_results($sql, ARRAY_A);
        if (empty($result)) {
            return '';
        }

        return $result[0]['meta_value'];
    }

    /**
     * @param string $tableName
     * @param string $optionName
     * @param string $optionValue
     * @return bool
     */
    protected function updateNetworkOptionValue(string $tableName, string $optionName, string $optionValue): bool
    {
        $database   = $this->tableService->getDatabase()->getWpdba()->getClient();
        $optionName = $database->esc_like($optionName);
        $sql        = "UPDATE {$tableName} SET meta_value = '{$optionValue}' WHERE meta_key LIKE '{$optionName}'";

        return $database->query($sql);
    }

    /**
     * @return bool
     */
    protected function isThresholdReached(): bool
    {
        if (!$this->phpAdapter->isCallable($this->thresholdCallable)) {
            return $this->customThreshold(false);
        }

        $result = call_user_func($this->thresholdCallable);
        return $this->customThreshold($result);
    }

    /**
     * @param bool $isThreshold
     * @return bool
     */
    private function customThreshold(bool $isThreshold): bool
    {
        return Hooks::applyFilters('wpstg.tests.tablesRenamingThreshold', $isThreshold);
    }
}
