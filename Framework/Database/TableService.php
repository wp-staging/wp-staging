<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x type-hints & return types

namespace WPStaging\Framework\Database;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Collection\Collection;

class TableService
{
    /** @var Database */
    private $database;

    /** @var Database\InterfaceDatabase|Database\InterfaceDatabaseClient|Database\MysqlAdapter|Database\MysqliAdapter|null */
    private $client;

    public function __construct(Database $database = null)
    {
        $this->database = $database ?: new Database;
        $this->client = $this->database->getClient();
    }

    /**
     * Get all tables information starting with a specific prefix as collection
     * @param string|null $prefix
     *
     * @return TableDto[]|Collection|null
     */
    public function findTableStatusStartsWith($prefix = null)
    {
        $tables = $this->database->find('SHOW TABLE STATUS LIKE "' . $this->provideSqlPrefix($prefix) . '%"');
        if (!$tables) {
            return null;
        }

        $collection = new Collection(TableDto::class);
        foreach ($tables as $table) {
            $collection->attach((new TableDto)->hydrate((array) $table));
        }
        return $collection;
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
