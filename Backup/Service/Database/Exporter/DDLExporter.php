<?php

namespace WPStaging\Backup\Service\Database\Exporter;

use Exception;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Database\TableService;

class DDLExporter extends AbstractExporter
{
    /** @var TableService */
    protected $tableService;

    /** @var ViewDDLOrder */
    protected $viewDDLOrder;

    /** @var array */
    protected $tables         = [];

    /** @var array */
    protected $views          = [];

    /** @var array */
    protected $excludedTables = [];

    /** @var array */
    protected $nonWpTables    = [];

    public function __construct(Database $database, TableService $tableService, ViewDDLOrder $viewDDLOrder)
    {
        parent::__construct($database);
        $this->tableService = $tableService;
        $this->viewDDLOrder = $viewDDLOrder;
    }

    /**
     * @throws Exception
     */
    public function backupDDLTablesAndViews()
    {
        $this->file->fwrite($this->getHeader());

        $this->client->query("SET SESSION sql_mode = ''");

        $this->tables = $this->tableService->findTableNamesStartWith($this->getPrefix());
        $this->views  = $this->tableService->findViewsNamesStartWith($this->getPrefix());

        $this->filterOtherSubsitesTables();

        // Add views to bottom of the array to make sure they can be created. Views are based on tables. So tables need to be created before views
        $tablesThenViews = array_merge($this->tables, $this->views);

        foreach ($tablesThenViews as $tableOrView) {
            // Backup views
            if ($this->isView($tableOrView, $this->views)) {
                $this->viewDDLOrder->enqueueViewToBeWritten($tableOrView, $this->tableService->getCreateViewQuery($tableOrView));
                continue;
            }

            if (in_array($tableOrView, $this->excludedTables)) {
                continue;
            }

            $this->writeQueryCreateTable($tableOrView);
        }

        $this->addUsersTablesForSubsite();

        $this->writeQueryNonWpTables();

        foreach ($this->viewDDLOrder->tryGetOrderedViews() as $viewName => $query) {
            $this->writeQueryCreateViews($viewName, $query);
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

    /**
     * @return void
     */
    protected function addUsersTablesForSubsite()
    {
        // no-op, used in Pro version
    }

    /**
     * @return void
     */
    protected function filterOtherSubsitesTables()
    {
        // no-op, used in Pro version
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

    /**
     * @param string $tableName
     * @param bool $isWpTable
     * @param bool $isBaseTable
     * @return string|false
     */
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

        $createTableQuery = $this->replaceTableConstraints($createTableQuery);
        $createTableQuery = $this->replaceTableOptions($createTableQuery);
        $this->file->fwrite(preg_replace('#\s+#', ' ', $createTableQuery));

        $this->file->fwrite(";\n");

        return $newTableName;
    }

    /**
     * Replace Constraints with empty string to remove them
     *
     * @param string $input SQL statement
     *
     * @return string
     */
    private function replaceTableConstraints($input)
    {
        $pattern = [
            '/\s+CONSTRAINT(.+)REFERENCES(.+),/i',
            '/,\s+CONSTRAINT(.+)REFERENCES(.+)/i',
        ];

        return preg_replace($pattern, '', $input);
    }

    /**
     * @param string $input SQL statement
     *
     * @return string
     */
    private function replaceTableOptions($input)
    {
        $search = [
            'TYPE=InnoDB',
            'TYPE=MyISAM',
            'ENGINE=Aria',
            'TRANSACTIONAL=0',
            'TRANSACTIONAL=1',
            'PAGE_CHECKSUM=0',
            'PAGE_CHECKSUM=1',
            'TABLE_CHECKSUM=0',
            'TABLE_CHECKSUM=1',
            'ROW_FORMAT=PAGE',
            'ROW_FORMAT=FIXED',
            'ROW_FORMAT=DYNAMIC',
        ];
        $replace = [
            'ENGINE=InnoDB',
            'ENGINE=MyISAM',
            'ENGINE=MyISAM',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ];

        return str_ireplace($search, $replace, $input);
    }

    /**
     * @param       $tableName
     * @param array $views
     *
     * @return bool
     */
    private function isView($tableName, array $views)
    {
        return in_array($tableName, $views, true);
    }

    /**
     * @param $tableName
     * @param $createViewQuery
     */
    protected function writeQueryCreateViews($tableName, $createViewQuery)
    {
        $prefixedTableName = $this->getPrefixedTableName($tableName);

        $dropView = "\nDROP VIEW IF EXISTS `{$prefixedTableName}`;\n";
        $this->file->fwrite($dropView);

        $createViewQuery = $this->replaceViewIdentifiers($createViewQuery);
        $createViewQuery = str_replace($tableName, $prefixedTableName, $createViewQuery);
        $createViewQuery = $this->replaceViewOptions($createViewQuery);
        $this->file->fwrite($createViewQuery);

        $this->file->fwrite(";\n");
    }

    /**
     * @return string
     */
    protected function getPrefix(): string
    {
        return $this->database->getPrefix();
    }

    /**
     * Replace view identifiers in SQL query
     *
     * @TODO The original method says "View" but actually replaces from base "Tables". Is this expected?
     *
     * @param string $sql table create query
     *
     * @return string
     */
    private function replaceViewIdentifiers($sql)
    {
        foreach (array_merge($this->tables, $this->views) as $tableName) {
            $newTableName = $this->replacePrefix($tableName, '{WPSTG_TMP_PREFIX}');
            $sql          = str_ireplace("`$tableName`", "`$newTableName`", $sql);
        }

        return $sql;
    }

    /**
     *
     * @param string $input Table value
     *
     * @return string
     */
    private function replaceViewOptions($input)
    {
        return preg_replace('/CREATE(.+?)VIEW/i', 'CREATE VIEW', $input);
    }

    /**
     * Get header for dump file
     *
     * @return string
     */
    protected function getHeader()
    {
        return sprintf(
            "-- WP Staging SQL Backup Dump\n" .
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
