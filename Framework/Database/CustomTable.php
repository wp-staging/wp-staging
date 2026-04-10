<?php

namespace WPStaging\Framework\Database;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Database as DatabaseAdapter;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient as Database;

use function WPStaging\functions\debug_log;

/**
 * Abstract base class for custom database tables.
 *
 * Subclasses implement four abstract methods to define their schema,
 * and inherit all table lifecycle management (creation, version tracking,
 * migration via dbDelta, existence checks, and teardown).
 */
abstract class CustomTable
{
    const TABLE_NOT_EXIST = -1;
    const TABLE_EXISTS    = 0;
    const TABLE_CREATED   = 1;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var int|null
     */
    protected $tableState;

    /**
     * @param Database|null $database
     */
    public function __construct($database = null)
    {
        $this->database = $database ?: WPStaging::getInstance()->getContainer()->make(DatabaseAdapter::class)->getClient();
    }

    /**
     * Returns the unprefixed table name (e.g. 'wpstg_settings').
     *
     * @return string
     */
    abstract protected function getTableName();

    /**
     * Returns the wp_options key used to track the table schema version.
     *
     * @return string
     */
    abstract protected function getTableVersionKey();

    /**
     * Returns the current schema version string (e.g. '1.0.0').
     *
     * @return string
     */
    abstract protected function getTableVersion();

    /**
     * Returns the CREATE TABLE SQL statement for this table.
     *
     * @return string
     */
    abstract protected function getCreateTableSql();

    /**
     * Invalidates all cached data for this table.
     * Subclasses must implement this to clear their specific cache entries.
     */
    abstract public function invalidateCache();

    /**
     * Returns the fully prefixed table name.
     *
     * @return string
     */
    public function getFullTableName()
    {
        global $wpdb;
        return $wpdb->prefix . $this->getTableName();
    }

    /**
     * Ensures the table exists, creating or updating it if necessary.
     * Called lazily before any CRUD operation.
     */
    public function ensureTable()
    {
        if ($this->tableState === null) {
            $this->checkTable(true);
        }
    }

    /**
     * Checks the table state and triggers creation/update if needed.
     *
     * @param bool $force Whether to force the check or trust cached state.
     * @return int One of the TABLE_* constants.
     */
    public function checkTable($force = false)
    {
        if (!$force && $this->tableState !== null) {
            return $this->tableState;
        }

        $currentVersion  = get_option($this->getTableVersionKey(), '0.0.0');
        $exists          = $this->tableExists();
        $schemaValid     = $exists && $this->hasExpectedSchema();
        $requiresUpgrade = version_compare($currentVersion, $this->getTableVersion(), '<');

        if ($exists && $schemaValid && !$requiresUpgrade) {
            $this->tableState = self::TABLE_EXISTS;
            return $this->tableState;
        }

        if ($this->updateTable() === self::TABLE_EXISTS) {
            $this->tableState = self::TABLE_EXISTS;
            return self::TABLE_CREATED;
        }

        $this->tableState = self::TABLE_NOT_EXIST;

        return $this->tableState;
    }

    /**
     * Updates the table schema using dbDelta and verifies final table structure.
     *
     * We intentionally avoid transaction/rollback flow here because DDL transaction
     * semantics are engine-dependent and MyISAM does not support transactional rollback.
     *
     * @return int One of the TABLE_* constants.
     */
    protected function updateTable()
    {
        $tableSql = $this->getCreateTableSql();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($tableSql);

        if (!$this->hasExpectedSchema()) {
            // Fallback to direct execution if dbDelta does not materialize the table.
            if ($this->database->query($tableSql) === false || !$this->hasExpectedSchema()) {
                debug_log($this->getTableName() . ' Table Upgrade Error: ' . $this->database->error());
                return self::TABLE_NOT_EXIST;
            }
        }

        update_option($this->getTableVersionKey(), $this->getTableVersion());

        return self::TABLE_EXISTS;
    }

    /**
     * @return bool Whether the table exists.
     */
    public function tableExists()
    {
        global $wpdb;

        $tableName = $this->getFullTableName();

        if ($wpdb instanceof \wpdb) {
            $query  = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($tableName));
            $result = $wpdb->get_var($query);

            return is_string($result) && $result === $tableName;
        }

