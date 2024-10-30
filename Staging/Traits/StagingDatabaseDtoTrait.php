<?php

namespace WPStaging\Staging\Traits;

trait StagingDatabaseDtoTrait
{
    /** @var string */
    private $databasePrefix = '';

    /** @var string[] */
    private $excludedTables = [];

    /**
     * @param string $databasePrefix
     * @return void
     */
    public function setDatabasePrefix(string $databasePrefix)
    {
        $this->databasePrefix = $databasePrefix;
    }

    /**
     * @return string
     */
    public function getDatabasePrefix(): string
    {
        return $this->databasePrefix;
    }

    /**
     * @param string[] $excludedTables
     * @return void
     */
    public function setExcludedTables(array $excludedTables)
    {
        $this->excludedTables = $excludedTables;
    }

    /**
     * @return string[]
     */
    public function getExcludedTables(): array
    {
        return $this->excludedTables;
    }
}
