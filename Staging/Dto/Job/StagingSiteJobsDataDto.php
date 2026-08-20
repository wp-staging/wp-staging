<?php

namespace WPStaging\Staging\Dto\Job;

use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\Traits\FilesystemScannerDtoTrait;
use WPStaging\Framework\Job\Interfaces\FilesystemScannerDtoInterface;
use WPStaging\Staging\Interfaces\AdvanceStagingOptionsInterface;
use WPStaging\Staging\Interfaces\StagingDatabaseDtoInterface;
use WPStaging\Staging\Interfaces\StagingNetworkDtoInterface;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;
use WPStaging\Staging\Interfaces\StagingSiteDtoInterface;
use WPStaging\Staging\Traits\StagingDatabaseDtoTrait;
use WPStaging\Staging\Traits\StagingNetworkDtoTrait;
use WPStaging\Staging\Traits\StagingOperationDtoTrait;
use WPStaging\Staging\Traits\WithAdvanceStagingOptions;
use WPStaging\Staging\Traits\WithStagingSiteDto;




class StagingSiteJobsDataDto extends JobDataDto implements StagingDatabaseDtoInterface, StagingSiteDtoInterface, StagingOperationDtoInterface, AdvanceStagingOptionsInterface, FilesystemScannerDtoInterface, StagingNetworkDtoInterface
{
    use FilesystemScannerDtoTrait, WithAdvanceStagingOptions, WithStagingSiteDto, StagingOperationDtoTrait, StagingDatabaseDtoTrait, StagingNetworkDtoTrait {
        StagingOperationDtoTrait::setExcludedTables insteadof StagingDatabaseDtoTrait;
        StagingOperationDtoTrait::getExcludedTables insteadof StagingDatabaseDtoTrait;
        WithAdvanceStagingOptions::getDatabasePrefix insteadof StagingDatabaseDtoTrait;
        WithAdvanceStagingOptions::setDatabasePrefix insteadof StagingDatabaseDtoTrait;
        WithAdvanceStagingOptions::setTmpExcludedFullPaths insteadof FilesystemScannerDtoTrait;
        WithAdvanceStagingOptions::getTmpExcludedFullPaths insteadof FilesystemScannerDtoTrait;
    }

 
    private $cloneId = '';

 
    private $name = '';

 
    private $directoryName = '';

 
    private $stagingEngine = 'next_gen';





    private $isCleanPluginsThemes = false;





    private $isCleanUploads = false;





    private $isPluginsCleanupDone = false;





    private $isThemesCleanupDone = false;





    private $isUploadsCleanupDone = false;





    public function setCloneId(string $cloneId)
    {
        $this->cloneId = $cloneId;
    }




    public function getCloneId(): string
    {
        return $this->cloneId;
    }





    public function setName(string $name)
    {
        $this->name = $name;
    }




    public function getName(): string
    {
        return $this->name;
    }





    public function setDirectoryName(string $directoryName)
    {
        $this->directoryName = $directoryName;
    }




    public function getDirectoryName(): string
    {
        return $this->directoryName;
    }





    public function setStagingEngine(string $stagingEngine)
    {
        $this->stagingEngine = $stagingEngine;
    }




    public function getStagingEngine(): string
    {
        return $this->stagingEngine;
    }





    public function setIsCleanPluginsThemes(bool $cleanPluginsThemes)
    {
        $this->isCleanPluginsThemes = $cleanPluginsThemes;
    }




    public function getIsCleanPluginsThemes(): bool
    {
        return $this->isCleanPluginsThemes;
    }





    public function setIsCleanUploads(bool $cleanUploads)
    {
        $this->isCleanUploads = $cleanUploads;
    }




    public function getIsCleanUploads(): bool
    {
        return $this->isCleanUploads;
    }





    public function setIsPluginsCleanupDone(bool $isPluginsCleanupDone)
    {
        $this->isPluginsCleanupDone = $isPluginsCleanupDone;
    }




    public function getIsPluginsCleanupDone(): bool
    {
        return $this->isPluginsCleanupDone;
    }





    public function setIsThemesCleanupDone(bool $isThemesCleanupDone)
    {
        $this->isThemesCleanupDone = $isThemesCleanupDone;
    }




    public function getIsThemesCleanupDone(): bool
    {
        return $this->isThemesCleanupDone;
    }





    public function setIsUploadsCleanupDone(bool $isUploadsCleanupDone)
    {
        $this->isUploadsCleanupDone = $isUploadsCleanupDone;
    }




    public function getIsUploadsCleanupDone(): bool
    {
        return $this->isUploadsCleanupDone;
    }
}
