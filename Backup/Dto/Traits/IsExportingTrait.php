<?php

namespace WPStaging\Backup\Dto\Traits;

/**
 * Not changing `Export` term to `Backup` here for backward compatibility
 * Otherwise old backup may not work properly because these terms are used in backup metadata
 * @todo Change this later to term `backup*` but add a compatibility layer on it to make it compatible with old backups
 */
trait IsExportingTrait
{
    /** @var bool */
    private $isExportingPlugins = false;

    /** @var bool */
    private $isExportingMuPlugins = false;

    /** @var bool */
    private $isExportingThemes = false;

    /** @var bool */
    private $isExportingUploads = false;

    /** @var bool */
    private $isExportingOtherWpContentFiles = false;

    /** @var bool */
    private $isExportingOtherWpRootFiles = false;

    /** @var array */
    private $backupExcludedDirectories = [];

    /** @var bool */
    private $isExportingDatabase = false;

    /**
     * @return bool
     */
    public function getIsExportingPlugins()
    {
        return (bool)$this->isExportingPlugins;
    }

    /**
     * @param bool $isExportingPlugins
     */
    public function setIsExportingPlugins($isExportingPlugins)
    {
        $this->isExportingPlugins = $isExportingPlugins === true || $isExportingPlugins === 'true';
    }

    /**
     * @return bool
     */
    public function getIsExportingMuPlugins()
    {
        return (bool)$this->isExportingMuPlugins;
    }

    /**
     * @param bool $isExportingMuPlugins
     */
    public function setIsExportingMuPlugins($isExportingMuPlugins)
    {
        $this->isExportingMuPlugins = $isExportingMuPlugins === true || $isExportingMuPlugins === 'true';
    }

    /**
     * @return bool
     */
    public function getIsExportingThemes()
    {
        return (bool)$this->isExportingThemes;
    }

    /**
     * @param bool $isExportingThemes
     */
    public function setIsExportingThemes($isExportingThemes)
    {
        $this->isExportingThemes = $isExportingThemes === true || $isExportingThemes === 'true';
    }

    /**
     * @return bool
     */
    public function getIsExportingUploads()
    {
        return (bool)$this->isExportingUploads;
    }

    /**
     * @param bool $isExportingUploads
     */
    public function setIsExportingUploads($isExportingUploads)
    {
        $this->isExportingUploads = $isExportingUploads === true || $isExportingUploads === 'true';
    }

    /**
     * @return bool
     */
    public function getIsExportingOtherWpContentFiles()
    {
        return (bool)$this->isExportingOtherWpContentFiles;
    }

    /**
     * @param bool $isExportingOtherWpContentFiles
     */
    public function setIsExportingOtherWpContentFiles($isExportingOtherWpContentFiles)
    {
        $this->isExportingOtherWpContentFiles = $isExportingOtherWpContentFiles === true || $isExportingOtherWpContentFiles === 'true';
    }

    /**
     * @return bool
     */
    public function getIsExportingOtherWpRootFiles(): bool
    {
        return (bool)$this->isExportingOtherWpRootFiles;
    }

    /**
     * @param bool $isExportingOtherWpRootFiles
     *
     * @return void
     */
    public function setIsExportingOtherWpRootFiles(bool $isExportingOtherWpRootFiles)
    {
        $this->isExportingOtherWpRootFiles = $isExportingOtherWpRootFiles === true || $isExportingOtherWpRootFiles === 'true';
    }

    /**
     * @return array
     */
    public function getBackupExcludedDirectories(): array
    {
        return $this->backupExcludedDirectories;
    }

    /**
     * @param array $backupExcludedDirectories
     *
     * @return void
     */
    public function setBackupExcludedDirectories(array $backupExcludedDirectories)
    {
        $this->backupExcludedDirectories = $backupExcludedDirectories;
    }

    /**
     * @return bool
     */
    public function getIsExportingDatabase()
    {
        return (bool)$this->isExportingDatabase;
    }

    /**
     * @param bool $isExportingDatabase
     */
    public function setIsExportingDatabase($isExportingDatabase)
    {
        $this->isExportingDatabase = $isExportingDatabase === true || $isExportingDatabase === 'true';
    }
}
