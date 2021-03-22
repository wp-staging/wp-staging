<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace WPStaging\Framework\Database;

use Exception;
use mysqli_result;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Filesystem\File;

class DatabaseDumper
{

    const MAX_SELECT_ROWS = 1000;
    const MAX_TRANSACTION_QUERIES = 1000;
    const MAX_EXECUTION_TIME_SECONDS = 10;

    /** @var InterfaceDatabaseClient */
    private $client;

    /** @var Database */
    private $database;

    /** @var File */
    private $file;

    /** @var string */
    private $filename;

    /**
     * Current table
     * @var string
     */
    private $tableIndex;

    /**
     * Start copy from table row
     * @var string
     */
    private $tableRowsOffset;

    /**
     * Executed table rows
     * @var string
     */
    private $tableRowsExported;

    /** @var array */
    private $tables = [];

    /** @var array */
    protected $tableWhereClauses = [];

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->client = $database->getClient();
    }

    public function __destruct()
    {
        $this->file = null;
    }

    /**
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return int|string
     */
    public function getTableRowsOffset()
    {
        return (int) $this->tableRowsOffset;
    }

    /**
     * @param string
     */
    public function setTableRowsOffset($tableRowsOffset)
    {
        $this->tableRowsOffset = $tableRowsOffset;
    }

    /**
     * @return int
     */
    public function getTableIndex()
    {
        return (int) $this->tableIndex;
    }

    /**
     * @param string
     */
    public function setTableIndex($tableIndex)
    {
        $this->tableIndex = $tableIndex;
    }

    /**
     * @param string
     */
    public function setTableRowsExported($tableRowsExported)
    {
        $this->tableRowsExported = $tableRowsExported;
    }

    /**
     * @return int
     */
    public function getTableRowsExported()
    {
        return (int) $this->tableRowsExported;
    }

    /**
     * @param string
     */
    public function setFileName($filename)
    {
        $this->filename = $filename;
        $this->file = new File($filename, File::MODE_APPEND);
    }

    /**
     * @param callable|null $shouldStop
     * @return string|null
     * @throws Exception
     */
    public function export(callable $shouldStop = null)
    {
        if ($this->tableIndex === 0) {
            $this->file->fwrite($this->getHeader());
        }

        $this->client->query("SET SESSION sql_mode = ''");

        $views = $this->getViews();

        $tables = $this->getTables();

        $loopsMax = count($tables);
        for (; $this->tableIndex < $loopsMax;) {

            $tableName = $tables[$this->tableIndex];

            // Export views
            if ($this->isView($tableName, $views)) {

                $this->writeQueryCreateViews($tableName);

                $this->tableIndex++;
                $this->tableRowsOffset = 0;
            } else {
                $this->writeQueryCreateTable($tableName);

                $primaryKeys = $this->getPrimaryKeys($tableName);
                $tableColumns = $this->getColumnTypes($tableName);

                do {
                    $query = $this->getQuery($primaryKeys, $tableName);
                    $result = $this->client->query($query);

                    if ($this->isTableCorrupt()) {
                        $this->repairTable($tableName);
                        $result = $this->client->query($query);
                    }

                    $numRows = $this->writeQueryInsert($result, $tableName, $tableColumns);

                    $this->client->freeResult($result);

                    // Stop execution
                    if ($shouldStop && $shouldStop()) {
                        return null;
                    }

                } while ($numRows > 0);
            }
        }
        return $this->filename;
    }

    /**
     * @param $tableName
     * @throws Exception
     */
    private function writeQueryCreateViews($tableName)
    {
        if ($this->tableRowsOffset === 0) {

            $dropView = "\nDROP VIEW IF EXISTS `{$tableName}`;\n";
            $this->file->fwrite($dropView);

            $create_view = $this->getCreateView($tableName);

            $create_view = $this->replaceViewOptions($create_view);

            $this->file->fwrite($create_view);

            $this->file->fwrite(";\n\n");
        }
    }

    /**
     * Get header for dump file
     *
     * @return string
     */
    private function getHeader()
    {
        return sprintf(
            "-- WP Staging SQL Export Dump\n" .
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

    /**
     *
     * @return array
     */
    private function getTables()
    {
        return $this->tables;
    }

    /**  @param array $tables */
    public function setTables(array $tables = [])
    {
        $this->tables = $tables;
    }

    /** @return array */
    private function getViews()
    {
        static $views = null;

        if ($views === null) {
            $views = [];

            // Loop over views
            $result = $this->client->query("SHOW FULL TABLES FROM `{$this->getWpDb()->dbname}` WHERE `Table_type` = 'VIEW'");
            while ($row = $this->client->fetchRow($result)) {
                if (isset($row[0])) {
                    $views[] = $row[0];
                }
            }

            $this->client->freeResult($result);
        }

        return $views;
    }

    /**
     * @param string $view_name View name
     * @return string
     */
    private function getCreateView($view_name)
    {
        $result = $this->client->query("SHOW CREATE VIEW `{$view_name}`");
        $row = $this->client->fetchAssoc($result);

        $this->client->freeResult($result);

        if (isset($row['Create View'])) {
            return $row['Create View'];
        }
        return '';
    }

    /**
     *
     * @param string $input Table value
     * @return string
     */
    private function replaceViewOptions($input)
    {
        return preg_replace('/CREATE(.+?)VIEW/i', 'CREATE VIEW', $input);
    }

    /**
     * @param string $input SQL statement
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
     * @param string $table_name Table name
     * @return array
     */
    private function getPrimaryKeys($table_name)
    {
        $primary_keys = [];

        $result = $this->client->query("SHOW KEYS FROM `{$table_name}` WHERE `Key_name` = 'PRIMARY'");
        while ($row = $this->client->fetchAssoc($result)) {
            if (isset($row['Column_name'])) {
                $primary_keys[] = $row['Column_name'];
            }
        }

        $this->client->freeResult($result);

        return $primary_keys;
    }

    /**
     * @param string $table_name Table name
     * @return array
     */
    private function getColumnTypes($table_name)
    {
        $column_types = [];

        $result = $this->client->query("SHOW COLUMNS FROM `{$table_name}`");
        while ($row = $this->client->fetchAssoc($result)) {
            if (isset($row['Field'])) {
                $column_types[strtolower($row['Field'])] = $row['Type'];
            }
        }

        $this->client->freeResult($result);

        return $column_types;
    }

    /**
     * @param string $table_name Table name
     * @return array
     */
    private function getTableWhereClauses($table_name)
    {
        if (isset($this->tableWhereClauses[strtolower($table_name)])) {
            return $this->tableWhereClauses[strtolower($table_name)];
        }

        return [];
    }

    /**
     * @param string $table_name Table name
     * @return void
     */
    protected function repairTable($table_name)
    {
        $this->client->query("REPAIR TABLE `{$table_name}`");
    }

    /**
     * Get MySQL create table query
     *
     * @param string $table_name Table name
     * @return string
     */
    protected function getCreateTable($table_name)
    {
        $result = $this->client->query("SHOW CREATE TABLE `{$table_name}`");
        $row = $this->client->fetchAssoc($result);

        $this->client->freeResult($result);

        if (isset($row['Create Table'])) {
            return $row['Create Table'];
        }
        return '';
    }

    /**
     * @param string $input
     * @param string $column_type
     * @return string
     */
    protected function prepareTableValues($input, $column_type)
    {
        if ($input === null) {
            return 'NULL';
        } elseif (stripos($column_type, 'tinyint') === 0) {
            return $input;
        } elseif (stripos($column_type, 'smallint') === 0) {
            return $input;
        } elseif (stripos($column_type, 'mediumint') === 0) {
            return $input;
        } elseif (stripos($column_type, 'int') === 0) {
            return $input;
        } elseif (stripos($column_type, 'bigint') === 0) {
            return $input;
        } elseif (stripos($column_type, 'float') === 0) {
            return $input;
        } elseif (stripos($column_type, 'double') === 0) {
            return $input;
        } elseif (stripos($column_type, 'decimal') === 0) {
            return $input;
        } elseif (stripos($column_type, 'bit') === 0) {
            return $input;
        }

        return "'" . $this->client->escape($input) . "'";
    }

    /**
     * @param $tableName
     * @throws Exception
     */
    private function writeQueryCreateTable($tableName)
    {
        if ($this->tableRowsOffset === 0) {

            $dropTable = "\nDROP TABLE IF EXISTS `{$tableName}`;\n";
            $this->file->fwrite($dropTable);

            $createTableQuery = $this->getCreateTable($tableName);
            $createTableQuery = $this->replaceTableConstraints($createTableQuery);
            $createTableQuery = $this->replaceTableOptions($createTableQuery);
            $this->file->fwrite(preg_replace('#\s+#', ' ', $createTableQuery));

            $this->file->fwrite(";\n\n");
        }
    }

    /**
     * @param array $primaryKeys
     * @param $tableName
     * @return string
     */
    private function getQuery(array $primaryKeys, $tableName)
    {
        if ($primaryKeys) {
            return $this->getSelectQueryPrimaryKey($primaryKeys, $tableName);
        }
        return $this->getSelectQuery($tableName);
    }

    /**
     * @param array $primaryKeys
     * @param $tableName
     * @return string
     */
    private function getSelectQueryPrimaryKey(array $primaryKeys, $tableName)
    {
        // Set table keys
        $tableKeys = [];
        foreach ($primaryKeys as $key) {
            $tableKeys[] = sprintf('`%s`', $key);
        }

        $tableKeys = implode(', ', $tableKeys);

        // Set table where clauses
        $tableWhere = [1];
        foreach ($this->getTableWhereClauses($tableName) as $clause) {
            $tableWhere[] = $clause;
        }

        $tableWhere = implode(' AND ', $tableWhere);

        // Return query with offset and rows count
        return sprintf(
            'SELECT t1.* FROM `%s` AS t1 JOIN (SELECT %s FROM `%s` WHERE %s ORDER BY %s LIMIT %d, %d) AS t2 USING (%s)',
            $tableName,
            $tableKeys,
            $tableName,
            $tableWhere,
            $tableKeys,
            $this->tableRowsOffset,
            self::MAX_SELECT_ROWS,
            $tableKeys
        );
    }

    /**
     * @param $tableName
     * @return string
     */
    private function getSelectQuery($tableName)
    {
        // Set table keys
        $tableKeys = 1;

        // Set table where clauses
        $tableWhere = [1];
        foreach ($this->getTableWhereClauses($tableName) as $clause) {
            $tableWhere[] = $clause;
        }

        $tableWhere = implode(' AND ', $tableWhere);

        // Return query with offset and rows count
        return sprintf(
            'SELECT * FROM `%s` WHERE %s ORDER BY %s LIMIT %d, %d',
            $tableName,
            $tableWhere,
            $tableKeys,
            $this->tableRowsOffset,
            self::MAX_SELECT_ROWS
        );
    }

    /**
     * @return bool
     */
    private function isTableCorrupt()
    {
        return $this->client->errno() === 1194;
    }

    /**
     * @param $tableName
     * @param array $views
     * @return bool
     */
    private function isView($tableName, array $views)
    {
        return in_array($tableName, $views, true);
    }

    /**
     * @param mysqli_result|resource $result
     * @param string $tableName
     * @param array $tableColumns
     * @return mixed
     * @throws Exception
     */
    private function writeQueryInsert($result, $tableName, $tableColumns)
    {
        if ($numRows = $this->client->numRows($result)) {

            // Loop over table rows
            while ($row = $this->client->fetchAssoc($result)) {

                // Start transaction
                if ($this->tableRowsOffset % self::MAX_TRANSACTION_QUERIES === 0) {
                    $this->file->fwrite("START TRANSACTION;\n");
                }

                $items = [];
                foreach ($row as $key => $value) {
                    $items[] = $this->prepareTableValues($value, $tableColumns[strtolower($key)]);
                }

                // Set table values
                $tableValues = implode(',', $items);

                $insertQuery = "INSERT INTO `{$tableName}` VALUES ({$tableValues});\n";

                // Write INSERTS
                $this->file->fwrite($insertQuery);

                $this->tableRowsOffset++;
                $this->tableRowsExported++;

                // End of transaction
                if ($this->tableRowsOffset % self::MAX_TRANSACTION_QUERIES === 0) {
                    $this->file->fwrite("COMMIT;\n");
                }

            }
        } else {

            // End of transaction
            if ($this->tableRowsOffset % self::MAX_TRANSACTION_QUERIES !== 0) {
                $this->file->fwrite("COMMIT;\n");
            }

            $this->tableIndex++;
            $this->tableRowsOffset = 0;
        }
        return $numRows;
    }

    private function getWpDb()
    {
        return $this->database->getWpdba()->getClient();
    }

}
