<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x type-hints & return types

namespace WPStaging\Framework\Database;

use RuntimeException;
use UnexpectedValueException;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Collection\Collection;
use WPStaging\Framework\Utils\Strings;

class TableService
{
    /** @var Database */
    private $database;

    /** @var Database\DatabaseAdapterInterface|Database\InterfaceDatabaseClient|Database\MysqliAdapter|null */
    private $client;

    /** @var callable|null */
    private $shouldStop;

    /** @var array */
    private $errors = [];

    /** @var Strings */
    private $strHelper;

    private $isSqlLite = false;

    /**
     * @param Database|null $database
     */
    public function __construct($database = null)
    {
        $this->database  = $database ?: new Database();
        $this->client    = $this->database->getClient();
        $this->strHelper = new Strings();

        $this->isSqlLite = property_exists($this->client, 'isSQLite');
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return callable|null
     */
    public function getShouldStop()
    {
        return $this->shouldStop;
    }

    /**
     * @param callable|null $shouldStop
     * @return self
     */
    public function setShouldStop($shouldStop = null)
    {
        $this->shouldStop = $shouldStop;
        return $this;
    }

    /**
     * @param string $tableName
     * @return bool
     */
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

    /**
     * Get all tables information in the current datababase as collection
     *
     * @return Collection|null
     */
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

    /**
     * Get all tables information starting with a specific prefix as collection
     * @param string|null $prefix
     *
     * @return TableDto[]|Collection|null
     */
    public function findTableStatusStartsWith($prefix = null)
    {
        // eg: SHOW TABLE STATUS LIKE 'wp\_%';
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

    /**
     * Get names of all table only
     * @param array $tables
     *
     * @return array
     */
    public function getTablesName($tables): array
    {
        return (!is_array($tables)) ? [] : array_map(function ($table) {
            return ($table->getName());
        }, $tables);
    }

    /**
     * Get all base tables starting with a certain prefix
     * This does not include table views
     * @param string $prefix
     *
     * @return array
     */
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

    /**
     * Get all table views starting with a certain prefix
     *
     * @param string $prefix
     *
     * @return array
     */
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

    /**
     * @param string $viewName View name
     *
     * @return string
     */
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

    /**
     * Get MySQL create table query
     *
     * @param string $table_name Table name
     *
     * @return string
     */
    public function getCreateTableQuery(string $table_name): string
    {
        $result = $this->client->query("SHOW CREATE TABLE `{$table_name}`");
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

    /**
     * Delete all the tables or views that starts with $startsWith
     *
     * @param string $prefix
     * @param array $excludedTables
     * @param bool $deleteViews
     * @return bool
     */
    public function deleteTablesStartWith(string $prefix, array $excludedTables = [], bool $deleteViews = false): bool
    {
        if ($deleteViews) {
            // Delete VIEWS first
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

    /**
     * Delete Tables
     * @param array $tables
     *
     * @return bool
     */
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
            // PROTECTION: Never delete any table that begins with wp prefix of live site
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

    /**
     * Delete Views
     *
     * @param array $views
     * @return bool
     */
    public function deleteViews($views): bool
    {
        foreach ($views as $view) {
            // PROTECTION: Never delete any table that begins with wp prefix of live site
            if ($this->isProductionSiteTableOrView($view)) {
                $this->errors[] = sprintf(__("Fatal Error: Trying to delete view %s of main WP installation!", 'wp-staging'), $view);

                return false;
            }

            $this->database->getWpdba()->exec("DROP VIEW {$view};");
        }

        return true;
    }

    /**
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param string $likeCondition
     * @return bool
     */
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

    /**
     * @param string $tableName
     * @return bool
     */
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

    /**
     * @param string $sourceTable
     * @param string $destinationTable
     * @return bool
     */
    public function renameTable(string $sourceTable, string $destinationTable): bool
    {
        // Rename table return int on success and false on failure, so we alter the condition to check for false
        $result = $this->client->query(sprintf(
            "RENAME TABLE `%s` TO `%s`;",
            $sourceTable,
            $destinationTable
        ));

        return $result !== false;
    }

    /**
     * @param string $sourceTable
     * @param string $destinationTable
     * @return bool
     */
    public function cloneTableWithoutData(string $sourceTable, string $destinationTable): bool
    {
        return $this->client->query("CREATE TABLE $destinationTable LIKE $sourceTable");
    }

    /**
     * @param string $sourceTable
     * @param string $destinationTable
     * @param int $offset
     * @param int $limit
     * @return bool
     */
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

    /**
     * @param string $tableName
     * @return int
     */
    public function getRowsCount(string $tableName, bool $encapsulateTableName = true): int
    {
        $tableName = $encapsulateTableName ? "`$tableName`" : $tableName;

        return (int)$this->database->getWpdb()->get_var("SELECT COUNT(1) FROM $tableName");
    }

    /**
     * @return string
     */
    public function getLastWpdbError(): string
    {
        /** @var \wpdb */
        $wpdb = $this->database->getWpdba()->getClient();

        return $wpdb->last_error;
    }


    /**
     * @return string The primary key of the current table, if any.
     */
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

    /**
     * Replace Constraints with empty string to remove them
     *
     * @param string $input SQL statement
     *
     * @return string
     */
    public function replaceTableConstraints(string $input): string
    {
        $pattern = [
            /**
             * This regex pattern makes it possible to match Table Constraints in SQL with close brackets ")" as the end marker.
             * If it matches, the string will be replaced with close brackets ")" to close "CREATE TABLE" open brackets "(" to avoid syntax errors.
             *
             * Example:
             *  KEY `key1` (`field1`,`field2`), CONSTRAINT `key_constraint` FOREIGN KEY (`field1`, `field2`) REFERENCES `another_table` (`field1`, `field2`) ON DELETE CASCADE ON UPDATE NO ACTION ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
             *
             * Pattern match:
             *  , CONSTRAINT `key_constraint` FOREIGN KEY (`field1`, `field2`) REFERENCES `another_table` (`field1`, `field2`) ON DELETE CASCADE ON UPDATE NO ACTION )
             *
             * String before:
             *  KEY `key1` (`field1`,`field2`), CONSTRAINT `key_constraint` FOREIGN KEY (`field1`, `field2`) REFERENCES `another_table` (`field1`, `field2`) ON DELETE CASCADE ON UPDATE NO ACTION ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
             *
             * String after:
             * KEY `key1` (`field1`,`field2`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
             *
             * @see https://github.com/wp-staging/wp-staging-pro/issues/3259
             * @see https://github.com/wp-staging/wp-staging-pro/pull/3265
             * @see https://github.com/wp-staging/wp-staging-pro/issues/3303
             * @see https://github.com/wp-staging/wp-staging-pro/pull/3304
             */
            '/(,)?(\s+)?CONSTRAINT\s(.*)\sREFERENCES\s(.*)(,)?(\s+)?ON\s+(DELETE|UPDATE)\s(.*)\s?(CASCADE|RESTRICT|NO\sACTION|SET\sNULL|SET\sDEFAULT)(,)/i',
            '/(,)?(\s+)?CONSTRAINT\s(.*)\sREFERENCES\s(.*)(,)?(\s+)?ON\s+(DELETE|UPDATE)\s(.*)\s?\)/i',
            '/\s+CONSTRAINT(.+)REFERENCES(.+),/i',
            '/,\s+CONSTRAINT(.+)REFERENCES(.+)/i',
        ];

        $replace = ['', ')', '', ''];
        return (string)preg_replace($pattern, $replace, $input);
    }

    /**
     * @param string $input SQL statement
     *
     * @return string
     */
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

    /**
     * @return void
     * @throws RuntimeException
     */
    public function lockTable(string $tableName)
    {
        if (!$this->client->query("LOCK TABLES `$tableName` WRITE;")) {
            throw new RuntimeException("WP STAGING: Could not lock table $tableName");
        }
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    public function unlockTables()
    {
        if (!$this->client->query("UNLOCK TABLES;")) {
            throw new RuntimeException("WP STAGING: Could not unlock tables");
        }
    }

    /**
     * @param string $tableName
     *
     * @return array
     */
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

    /**
     * @param string $tableOrView
     * @return bool
     */
    private function isProductionSiteTableOrView($tableOrView): bool
    {
        // Early return if current database is external
        if ($this->database->isExternal()) {
            return false;
        }

        $productionPrefix = $this->database->getProductionPrefix();

        // If table does not start with production prefix, it is not a production table
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

    /**
     * @param string $tableType
     * @param string $prefix
     * @return string
     */
    private function getTablesFindQueryByTableType(string $tableType, string $prefix = ''): string
    {

        if ($this->isSqlLite) {
            // SQLite query
            $tableType = $tableType === 'VIEW' ? 'view' : 'table';
            $query     = "SELECT name FROM sqlite_master WHERE type = '{$tableType}'";
            if (!empty($prefix)) {
                $query .= " AND name LIKE '{$this->database->escapeSqlPrefixForLIKE($prefix)}%'";
            }
        } else {
            // MySQL-compatible query
            $dbname = $this->database->getWpdba()->getClient()->dbname;
            $query  = "SHOW FULL TABLES FROM `{$dbname}` WHERE `Table_type` = '{$tableType}'";
            if (!empty($prefix)) {
                $query .= " AND `Tables_in_{$dbname}` LIKE '{$this->database->escapeSqlPrefixForLIKE($prefix)}%'";
            }
        }

        return $query;
    }

    /**
     * @return bool
     *
     * @throws UnexpectedValueException
     */
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
