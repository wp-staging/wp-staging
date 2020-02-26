<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x type-hints & return types

namespace WPStaging\Manager\Database;

use WPStaging\Service\Adapter\Database;
use WPStaging\Service\Collection\Collection;

class TableManager
{
    /** @var Database */
    private $database;

    public function __construct()
    {
        $this->database = new Database;
    }

    /**
     * @param string|null $prefix
     *
     * @return TableDto[]|Collection|null
     */
    public function findStartsWith($prefix = null)
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
     * @param string|null $prefix
     * @return string
     */
    private function provideSqlPrefix($prefix = null)
    {
        return $this->database->provideSqlPrefix($prefix);
    }
}
