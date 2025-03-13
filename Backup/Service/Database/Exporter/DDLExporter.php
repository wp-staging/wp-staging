<?php
namespace WPStaging\Backup\Service\Database\Exporter;
use Exception;
use WPStaging\Backup\BackupHeader;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Database\TableService;
class DDLExporter extends AbstractExporter
{
    protected $tableService;
    protected $viewDDLOrder;
    protected $tables = [];
    protected $views = [];
    protected $excludedTables = [];
    protected $nonWpTables = [];

    public function __construct(Database $database, TableService $tableService, ViewDDLOrder $viewDDLOrder)
    {
        parent::__construct($database);
        $this->tableService = $tableService;
        $this->viewDDLOrder = $viewDDLOrder;
    }

    public function backupDDLTablesAndViews()
    {
        $this->file->fwrite($this->getHeader());
        $this->client->query("SET SESSION sql_mode = ''");
        $this->tables = $this->tableService->findTableNamesStartWith($this->getPrefix());
        $this->views  = $this->tableService->findViewsNamesStartWith($this->getPrefix());
        $this->filterOtherSubsitesTables();
        $tablesThenViews   = array_merge($this->tables, $this->views);
        $isMultisiteBackup = is_multisite() && !$this->isNetworkSiteBackup;
        foreach ($tablesThenViews as $tableOrView) {
            if ($this->isView($tableOrView, $this->views)) {
                $this->viewDDLOrder->enqueueViewToBeWritten($tableOrView, $this->tableService->getCreateViewQuery($tableOrView));
                continue;
            }
            if (in_array($tableOrView, $this->excludedTables)) {
                continue;
            }
            $this->writeQueryCreateTable($tableOrView, true, $isMultisiteBackup);
        }
        $this->addUsersTablesForSubsite();
        $this->writeQueryNonWpTables();
        foreach ($this->viewDDLOrder->tryGetOrderedViews() as $viewName => $query) {
            $this->writeQueryCreateViews($viewName, $query, $isMultisiteBackup);
        }
    }

    public function getTables()
    {
        return $this->tables;
    }

    public function getNonWPTables()
    {
        return $this->nonWpTables;
    }

    protected function addUsersTablesForSubsite()
    {
        }

    protected function filterOtherSubsitesTables()
    {
        }

    protected function writeQueryNonWpTables()
    {
        $nonPrefixedTables = $this->getNonPrefixedTablesFromFilter();
        foreach ($nonPrefixedTables as $table) {
            if (in_array($table, $this->tables)) {
                continue;
            }
            if (in_array($table, $this->views)) {
                continue;
            }
            $this->addNonWpTable($table);
        }
    }

    protected function getNonPrefixedTablesFromFilter()
    {
        $nonPrefixedTables = apply_filters('wpstg.backup.tables.non-prefixed', []);
        return is_array($nonPrefixedTables) ? $nonPrefixedTables : [];
    }

    protected function addNonWpTable($table)
    {
        $isTableAdded = $this->writeQueryCreateTable($table, false);
        if ($isTableAdded !== false) {
            $this->nonWpTables[] = $table;
        }
    }

    protected function writeQueryCreateTable(string $tableName, bool $isWpTable = true, bool $isBaseTable = false)
    {
        $newTableName = $tableName;
        if ($isWpTable && !$isBaseTable) {
            $newTableName = $this->getPrefixedTableName($tableName);
        } elseif ($isWpTable && $isBaseTable) {
            $newTableName = $this->getPrefixedBaseTableName($tableName);
        }
        $createTableQuery = $this->tableService->getCreateTableQuery($tableName);
        if ($createTableQuery === '' && !$isWpTable) {
            return false;
        }
        $dropTable = "DROP TABLE IF EXISTS `{$newTableName}`;\n";
        $this->file->fwrite($dropTable);
        if ($isWpTable) {
            $createTableQuery = str_replace($tableName, $newTableName, $createTableQuery);
        }
        $createTableQuery = $this->tableService->replaceTableConstraints($createTableQuery);
        $createTableQuery = $this->tableService->replaceTableOptions($createTableQuery);
        $createTableQuery = rtrim($createTableQuery, ';');
        $this->file->fwrite(preg_replace('#\s+#', ' ', $createTableQuery));
        $this->file->fwrite(";\n");
        return $newTableName;
    }

    private function isView($tableName, array $views)
    {
        return in_array($tableName, $views, true);
    }

    protected function writeQueryCreateViews(string $tableName, string $createViewQuery, bool $isBaseView)
    {
        $prefixedTableName = $this->getPrefixedTableName($tableName);
        if ($isBaseView) {
            $prefixedTableName = $this->getPrefixedBaseTableName($tableName);
        }
        $dropView = "\nDROP VIEW IF EXISTS `{$prefixedTableName}`;\n";
        $this->file->fwrite($dropView);
        $createViewQuery = $this->replaceViewIdentifiers($createViewQuery);
        $createViewQuery = str_replace($tableName, $prefixedTableName, $createViewQuery);
        $createViewQuery = $this->replaceViewOptions($createViewQuery);
        $this->file->fwrite($createViewQuery);
        $this->file->fwrite(";\n");
    }

    protected function getPrefix(): string
    {
        return $this->database->getPrefix();
    }

    protected function replaceViewIdentifiers($sql)
    {
        foreach (array_merge($this->tables, $this->views) as $tableName) {
            $newTableName = $this->replacePrefix($tableName, '{WPSTG_TMP_PREFIX}');
            $sql          = str_ireplace("`$tableName`", "`$newTableName`", $sql);
        }
        return $sql;
    }

    private function replaceViewOptions($input)
    {
        return preg_replace('/CREATE(.+?)VIEW/i', 'CREATE VIEW', $input);
    }

    protected function getHeader()
    {
        return sprintf(
            BackupHeader::WPSTG_SQL_BACKUP_DUMP_HEADER .
            "-- https://wp-staging.com/\n" .
            "--\n" .
            "-- Host: %s\n" .
            "-- Database: %s\n" .
            "-- Class: %s\n" .
            "--\n",
            $this->getWpDb()->dbhost,
            $this->getWpDb()->dbname,
            get_class($this)
        );
    }
}
