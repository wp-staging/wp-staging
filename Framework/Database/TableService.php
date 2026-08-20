<?php

 
 

namespace WPStaging\Framework\Database;

use RuntimeException;
use UnexpectedValueException;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Collection\Collection;
use WPStaging\Framework\Utils\Strings;

class TableService
{
 
    private $database;

 
    private $client;

 
    private $shouldStop;

 
    private $errors = [];

 
    private $strHelper;

    private $isSqlLite = false;




    public function __construct($database = null)
    {
        $this->database  = $database ?: new Database();
        $this->client    = $this->database->getClient();
        $this->strHelper = new Strings();

        $this->isSqlLite = property_exists($this->client, 'isSQLite');
    }




    public function getErrors()
    {
        return $this->errors;
    }




    public function getShouldStop()
    {
        return $this->shouldStop;
    }





    public function setShouldStop($shouldStop = null)
    {
        $this->shouldStop = $shouldStop;
        return $this;
    }





    public function tableExists(string $tableName): bool
    {
        $wpdb   = $this->database->getWpdb();
        $tables = $wpdb->get_results(
            $wpdb->prepare('SHOW TABLES LIKE %s;', $wpdb->esc_like($tableName)),
            ARRAY_A
        );

        if (!$tables) {
            return false;
        }

        return true;
    }






    public function findAllTableStatus()
    {
        $tables = $this->database->find("SHOW TABLE STATUS");
        if (!$tables) {
            return null;
        }

        $collection = new Collection(TableDto::class);
        foreach ($tables as $table) {
            $collection->attach((new TableDto())->hydrate((array) $table));
        }

        return $collection;
    }







    public function findTableStatusStartsWith($prefix = null)
    {
 
        $tables = $this->database->find("SHOW TABLE STATUS LIKE '{$this->database->escapeSqlPrefixForLIKE($prefix)}%'");
        if (!$tables) {
            return null;
        }

        $collection = new Collection(TableDto::class);
        foreach ($tables as $table) {
            $collection->attach((new TableDto())->hydrate((array) $table));
        }

        return $collection;
    }







    public function getTablesName($tables): array
    {
        return (!is_array($tables)) ? [] : array_map(function ($table) {
            return ($table->getName());
        }, $tables);
    }








    public function findTableNamesStartWith(string $prefix = ''): array
    {
        $query  = $this->getTablesFindQueryByTableType('BASE TABLE', $prefix);
        $result = $this->client->query($query);
        if (!$result) {
            return [];
        }

        $tables = [];
        while ($row = $this->client->fetchRow($result)) {
            if (isset($row[0])) {
                $tables[] = $row[0];
            }
        }

        $this->client->freeResult($result);

        return $tables;
    }








    public function findViewsNamesStartWith(string $prefix = ''): array
    {
        $query  = $this->getTablesFindQueryByTableType('VIEW', $prefix);
        $result = $this->client->query($query);
        if (!$result) {
            return [];
        }

        $views = [];
        while ($row = $this->client->fetchRow($result)) {
            if (isset($row[0])) {
                $views[] = $row[0];
            }
        }

        $this->client->freeResult($result);

        return $views;
    }






    public function getCreateViewQuery(string $viewName): string
    {
        $result = $this->client->query("SHOW CREATE VIEW `{$viewName}`");
        $row    = $this->client->fetchAssoc($result);

        $this->client->freeResult($result);

        if (isset($row['Create View'])) {
            return $row['Create View'];
        }

        return '';
    }








    public function getCreateTableQuery(string $tableName): string
    {
        $result = $this->client->query("SHOW CREATE TABLE `{$tableName}`");
        if ($result === false) {
            return '';
        }

        $row = $this->client->fetchAssoc($result);

        $this->client->freeResult($result);

        if (isset($row['Create Table'])) {
            return $row['Create Table'];
        }

        return '';
    }









    public function deleteTablesStartWith(string $prefix, array $excludedTables = [], bool $deleteViews = false): bool
    {
        if ($deleteViews) {
 
            $views = $this->findViewsNamesStartWith($prefix);
            if (is_array($views) && !empty($views)) {
                $viewsToRemove = array_diff($views, $excludedTables);
                if (!$this->deleteViews($viewsToRemove)) {
                    return false;
                }
            }
        }

        $tables = $this->findTableStatusStartsWith($prefix);
        if ($tables === null) {
            return true;
        }

        $tables = $this->getTablesName($tables->toArray());

        $tablesToRemove = array_diff($tables, $excludedTables);
        if ($tablesToRemove === []) {
            return true;
        }

        if (!$this->deleteTables($tablesToRemove)) {
            return false;
        }

        return true;
    }







