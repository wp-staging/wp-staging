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

    public function setExcludedDirectories(array $directories);

    public function getExcludedDirectories(): array;

    public function setExtraDirectories(array $directories);

    public function getExtraDirectories(): array;

    public function setExcludeSizeGreaterThan(float $size);

    public function getExcludeSizeGreaterThan(): float;

    public function setExcludeFileRules(array $rules);

    public function getExcludeFileRules(): array;

    public function setExcludeFolderRules(array $rules);

    public function getExcludeFolderRules(): array;

    public function setExcludeExtensionRules(array $rules);

    public function getExcludeExtensionRules(): array;

    public function setStagingSitePath(string $path);

    public function getStagingSitePath(): string;

    public function setStagingSiteUrl(string $url);

    public function getStagingSiteUrl(): string;

    public function setStagingSiteUploads(string $path);

    public function getStagingSiteUploads(): string;

    public function setIsWpConfigExcluded(bool $excluded);

    public function getIsWpConfigExcluded(): bool;

    public function setIsKeepPermalinks(bool $isKeepPermalinks);

    public function getIsKeepPermalinks(): bool;

    public function setIsRootFilesExcluded(bool $excluded);

    public function getIsRootFilesExcluded(): bool;

    public function setIsWpAdminExcluded(bool $excluded);

    public function getIsWpAdminExcluded(): bool;

    public function setIsWpContentExcluded(bool $excluded);

    public function getIsWpContentExcluded(): bool;

    public function setIsWpIncludesExcluded(bool $excluded);

    public function getIsWpIncludesExcluded(): bool;

    public function setIsPluginsExcluded(bool $excluded);

    public function getIsPluginsExcluded(): bool;

    public function setIsMuPluginsExcluded(bool $excluded);

    public function getIsMuPluginsExcluded(): bool;

    public function setIsThemesExcluded(bool $excluded);

    public function getIsThemesExcluded(): bool;

    public function setIsUploadsExcluded(bool $excluded);

    public function getIsUploadsExcluded(): bool;

    public function setIsRootDirectoriesExcluded(bool $excluded);

    public function getIsRootDirectoriesExcluded(): bool;

    public function getIsNewStagingSite(): bool;

    public function getIsUpdateJob(): bool;

    public function getIsResetJob(): bool;

    public function getIsUpdateOrResetJob(): bool;
}
