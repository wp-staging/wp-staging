<?php

namespace WPStaging\Backup\Dto\Traits;






trait IsExportingTrait
{
 
    private $isExportingPlugins = false;

 
    private $isExportingMuPlugins = false;

 
    private $isExportingThemes = false;

 
    private $isExportingUploads = false;

 
    private $isExportingOtherWpContentFiles = false;

 
    private $isExportingOtherWpRootFiles = false;

 
    private $backupExcludedDirectories = [];

 
    private $isExportingDatabase = false;




    public function getIsExportingPlugins()
    {
        return (bool)$this->isExportingPlugins;
    }




    public function setIsExportingPlugins($isExportingPlugins)
    {
        $this->isExportingPlugins = $isExportingPlugins === true || $isExportingPlugins === 'true';
    }




    public function getIsExportingMuPlugins()
    {
        return (bool)$this->isExportingMuPlugins;
    }




    public function setIsExportingMuPlugins($isExportingMuPlugins)
    {
        $this->isExportingMuPlugins = $isExportingMuPlugins === true || $isExportingMuPlugins === 'true';
    }




    public function getIsExportingThemes()
    {
        return (bool)$this->isExportingThemes;
    }




    public function setIsExportingThemes($isExportingThemes)
    {
        $this->isExportingThemes = $isExportingThemes === true || $isExportingThemes === 'true';
    }




    public function getIsExportingUploads()
    {
        return (bool)$this->isExportingUploads;
    }




    public function setIsExportingUploads($isExportingUploads)
    {
        $this->isExportingUploads = $isExportingUploads === true || $isExportingUploads === 'true';
    }




    public function getIsExportingOtherWpContentFiles()
    {
        return (bool)$this->isExportingOtherWpContentFiles;
    }




    public function setIsExportingOtherWpContentFiles($isExportingOtherWpContentFiles)
    {
        $this->isExportingOtherWpContentFiles = $isExportingOtherWpContentFiles === true || $isExportingOtherWpContentFiles === 'true';
    }




    public function getIsExportingOtherWpRootFiles(): bool
    {
        return (bool)$this->isExportingOtherWpRootFiles;
    }






    public function setIsExportingOtherWpRootFiles(bool $isExportingOtherWpRootFiles)
    {
        $this->isExportingOtherWpRootFiles = $isExportingOtherWpRootFiles === true || $isExportingOtherWpRootFiles === 'true';
    }




    public function getBackupExcludedDirectories(): array
    {
        return $this->backupExcludedDirectories;
    }






    public function setBackupExcludedDirectories(array $backupExcludedDirectories)
    {
        $this->backupExcludedDirectories = $backupExcludedDirectories;
    }




    public function getIsExportingDatabase()
    {
        return (bool)$this->isExportingDatabase;
    }




    public function setIsExportingDatabase($isExportingDatabase)
    {
        $this->isExportingDatabase = $isExportingDatabase === true || $isExportingDatabase === 'true';
    }
}
