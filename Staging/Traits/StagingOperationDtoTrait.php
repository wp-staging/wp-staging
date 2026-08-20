<?php

namespace WPStaging\Staging\Traits;

use WPStaging\Staging\Service\StagingSetup;





trait StagingOperationDtoTrait
{



    private $jobType = '';





    private $includedTables = [];





    private $excludedTables = [];





    private $nonSiteTables = [];





    private $selectedTables = [];





    private $stagingTables = [];






    private $preserveTables = [];

 
    private $allTablesExcluded = false;

 
    private $extraDirectories = [];

 
    private $excludedDirectories = [];

 
    private $excludeSizeGreaterThan = 8;

 
    private $excludeFileRules = [];

 
    private $excludeFolderRules = [];

 
    private $excludeExtensionRules = [];

 
    private $stagingSitePath = '';

 
    private $stagingSiteUrl = '';





    private $stagingSiteUploads = '';




    private $isWpConfigExcluded = false;




    private $isKeepPermalinks = false;




    private $isRootFilesExcluded = false;




    private $isWpAdminExcluded = false;




    private $isWpIncludesExcluded = false;




    private $isWpContentExcluded = false;




    private $isPluginsExcluded = false;




    private $isMuPluginsExcluded = false;




    private $isThemesExcluded = false;




    private $isUploadsExcluded = false;




    private $isRootDirectoriesExcluded = false;




    private $isExternalDatabase = false;





    public function setJobType(string $jobType)
    {
        $this->jobType = $jobType;
    }




    public function getJobType(): string
    {
        return $this->jobType;
    }





    public function setIncludedTables(array $tables)
    {
        $this->includedTables = $tables;
    }




    public function getIncludedTables(): array
    {
        return $this->includedTables;
    }





    public function setExcludedTables(array $tables)
    {
        $this->excludedTables = $tables;
    }




    public function getExcludedTables(): array
    {
        return $this->excludedTables;
    }





    public function setNonSiteTables(array $tables)
    {
        $this->nonSiteTables = $tables;
    }




    public function getNonSiteTables(): array
    {
        return $this->nonSiteTables;
    }





    public function setSelectedTables(array $tables)
    {
        $this->selectedTables = $tables;
    }




    public function getSelectedTables(): array
    {
        return $this->selectedTables;
    }





    public function setStagingTables(array $tables)
    {
        $this->stagingTables = $tables;
    }

    public function getStagingTables(): array
    {
        return $this->stagingTables;
    }






    public function addStagingTable(string $srcTable, string $destTable)
    {
        $this->stagingTables[] = [
            'source'      => $srcTable,
            'destination' => $destTable,
        ];
    }





    public function setPreserveTables(array $tables)
    {
        $this->preserveTables = $tables;
    }

    public function getPreserveTables(): array
    {
        return $this->preserveTables;
    }






    public function addPreserveTable(string $destTable, string $backupTable)
    {
        $this->preserveTables[] = [
            'destination' => $destTable,
            'backup'      => $backupTable,
        ];
    }





    public function setAllTablesExcluded(bool $allTablesExcluded)
    {
        $this->allTablesExcluded = $allTablesExcluded;
    }




    public function getAllTablesExcluded(): bool
    {
        return $this->allTablesExcluded;
    }





    public function setExtraDirectories(array $extraDirectories)
    {
        $this->extraDirectories = $extraDirectories;
    }




    public function getExtraDirectories(): array
    {
        return $this->extraDirectories;
    }





    public function setExcludedDirectories(array $excludedDirectories)
    {
        $this->excludedDirectories = $excludedDirectories;
    }




    public function getExcludedDirectories(): array
    {
        return $this->excludedDirectories;
    }





    public function setExcludeSizeGreaterThan(float $excludeSizeGreaterThan)
    {
        $this->excludeSizeGreaterThan = $excludeSizeGreaterThan;
    }




    public function getExcludeSizeGreaterThan(): float
    {
        return $this->excludeSizeGreaterThan;
    }





    public function setExcludeFileRules(array $excludeFileRules)
    {
        $this->excludeFileRules = $excludeFileRules;
    }




    public function getExcludeFileRules(): array
    {
        return $this->excludeFileRules;
    }





