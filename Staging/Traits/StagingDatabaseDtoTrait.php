<?php

namespace WPStaging\Staging\Traits;

trait StagingDatabaseDtoTrait
{
 
    private $databasePrefix = '';

 
    private $excludedTables = [];





    public function setDatabasePrefix(string $databasePrefix)
    {
        $this->databasePrefix = $databasePrefix;
    }




    public function getDatabasePrefix(): string
    {
        return $this->databasePrefix;
    }





    public function setExcludedTables(array $excludedTables)
    {
        $this->excludedTables = $excludedTables;
    }




    public function getExcludedTables(): array
    {
        return $this->excludedTables;
    }
}
