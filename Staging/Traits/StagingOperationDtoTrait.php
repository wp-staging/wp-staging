<?php

namespace WPStaging\Staging\Traits;

use WPStaging\Pro\Staging\Service\StagingSetup;

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

    /** @var float */
    private $excludeSizeGreaterThan = 8;

    /** @var array */
    private $excludeFileRules = [];

    /** @var array */
    private $excludeFolderRules = [];

    /** @var array */
    private $excludeExtensionRules = [];

    /** @var string */
    private $stagingSitePath = '';

    /** @var string */
    private $stagingSiteUrl = '';

    /**
     * Relative path to the uploads directory on the staging site.
     * @var string
     */
    private $stagingSiteUploads = '';

    /**
     * @var bool
     */
    private $isWpConfigExcluded = false;

    /**
     * @var bool
     */
    private $isKeepPermalinks = false;

    /**
     * @var bool
     */
    private $isRootFilesExcluded = false;

    /**
     * @var bool
     */
    private $isWpAdminExcluded = false;

    /**
     * @var bool
     */
    private $isWpIncludesExcluded = false;

    /**
     * @var bool
     */
    private $isWpContentExcluded = false;

    /**
     * @var bool
     */
    private $isPluginsExcluded = false;

    /**
     * @var bool
     */
    private $isMuPluginsExcluded = false;

    /**
     * @var bool
     */
    private $isThemesExcluded = false;

    /**
     * @var bool
     */
    private $isUploadsExcluded = false;

    /**
     * @var bool
     */
    private $isRootDirectoriesExcluded = false;

    /**
     * @var bool
     */
    private $isExternalDatabase = false;

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
            'destination' => $destTable,
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
     * @param float $excludeSizeGreaterThan
     * @return void
     */
    public function setExcludeSizeGreaterThan(float $excludeSizeGreaterThan)
    {
        $this->excludeSizeGreaterThan = $excludeSizeGreaterThan;
    }

    /**
     * @return float
     */
    public function getExcludeSizeGreaterThan(): float
    {
        return $this->excludeSizeGreaterThan;
    }

    /**
     * @param array $excludeFileRules
     * @return void
     */
    public function setExcludeFileRules(array $excludeFileRules)
    {
        $this->excludeFileRules = $excludeFileRules;
    }

    /**
     * @return array
     */
    public function getExcludeFileRules(): array
    {
        return $this->excludeFileRules;
    }

    /**
     * @param array $excludeFolderRules
     * @return void
     */
    public function setExcludeFolderRules(array $excludeFolderRules)
    {
        $this->excludeFolderRules = $excludeFolderRules;
    }

    /**
     * @return array
     */
    public function getExcludeFolderRules(): array
    {
        return $this->excludeFolderRules;
    }

    /**
     * @param array $excludeExtensionRules
     * @return void
     */
    public function setExcludeExtensionRules(array $excludeExtensionRules)
    {
        $this->excludeExtensionRules = $excludeExtensionRules;
    }

    /**
     * @return array
     */
    public function getExcludeExtensionRules(): array
    {
        return $this->excludeExtensionRules;
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

    /**
     * Set the relative path to the uploads directory on the staging site.
     * @param string $path
     * @return void
     */
    public function setStagingSiteUploads(string $path)
    {
        $this->stagingSiteUploads = $path;
    }

    /**
     * Get the relative path to the uploads directory on the staging site.
     * @return string
     */
    public function getStagingSiteUploads(): string
    {
        return $this->stagingSiteUploads;
    }

    /**
     * @param bool $excluded
     * @return void
     */
    public function setIsWpConfigExcluded(bool $excluded)
    {
        $this->isWpConfigExcluded = $excluded;
    }

    /**
     * @return bool
     */
    public function getIsWpConfigExcluded(): bool
    {
        return $this->isWpConfigExcluded;
    }

    /**
     * @param bool $isKeepPermalinks
     * @return void
     */
    public function setIsKeepPermalinks(bool $isKeepPermalinks)
    {
        $this->isKeepPermalinks = $isKeepPermalinks;
    }

    /**
     * @return bool
     */
    public function getIsKeepPermalinks(): bool
    {
        return $this->isKeepPermalinks;
    }

    /**
     * @param bool $isRootFilesExcluded
     * @return void
     */
    public function setIsRootFilesExcluded(bool $isRootFilesExcluded)
    {
        $this->isRootFilesExcluded = $isRootFilesExcluded;
    }

    /**
     * @return bool
     */
    public function getIsRootFilesExcluded(): bool
    {
        return $this->isRootFilesExcluded;
    }

    /**
     * @param bool $isWpAdminExcluded
     * @return void
     */
    public function setIsWpAdminExcluded(bool $isWpAdminExcluded)
    {
        $this->isWpAdminExcluded = $isWpAdminExcluded;
    }

    /**
     * @return bool
     */
    public function getIsWpAdminExcluded(): bool
    {
        return $this->isWpAdminExcluded;
    }

    /**
     * @param bool $isWpIncludesExcluded
     * @return void
     */
    public function setIsWpIncludesExcluded(bool $isWpIncludesExcluded)
    {
        $this->isWpIncludesExcluded = $isWpIncludesExcluded;
    }

    /**
     * @return bool
     */
    public function getIsWpIncludesExcluded(): bool
    {
        return $this->isWpIncludesExcluded;
    }

    /**
     * @param bool $isWpContentExcluded
     * @return void
     */
    public function setIsWpContentExcluded(bool $isWpContentExcluded)
    {
        $this->isWpContentExcluded = $isWpContentExcluded;
    }

    /**
     * @return bool
     */
    public function getIsWpContentExcluded(): bool
    {
        return $this->isWpContentExcluded;
    }

    /**
     * @param bool $isPluginsExcluded
     * @return void
     */
    public function setIsPluginsExcluded(bool $isPluginsExcluded)
    {
        $this->isPluginsExcluded = $isPluginsExcluded;
    }

    /**
     * @return bool
     */
    public function getIsPluginsExcluded(): bool
    {
        return $this->isPluginsExcluded;
    }

    /**
     * @param bool $isMuPluginsExcluded
     * @return void
     */
    public function setIsMuPluginsExcluded(bool $isMuPluginsExcluded)
    {
        $this->isMuPluginsExcluded = $isMuPluginsExcluded;
    }

    /**
     * @return bool
     */
    public function getIsMuPluginsExcluded(): bool
    {
        return $this->isMuPluginsExcluded;
    }

    /**
     * @param bool $isThemesExcluded
     * @return void
     */
    public function setIsThemesExcluded(bool $isThemesExcluded)
    {
        $this->isThemesExcluded = $isThemesExcluded;
    }

    /**
     * @return bool
     */
    public function getIsThemesExcluded(): bool
    {
        return $this->isThemesExcluded;
    }

    /**
     * @param bool $isUploadsExcluded
     * @return void
     */
    public function setIsUploadsExcluded(bool $isUploadsExcluded)
    {
        $this->isUploadsExcluded = $isUploadsExcluded;
    }

    /**
     * @return bool
     */
    public function getIsUploadsExcluded(): bool
    {
        return $this->isUploadsExcluded;
    }

    /**
     * @param bool $isRootDirectoriesExcluded
     * @return void
     */
    public function setIsRootDirectoriesExcluded(bool $isRootDirectoriesExcluded)
    {
        $this->isRootDirectoriesExcluded = $isRootDirectoriesExcluded;
    }

    /**
     * @return bool
     */
    public function getIsRootDirectoriesExcluded(): bool
    {
        return $this->isRootDirectoriesExcluded;
    }

    /**
     * @param bool $isExternalDatabase
     * @return void
     */
    public function setIsExternalDatabase(bool $isExternalDatabase)
    {
        $this->isExternalDatabase = $isExternalDatabase;
    }

    /**
     * @return bool
     */
    public function getIsExternalDatabase(): bool
    {
        return $this->isExternalDatabase;
    }

    public function getIsNewStagingSite(): bool
    {
        return $this->jobType === StagingSetup::JOB_NEW_STAGING_SITE;
    }

    public function getIsUpdateJob(): bool
    {
        return $this->jobType === StagingSetup::JOB_UPDATE;
    }

    public function getIsResetJob(): bool
    {
        return $this->jobType === StagingSetup::JOB_RESET;
    }

    public function getIsUpdateOrResetJob(): bool
    {
        return $this->getIsUpdateJob() || $this->getIsResetJob();
    }
}
