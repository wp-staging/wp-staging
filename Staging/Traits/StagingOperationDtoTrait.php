<?php

namespace WPStaging\Staging\Traits;

/**
 * Trait StagingOperationDtoTrait
 * This trait is common between staging site creation, update and reset
 */
trait StagingOperationDtoTrait
{
    /**
     * @var string
     */
    private $jobType = '';

    /**
     * Tables that starts with current site prefix
     * @var array
     */
    private $includedTables = [];

    /**
     * Exluded Tables that start with current site prefix
     * @var array
     */
    private $excludedTables = [];

    /**
     * Tables that do not start with current site prefix
     * @var array
     */
    private $nonSiteTables = [];

    /**
     * Tables that are to be operated
     * @var array
     */
    private $selectedTables = [];

    /**
     * Tables that are to be operated
     * @var array
     */
    private $stagingTables = [];

    /** @var bool */
    private $allTablesExcluded = false;

    /** @var array */
    private $extraDirectories = [];

    /** @var array */
    private $excludedDirectories = [];

    /** @var array */
    private $excludeGlobRules = [];

    /**
     * @param string $jobType
     * @return void
     */
    public function setJobType(string $jobType)
    {
        $this->jobType = $jobType;
    }

    /**
     * @return string
     */
    public function getJobType(): string
    {
        return $this->jobType;
    }

    /**
     * @param array $tables
     * @return void
     */
    public function setIncludedTables(array $tables)
    {
        $this->includedTables = $tables;
    }

    /**
     * @return array
     */
    public function getIncludedTables(): array
    {
        return $this->includedTables;
    }

    /**
     * @param array $tables
     * @return void
     */
    public function setExcludedTables(array $tables)
    {
        $this->excludedTables = $tables;
    }

    /**
     * @return array
     */
    public function getExcludedTables(): array
    {
        return $this->excludedTables;
    }

    /**
     * @param array $tables
     * @return void
     */
    public function setNonSiteTables(array $tables)
    {
        $this->nonSiteTables = $tables;
    }

    /**
     * @return array
     */
    public function getNonSiteTables(): array
    {
        return $this->nonSiteTables;
    }

    /**
     * @param array $tables
     * @return void
     */
    public function setSelectedTables(array $tables)
    {
        $this->selectedTables = $tables;
    }

    /**
     * @return array
     */
    public function getSelectedTables(): array
    {
        return $this->selectedTables;
    }

    /**
     * @param array $tables
     * @return void
     */
    public function setStagingTables(array $tables)
    {
        $this->stagingTables = $tables;
    }

    public function getStagingTables(): array
    {
        return $this->stagingTables;
    }

    /**
     * @param string $srcTable
     * @param string $destTable
     * @return void
     */
    public function addStagingTable(string $srcTable, string $destTable)
    {
        $this->stagingTables[] = [
            'source'      => $srcTable,
            'destination' => $destTable
        ];
    }

    /**
     * @param bool $allTablesExcluded
     * @return void
     */
    public function setAllTablesExcluded(bool $allTablesExcluded)
    {
        $this->allTablesExcluded = $allTablesExcluded;
    }

    /**
     * @return bool
     */
    public function getAllTablesExcluded(): bool
    {
        return $this->allTablesExcluded;
    }

    /**
     * @param array $extraDirectories
     * @return void
     */
    public function setExtraDirectories(array $extraDirectories)
    {
        $this->extraDirectories = $extraDirectories;
    }

    /**
     * @return array
     */
    public function getExtraDirectories(): array
    {
        return $this->extraDirectories;
    }

    /**
     * @param array $excludedDirectories
     * @return void
     */
    public function setExcludedDirectories(array $excludedDirectories)
    {
        $this->excludedDirectories = $excludedDirectories;
    }

    /**
     * @return array
     */
    public function getExcludedDirectories(): array
    {
        return $this->excludedDirectories;
    }

    /**
     * @param array $excludeGlobRules
     * @return void
     */
    public function setExcludeGlobRules(array $excludeGlobRules)
    {
        $this->excludeGlobRules = $excludeGlobRules;
    }

    /**
     * @return array
     */
    public function getExcludeGlobRules(): array
    {
        return $this->excludeGlobRules;
    }

    /**
     * @param string $path
     * @return void
     */
    public function setStagingSitePath(string $path)
    {
        $this->stagingSitePath = $path;
    }

    /**
     * @return string
     */
    public function getStagingSitePath(): string
    {
        return $this->stagingSitePath;
    }

    /**
     * @param string $url
     * @return void
     */
    public function setStagingSiteUrl(string $url)
    {
        $this->stagingSiteUrl = $url;
    }

    /**
     * @return string
     */
    public function getStagingSiteUrl(): string
    {
        return $this->stagingSiteUrl;
    }
}