        $escapedTableName = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $tableName);
        $escapedTableName = $this->database->escape($escapedTableName);

        $result = $this->database->query("SHOW TABLES LIKE '{$escapedTableName}' ESCAPE '\\\\'");
        if ($result === false) {
            return false;
        }

        $row = $this->database->fetchRow($result);

        return !empty($row[0]) && (string)$row[0] === $tableName;
    }

    /**
     * Verifies the existing table matches the expected schema shape.
     *
     * @return bool
     */
    protected function hasExpectedSchema()
    {
        if (!$this->tableExists()) {
            return false;
        }

        $expectedSchema = $this->parseExpectedSchema($this->getCreateTableSql());

        if (empty($expectedSchema['columns']) && empty($expectedSchema['indexes'])) {
            return true;
        }

        $actualColumns = $this->getActualColumnNames();
        foreach ($expectedSchema['columns'] as $columnName) {
            if (!in_array($columnName, $actualColumns, true)) {
                return false;
            }
        }

        $actualIndexes = $this->getActualIndexNames();
        foreach ($expectedSchema['indexes'] as $indexName) {
            if (!in_array($indexName, $actualIndexes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Drops the table if it exists.
      *
      * The table is dropped again after option/cache cleanup because hooks triggered by that
      * cleanup may recreate the table in the same request.
     *
     * @return bool Whether the drop was successful.
     */
    public function dropTable()
    {
        $tableName = $this->getFullTableName();
        $result    = $this->executeDropTableQuery($tableName);

        delete_option($this->getTableVersionKey());
        $this->invalidateCache();

        $cleanupResult = $this->executeDropTableQuery($tableName);

        $this->tableState = null;

        return $result !== false && $cleanupResult !== false;
    }

    /**
     * @return string[]
     */
    private function getActualColumnNames()
    {
        global $wpdb;

        $tableName = $this->getFullTableName();

        if ($wpdb instanceof \wpdb) {
            $columns = $wpdb->get_col("SHOW COLUMNS FROM `{$tableName}`", 0);
            return is_array($columns) ? array_values($columns) : [];
        }

        $result = $this->database->query("SHOW COLUMNS FROM `{$tableName}`");
        if ($result === false) {
            return [];
        }

        $columns = [];
        while ($row = $this->database->fetchAssoc($result)) {
            if (!empty($row['Field'])) {
                $columns[] = (string)$row['Field'];
            }
        }

        return $columns;
    }

    /**
     * @return string[]
     */
    private function getActualIndexNames()
    {
        global $wpdb;

        $tableName = $this->getFullTableName();

        if ($wpdb instanceof \wpdb) {
            $indexes = $wpdb->get_col("SHOW INDEX FROM `{$tableName}`", 2);
            return is_array($indexes) ? array_values(array_unique(array_map('strval', $indexes))) : [];
        }

        $result = $this->database->query("SHOW INDEX FROM `{$tableName}`");
        if ($result === false) {
            return [];
        }

        $indexes = [];
        while ($row = $this->database->fetchAssoc($result)) {
            if (!empty($row['Key_name'])) {
                $indexes[] = (string)$row['Key_name'];
            }
        }

        return array_values(array_unique($indexes));
    }

    /**
     * @param string $createTableSql
     * @return array<string,array<string>>
     */
    private function parseExpectedSchema($createTableSql)
    {
        $schema = [
            'columns' => [],
            'indexes' => [],
        ];

        $createTableSql = trim($createTableSql);
        $openingParen   = strpos($createTableSql, '(');
        $closingParen   = strrpos($createTableSql, ')');

        if ($openingParen === false || $closingParen === false || $closingParen <= $openingParen) {
            return $schema;
        }

        $definitions = preg_split('/\r?\n/', substr($createTableSql, $openingParen + 1, $closingParen - $openingParen - 1));
        if (!is_array($definitions)) {
            return $schema;
        }

        foreach ($definitions as $definition) {
            $definition = trim($definition, " \t\n\r\0\x0B,");

            if ($definition === '') {
                continue;
            }

            if (preg_match('/^PRIMARY\s+KEY/i', $definition)) {
                $schema['indexes'][] = 'PRIMARY';
                continue;
            }

            if (preg_match('/^(?:UNIQUE\s+KEY|KEY|INDEX)\s+`?([A-Za-z0-9_]+)`?/i', $definition, $indexMatches)) {
                $schema['indexes'][] = $indexMatches[1];
                continue;
            }

            if (preg_match('/^`?([A-Za-z0-9_]+)`?\s+/i', $definition, $columnMatches)) {
                $schema['columns'][] = $columnMatches[1];
            }
        }

        $schema['columns'] = array_values(array_unique($schema['columns']));
        $schema['indexes'] = array_values(array_unique($schema['indexes']));

        return $schema;
    }

    /**
     * @param string $tableName
     * @return int|bool
     */
    private function executeDropTableQuery($tableName)
    {
        global $wpdb;

        if ($wpdb instanceof \wpdb) {
            return $wpdb->query("DROP TABLE IF EXISTS `{$tableName}`");
        }

        return $this->database->query("DROP TABLE IF EXISTS `{$tableName}`");
    }
}
