<?php

namespace WPStaging\Staging\Interfaces;

interface StagingOperationDtoInterface
{
    public function setJobType(string $jobType);

    public function getJobType(): string;

    public function setIncludedTables(array $tables);

    public function getIncludedTables(): array;

    public function setExcludedTables(array $tables);

    public function getExcludedTables(): array;

    public function setNonSiteTables(array $tables);

    public function getNonSiteTables(): array;

    public function setSelectedTables(array $tables);

    public function getSelectedTables(): array;

    public function setStagingTables(array $tables);

    public function getStagingTables(): array;

    public function addStagingTable(string $srcTable, string $destTable);

    public function setAllTablesExcluded(bool $excluded);

    public function getAllTablesExcluded(): bool;

    public function setStagingSitePath(string $path);

    public function getStagingSitePath(): string;

    public function setStagingSiteUrl(string $url);

    public function getStagingSiteUrl(): string;
}
