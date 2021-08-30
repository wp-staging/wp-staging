<?php

namespace WPStaging\Framework\Adapter\Database;

use SplObjectStorage;
use wpdb;

class WpDbAdapter extends AbstractDatabase
{
    /** @var wpdb  */
    private $client;

    public function __construct(wpdb $wpdb)
    {
        $this->client = $wpdb;
    }

    /**
     * @inheritDoc
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @inheritDoc
     */
    public function find($sql, array $conditions = [])
    {
        $records = $this->getResults($sql, $conditions);

        if (!$records) {
            return null;
        }

        $collection = new SplObjectStorage();
        foreach ($records as $record) {
            $collection->attach($record);
        }

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function findOne($sql, array $conditions = [])
    {
        $records = $this->getResults($sql, $conditions);

        if (!$records) {
            return null;
        }

        return reset($records);
    }

    /**
     * @inheritDoc
     */
    public function insert(DatabaseQueryDto $queryDto)
    {
        // TODO: Implement insert() method.
        return false;
    }

    /**
     * @inheritDoc
     */
    public function update(DatabaseQueryDto $queryDto)
    {
        // TODO: Implement update() method.
        return false;
    }

    /**
     * @inheritDoc
     */
    public function delete($tableName, array $condition = [])
    {
        // TODO: Implement delete() method.
        return false;
    }

    /**
     * @inheritDoc
     */
    public function exec($sql)
    {
        return $this->client->query($sql);
    }

    private function getResults($sql, array $conditions = [])
    {
        if (!$conditions) {
            $response = $this->client->get_results($sql);
        } else {
            $response = $this->client->get_results($this->client->prepare($sql, $conditions));
        }

        return $response ? array_values((array)$response) : null;
    }
}
