<?php

namespace WPStaging\Framework\Adapter\Database;

use SplObjectStorage;
use wpdb;

class WpDbAdapter extends AbstractDatabase
{
 
    private $client;

    public function __construct(wpdb $wpdb)
    {
        $this->client = $wpdb;
    }




    public function getClient()
    {
        return $this->client;
    }




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




    public function findOne($sql, array $conditions = [])
    {
        $records = $this->getResults($sql, $conditions);

        if (!$records) {
            return null;
        }

        return reset($records);
    }




    public function insert(DatabaseQueryDto $queryDto)
    {
 
        return false;
    }




    public function update(DatabaseQueryDto $queryDto)
    {
 
        return false;
    }




    public function delete($tableName, array $condition = [])
    {
 
        return false;
    }




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