    public function setExcludeFolderRules(array $excludeFolderRules)
    {
        $this->excludeFolderRules = $excludeFolderRules;
    }




    public function getExcludeFolderRules(): array
    {
        return $this->excludeFolderRules;
    }





    public function setExcludeExtensionRules(array $excludeExtensionRules)
    {
        $this->excludeExtensionRules = $excludeExtensionRules;
    }




    public function getExcludeExtensionRules(): array
    {
        return $this->excludeExtensionRules;
    }





    public function setStagingSitePath(string $path)
    {
        $this->stagingSitePath = $path;
    }




    public function getStagingSitePath(): string
    {
        return $this->stagingSitePath;
    }





    public function setStagingSiteUrl(string $url)
    {
        $this->stagingSiteUrl = $url;
    }




    public function getStagingSiteUrl(): string
    {
        return $this->stagingSiteUrl;
    }






    public function setStagingSiteUploads(string $path)
    {
        $this->stagingSiteUploads = $path;
    }





    public function getStagingSiteUploads(): string
    {
        return $this->stagingSiteUploads;
    }





    public function setIsWpConfigExcluded(bool $excluded)
    {
        $this->isWpConfigExcluded = $excluded;
    }




    public function getIsWpConfigExcluded(): bool
    {
        return $this->isWpConfigExcluded;
    }





    public function setIsKeepPermalinks(bool $isKeepPermalinks)
    {
        $this->isKeepPermalinks = $isKeepPermalinks;
    }




    public function getIsKeepPermalinks(): bool
    {
        return $this->isKeepPermalinks;
    }





    public function setIsRootFilesExcluded(bool $isRootFilesExcluded)
    {
        $this->isRootFilesExcluded = $isRootFilesExcluded;
    }




    public function getIsRootFilesExcluded(): bool
    {
        return $this->isRootFilesExcluded;
    }





    public function setIsWpAdminExcluded(bool $isWpAdminExcluded)
    {
        $this->isWpAdminExcluded = $isWpAdminExcluded;
    }




    public function getIsWpAdminExcluded(): bool
    {
        return $this->isWpAdminExcluded;
    }





    public function setIsWpIncludesExcluded(bool $isWpIncludesExcluded)
    {
        $this->isWpIncludesExcluded = $isWpIncludesExcluded;
    }




    public function getIsWpIncludesExcluded(): bool
    {
        return $this->isWpIncludesExcluded;
    }





    public function setIsWpContentExcluded(bool $isWpContentExcluded)
    {
        $this->isWpContentExcluded = $isWpContentExcluded;
    }




    public function getIsWpContentExcluded(): bool
    {
        return $this->isWpContentExcluded;
    }





    public function setIsPluginsExcluded(bool $isPluginsExcluded)
    {
        $this->isPluginsExcluded = $isPluginsExcluded;
    }




    public function getIsPluginsExcluded(): bool
    {
        return $this->isPluginsExcluded;
    }





    public function setIsMuPluginsExcluded(bool $isMuPluginsExcluded)
    {
        $this->isMuPluginsExcluded = $isMuPluginsExcluded;
    }




    public function getIsMuPluginsExcluded(): bool
    {
        return $this->isMuPluginsExcluded;
    }





    public function setIsThemesExcluded(bool $isThemesExcluded)
    {
        $this->isThemesExcluded = $isThemesExcluded;
    }




    public function getIsThemesExcluded(): bool
    {
        return $this->isThemesExcluded;
    }





    public function setIsUploadsExcluded(bool $isUploadsExcluded)
    {
        $this->isUploadsExcluded = $isUploadsExcluded;
    }




    public function getIsUploadsExcluded(): bool
    {
        return $this->isUploadsExcluded;
    }





    public function setIsRootDirectoriesExcluded(bool $isRootDirectoriesExcluded)
    {
        $this->isRootDirectoriesExcluded = $isRootDirectoriesExcluded;
    }




    public function getIsRootDirectoriesExcluded(): bool
    {
        return $this->isRootDirectoriesExcluded;
    }





    public function setIsExternalDatabase(bool $isExternalDatabase)
    {
        $this->isExternalDatabase = $isExternalDatabase;
    }




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
