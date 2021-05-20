<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x type-hints & return types

namespace WPStaging\Framework\Database;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Collection\Collection;
use WPStaging\Framework\Utils\Strings;

class TableService
{
    /** @var Database */
    private $database;

    /** @var Database\InterfaceDatabase|Database\InterfaceDatabaseClient|Database\MysqlAdapter|Database\MysqliAdapter|null */
    private $client;

    /** @var callable|null */
    private $shouldStop;

    /** @var array */
    private $errors = [];

    public function __construct(Database $database = null)
    {
        $this->database = $database ?: new Database();
        $this->client = $this->database->getClient();
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
    public function setShouldStop(callable $shouldStop = null)
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
        $tables = $this->database->find("SHOW TABLE STATUS LIKE '{$this->provideSqlPrefix($prefix)}%'");
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
    public function getTablesName($tables)
    {
        return (!is_array($tables)) ? [] : array_map(function ($table) {
            return ($table->getName());
        }, $tables);
    }

    /**
     * Get all base tables starting with a certain prefix
     * This does not include table views
     * @param string|null $prefix
     *
     * @return array
     */
    public function findTableNamesStartWith($prefix = null)
    {
        $result = $this->client->query("SHOW FULL TABLES FROM `{$this->database->getWpdba()->getClient()->dbname}` WHERE `Table_type` = 'BASE TABLE'");
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

        if ($prefix === null) {
            return $tables;
        }

        return $this->getFilteredResult($tables, $prefix);
    }


    /**
     * Get all table views starting with a certain prefix
     * @return array|null
     */
    public function findViewsNamesStartWith($prefix = null)
    {
        $result = $this->client->query("SHOW FULL TABLES FROM `{$this->database->getWpdba()->getClient()->dbname}` WHERE `Table_type` = 'VIEW'");
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

        if ($prefix === null) {
            return $views;
        }

        return $this->getFilteredResult($views, $prefix);
    }

    /**
     * Delete all the tables or views that starts with $startsWith
     * @param string $startsWith
     * @param array $excludedTables
     * @return bool
     */
    public function deleteTablesStartWith($startsWith = null, $excludedTables = [])
    {
        $prefix = $this->provideSqlPrefix($startsWith);
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
     * @param string $prefix
     *
     * @return bool
     */
    public function deleteTables($tables)
    {
        foreach ($tables as $table) {
            // PROTECTION: Never delete any table that beginns with wp prefix of live site
            // TODO: inject class Strings using DI
            if (!$this->database->isExternal() && (new Strings())->startsWith($table, $this->database->getProductionPrefix())) {
                $this->errors[] = sprintf(__("Fatal Error: Trying to delete table %s of main WP installation!", 'wp-staging'), $table);
                return false;
            }

            $this->database->getWpdba()->exec("DROP TABLE {$table}");

            if (!is_callable($this->shouldStop)) {
                continue;
            }

            if (call_user_func($this->shouldStop)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all elements starting with a specific string from an array
     * @param array $data
     * @param string $startsWith
     * @return array
     */
    private function getFilteredResult($data, $startsWith)
    {
        $result = [];
        foreach ($data as $value) {
            if (strpos($value, $startsWith) === 0) {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param string|null $prefix
     * @return string
     */
    private function provideSqlPrefix($prefix = null)
    {
        return $this->database->provideSqlPrefix($prefix);
    }
}
