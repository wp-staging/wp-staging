<?php

namespace WPStaging\Staging\Interfaces;

interface StagingDatabaseDtoInterface
{
    public function setDatabasePrefix(string $databasePrefix);

    public function getDatabasePrefix(): string;

    public function setExcludedTables(array $excludedTables);

    public function getExcludedTables(): array;
}