    public function deleteTables($tables): bool
    {
        $isForeignKeyCheckEnabled = "0";

        $result = $this->client->fetchAssoc($this->client->query("SELECT @@FOREIGN_KEY_CHECKS AS fk_check"));
        if (!empty($result)) {
            $isForeignKeyCheckEnabled = empty($result['fk_check']) ? "0" : $result['fk_check'];
        }

        if ($isForeignKeyCheckEnabled === "1") {
            $this->client->query("SET FOREIGN_KEY_CHECKS = 0");
        }

        foreach ($tables as $table) {
 
            if ($this->isProductionSiteTableOrView($table)) {
                $this->errors[] = sprintf(__("Fatal Error: Trying to delete table %s of main WP installation!", 'wp-staging'), $table);

                return false;
            }

            $this->client->query("DROP TABLE `{$table}`;");
        }

        if ($isForeignKeyCheckEnabled === "1") {
            $this->client->query("SET FOREIGN_KEY_CHECKS = 1");
        }

        return true;
    }







    public function deleteViews($views): bool
    {
        foreach ($views as $view) {
 
            if ($this->isProductionSiteTableOrView($view)) {
                $this->errors[] = sprintf(__("Fatal Error: Trying to delete view %s of main WP installation!", 'wp-staging'), $view);

                return false;
            }

            $this->database->getWpdba()->exec("DROP VIEW {$view};");
        }

        return true;
    }




    public function getDatabase()
    {
        return $this->database;
    }





    public function dropTablesLike(string $likeCondition): bool
    {
        $wpdb   = $this->database->getWpdb();
        $tables = $wpdb->get_results(
            $wpdb->prepare('SHOW TABLES LIKE %s;', $wpdb->esc_like($likeCondition) . '%')
        );

        if (!$tables) {
            return false;
        }

        foreach ($tables as $tableObj) {
            $tableName = current($tableObj);
            $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
        }

        return true;
    }





    public function dropTable(string $tableName): bool
    {
        $wpdb   = $this->database->getWpdb();
        $tables = $wpdb->get_results(
            $wpdb->prepare('SHOW TABLES LIKE %s;', $wpdb->esc_like($tableName)),
            ARRAY_A
        );

        if (!$tables) {
            return true;
        }

        foreach ($tables as $tableObj) {
            $tableName = current($tableObj);
            $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
        }

        return true;
    }






    public function renameTable(string $sourceTable, string $destinationTable): bool
    {
 
        $result = $this->client->query(sprintf(
            "RENAME TABLE `%s` TO `%s`;",
            $sourceTable,
            $destinationTable
        ));

        return $result !== false;
    }






    public function cloneTableWithoutData(string $sourceTable, string $destinationTable): bool
    {
        return $this->client->query("CREATE TABLE $destinationTable LIKE $sourceTable");
    }








    public function copyTableData(string $sourceTable, string $destinationTable, int $offset = 0, int $limit = 0): bool
    {
        $query = sprintf(
            "INSERT INTO %s SELECT * FROM %s LIMIT %d OFFSET %d",
            $destinationTable,
            $sourceTable,
            $limit,
            $offset
        );

        return $this->client->query($query);
    }





    public function getRowsCount(string $tableName, bool $encapsulateTableName = true): int
    {
        $tableName = $encapsulateTableName ? "`$tableName`" : $tableName;

        return (int)$this->database->getWpdb()->get_var("SELECT COUNT(1) FROM $tableName");
    }




    public function getLastWpdbError(): string
    {
 
        $wpdb = $this->database->getWpdba()->getClient();

        return $wpdb->last_error;
    }





