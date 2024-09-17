<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x type-hints & return types

namespace WPStaging\Framework\Database;

use WPStaging\Backup\Ajax\Restore\PrepareRestore;
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

    /**
     * @param Database|null $database
     */
    public function __construct($database = null)
    {
        $this->database  = $database ?: new Database();
        $this->client    = $this->database->getClient();
        $this->strHelper = new Strings();
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
        $row = $this->client->fetchAssoc($result);

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
        $isForeignKeyCheckEnabled = false;
        $isForeignKeyCheckEnabled = $this->database->getClient()->query("SELECT @@FOREIGN_KEY_CHECKS AS fk_check;")->fetch_assoc()['fk_check'];
        if ($isForeignKeyCheckEnabled === "1") {
            $this->database->getClient()->query("SET FOREIGN_KEY_CHECKS = 0;");
        }

        foreach ($tables as $table) {
            // PROTECTION: Never delete any table that begins with wp prefix of live site
            if ($this->isProductionSiteTableOrView($table)) {
                $this->errors[] = sprintf(__("Fatal Error: Trying to delete table %s of main WP installation!", 'wp-staging'), $table);

                return false;
            }

            $this->database->getClient()->query("DROP TABLE `{$table}`;");
        }

        if ($isForeignKeyCheckEnabled === "1") {
            $this->database->getClient()->query("SET FOREIGN_KEY_CHECKS = 1;");
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
        $wpdb = $this->database->getWpdb();
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
        $wpdb = $this->database->getWpdb();
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
        $result = $this->database->getClient()->query(sprintf(
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
        return $this->database->getClient()->query("CREATE TABLE $destinationTable LIKE $sourceTable");
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

        return $this->database->getClient()->query($query);
    }

    /**
     * @param string $tableName
     * @return int
     */
    public function getRowsCount(string $tableName): int
    {
        return (int)$this->database->getWpdb()->get_var("SELECT COUNT(1) FROM `$tableName`");
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
            PrepareRestore::TMP_DATABASE_PREFIX,
            PrepareRestore::TMP_DATABASE_PREFIX_TO_DROP,
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
        $dbname = $this->database->getWpdba()->getClient()->dbname;
        $query  = "SHOW FULL TABLES FROM `{$dbname}` WHERE `Table_type` = '{$tableType}'";
        if (!empty($prefix)) {
            $query .= " AND `Tables_in_{$dbname}` LIKE '{$this->database->escapeSqlPrefixForLIKE($prefix)}%'";
        }

        return $query;
    }
}
