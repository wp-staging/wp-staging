<?php

namespace WPStaging\Framework\Adapter\Database;

use SplObjectStorage;

interface DatabaseAdapterInterface
{



    public function getClient();







    public function find($sql, array $conditions = []);







    public function findOne($sql, array $conditions = []);






    public function insert(DatabaseQueryDto $queryDto);






    public function update(DatabaseQueryDto $queryDto);







    public function delete($tableName, array $condition = []);






    public function exec($sql);
}