    public function getNumericPrimaryKey(string $database, string $table): string
    {
        if ($this->hasMoreThanOnePrimaryKey($database, $table)) {
            throw new UnexpectedValueException();
        }

        $query = "SELECT COLUMN_NAME
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = '$table'
                  AND TABLE_SCHEMA = '$database'
                  AND IS_NULLABLE = 'NO'
                  AND DATA_TYPE IN ('int', 'bigint', 'smallint', 'mediumint')
                  AND COLUMN_KEY = 'PRI'
                  AND EXTRA like '%auto_increment%';";

        $result = $this->client->query($query);

        if (!$result) {
            throw new UnexpectedValueException();
        }

        $primaryKey = $this->client->fetchObject($result);

        $this->client->freeResult($result);

        if (!is_object($primaryKey)) {
            throw new UnexpectedValueException();
        }

        if (!property_exists($primaryKey, 'COLUMN_NAME')) {
            throw new UnexpectedValueException();
        }

        if (empty($primaryKey->COLUMN_NAME)) {
            throw new UnexpectedValueException();
        }

        return $primaryKey->COLUMN_NAME;
    }








    public function replaceTableConstraints(string $input): string
    {
        $pattern = [





















            '/(,)?(\s+)?CONSTRAINT\s(.*)\sREFERENCES\s(.*)(,)?(\s+)?ON\s+(DELETE|UPDATE)\s(.*)\s?(CASCADE|RESTRICT|NO\sACTION|SET\sNULL|SET\sDEFAULT)(,)/i',
            '/(,)?(\s+)?CONSTRAINT\s(.*)\sREFERENCES\s(.*)(,)?(\s+)?ON\s+(DELETE|UPDATE)\s(.*)\s?\)/i',
            '/\s+CONSTRAINT(.+)REFERENCES(.+),/i',
            '/,\s+CONSTRAINT(.+)REFERENCES(.+)/i',
        ];

        $replace = ['', ')', '', ''];
        return (string)preg_replace($pattern, $replace, $input);
    }






    public function replaceTableOptions(string $input): string
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





    public function lockTable(string $tableName)
    {
        if (!$this->client->query("LOCK TABLES `$tableName` WRITE;")) {
            throw new RuntimeException("WP STAGING: Could not lock table $tableName");
        }
    }





    public function unlockTables()
    {
        if (!$this->client->query("UNLOCK TABLES;")) {
            throw new RuntimeException("WP STAGING: Could not unlock tables");
        }
    }






    public function getColumnTypes(string $tableName): array
    {
        $column_types = [];

        $result = $this->client->query("SHOW COLUMNS FROM `{$tableName}`");
        while ($row = $this->client->fetchAssoc($result)) {
            if (isset($row['Field'])) {
                $column_types[strtolower($row['Field'])] = strtolower($row['Type']);
            }
        }

        $this->client->freeResult($result);

        return $column_types;
    }





    private function isProductionSiteTableOrView($tableOrView): bool
    {
 
        if ($this->database->isExternal()) {
            return false;
        }

        $productionPrefix = $this->database->getProductionPrefix();

 
        $result = $this->strHelper->startsWith($tableOrView, $productionPrefix);
        if (!$result) {
            return false;
        }

        $tmpPrefixes = [
            DatabaseImporter::TMP_DATABASE_PREFIX,
            DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP,
        ];

        if (in_array($productionPrefix, $tmpPrefixes)) {
            return true;
        }

        foreach ($tmpPrefixes as $tmpPrefix) {
            if ($this->strHelper->startsWith($tableOrView, $tmpPrefix) && $this->strHelper->startsWith($tmpPrefix, $productionPrefix)) {
                return false;
            }
        }

        return true;
    }






    private function getTablesFindQueryByTableType(string $tableType, string $prefix = ''): string
    {

        if ($this->isSqlLite) {
 
            $tableType = $tableType === 'VIEW' ? 'view' : 'table';
            $query     = "SELECT name FROM sqlite_master WHERE type = '{$tableType}'";
            if (!empty($prefix)) {
                $query .= " AND name LIKE '{$this->database->escapeSqlPrefixForLIKE($prefix)}%'";
            }
        } else {
 
            $dbname = $this->database->getWpdba()->getClient()->dbname;
            $query  = "SHOW FULL TABLES FROM `{$dbname}` WHERE `Table_type` = '{$tableType}'";
            if (!empty($prefix)) {
                $query .= " AND `Tables_in_{$dbname}` LIKE '{$this->database->escapeSqlPrefixForLIKE($prefix)}%'";
            }
        }

        return $query;
    }






    private function hasMoreThanOnePrimaryKey(string $database, string $table): bool
    {
        $query = "SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'";

        $result = $this->client->query($query);

        if (!$result) {
            throw new UnexpectedValueException();
        }

        $primaryKeys = $this->client->fetchAll($result);

        return count($primaryKeys) > 1;
    }
}
