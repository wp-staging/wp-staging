<?php

namespace WPStaging\Framework\Adapter\Database;

use SplObjectStorage;

interface InterfaceDatabase
{
    /**
     * @return object
     */
    public function getClient();

    /**
     * @param string $sql
     * @param array $conditions
     *
     * @return SplObjectStorage|null
     */
    public function find($sql, array $conditions = []);

    /**
     * @param string $sql
     * @param array $conditions
     *
     * @return object|null
     */
    public function findOne($sql, array $conditions = []);

    /**
     * @param DatabaseQueryDto $queryDto
     *
     * @return bool
     */
    public function insert(DatabaseQueryDto $queryDto);

    /**
     * @param DatabaseQueryDto $queryDto
     *
     * @return bool
     */
    public function update(DatabaseQueryDto $queryDto);

    /**
     * @param string $tableName
     * @param array $condition
     *
     * @return bool|int
     */
    public function delete($tableName, array $condition = []);

    /**
     * @param string $sql
     *
     * @return bool|int
     */
    public function exec($sql);
}
