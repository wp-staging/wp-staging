<?php

namespace WPStaging\Backup\Dto\Job;

use WPStaging\Backup\Dto\Interfaces\RemoteUploadDtoInterface;
use WPStaging\Backup\Dto\Traits\IsExportingTrait;
use WPStaging\Backup\Dto\Traits\IsExcludingTrait;
use WPStaging\Backup\Dto\Traits\RemoteUploadTrait;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\ZlibCompressor;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\JobDataDto;

class JobBackupDataDto extends JobDataDto implements RemoteUploadDtoInterface
{
    use IsExportingTrait;
    use IsExcludingTrait;
    use RemoteUploadTrait;

    /** @var string|null */
    private $name;

    /** @var array */
    private $excludedDirectories = [];

    /** @var int */
    private $totalDirectories;

    /** @var int The number of files in the backup index */
    private $totalFiles;

    /** @var array The number of files in backup parts */
    private $filesInParts = [];

    /** @var int The number of files the FilesystemScanner discovered */
    private $discoveredFiles;

    /** @var array The number of files the FilesystemScanner discovered in themes,plugins,muplugins,uploads,others */
    private $discoveredFilesArray = [];

    /** @var string */
    private $databaseFile;

    /**
     * @var int If a file couldn't be processed in a single request,
     *          this property holds how many bytes were written thus far
     *          so that the backup can start writing from this byte onwards.
     */
    private $fileBeingBackupWrittenBytes;

    /**
     * @var int If header of a file was written but it couldn't be processed in single requests,
     */
    private $currentWrittenFileHeaderBytes = 0;

    /** @var int */
    private $totalRowsBackup;

    /** @var int */
    private $tableRowsOffset = 0;

    /** @var int */
    private $totalRowsOfTableBeingBackup = 0;

    /** @var int reset to PHP_INT_MIN for each table */
    private $lastInsertId;

    /** @var array */
    private $tablesToBackup = [];

    /** @var array */
    private $nonWpTables = [];

    /** @var int The size in bytes of the database in this backup */
    private $databaseFileSize = 0;

    /** @var int The size in bytes of the filesystem in this backup */
    private $filesystemSize = 0;

    /** @var int The number of requests that the Discovering Files task has executed so far */
    private $discoveringFilesRequests = 0;

    /** @var string The cron to repeat this backup, if scheduled. */
    private $scheduleRecurrence;

    /** @var array The hour and minute to repeat this backup, if scheduled. */
    private $scheduleTime = [];

    /** @var int How many backups to keep, if scheduled. */
    private $scheduleRotation;

    /** @var string The absolute path to this .wpstg file */
    private $backupFilePath;

    /** @var string If set, this backup was created as part of this schedule ID. */
    private $scheduleId;

    /** @var bool Should the backup be validated for each file once the backup is created. */
    private $isValidateBackupFiles = false;

    /** @var bool Should this scheduled backup be created right now. Matters only if this backup is repeated on schedule */
    private $isCreateScheduleBackupNow;

    /** @var bool Should the backup be created in background? */
    private $isCreateBackupInBackground;

    /** @var array Site selected to backup */
    private $sitesToBackup = [];

    /**
    * is network subsite or network main site backup
    * @var bool */
    private $isNetworkSiteBackup = false;

    /**
     * @var array
     * Store max index for each category
     */
    private $fileBackupIndices = [];

    /** @var int */
    private $maxDbPartIndex = 0;

    /** @var int */
    private $currentMultipartFileInfoIndex = 0;

    /** @var array */
    private $multipartFilesInfo = [];

    /**
     * @var array<string, int>
     * Store total size for each category
     */
    private $categorySizes = [];

    /** @var string */
    private $backupType;

    /** @var int */
    private $subsiteBlogId;

    /** @var int */
    private $filePartIndex = 0;

    /** @var bool */
    private $isContaining2GBFile = false;

    /** @var int */
    private $fileAppendTimeLimit = 10;

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Hydrated dynamically.
     *
     * @param string|null $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return array|null
     */
    public function getExcludedDirectories()
    {
        return (array)$this->excludedDirectories;
    }

    public function setExcludedDirectories(array $excludedDirectories = [])
    {
        $this->excludedDirectories = $excludedDirectories;
    }

    /**
     * @return int
     */
    public function getTotalDirectories()
    {
        return $this->totalDirectories;
    }

    /**
     * @param int $totalDirectories
     */
    public function setTotalDirectories($totalDirectories)
    {
        $this->totalDirectories = $totalDirectories;
    }

    /**
     * @return int
     */
    public function getTotalFiles()
    {
        return $this->totalFiles;
    }

    /**
     * @param int $totalFiles
     */
    public function setTotalFiles($totalFiles)
    {
        $this->totalFiles = $totalFiles;
    }

    /**
     * @return int
     */
    public function getDiscoveredFiles()
    {
        return $this->discoveredFiles;
    }

    /**
     * @param int $discoveredFiles
     */
    public function setDiscoveredFiles($discoveredFiles)
    {
        $this->discoveredFiles = $discoveredFiles;
    }

    /**
     * @return string
     */
    public function getDatabaseFile()
    {
        return $this->databaseFile;
    }

    /**
     * @param string $databaseFile
     */
    public function setDatabaseFile($databaseFile)
    {
        $this->databaseFile = $databaseFile;
    }

    /**
     * @return int
     */
    public function getTableRowsOffset()
    {
        return (int)$this->tableRowsOffset;
    }

    /**
     * @param int $tableRowsOffset
     */
    public function setTableRowsOffset($tableRowsOffset)
    {
        $this->tableRowsOffset = (int)$tableRowsOffset;
    }

    /**
     * @return int
     */
    public function getTotalRowsBackup()
    {
        return (int)$this->totalRowsBackup;
    }

    /**
     * @param int $totalRowsBackup
     */
    public function setTotalRowsBackup($totalRowsBackup)
    {
        $this->totalRowsBackup = (int)$totalRowsBackup;
    }

    /**
     * @return int
     */
    public function getFileBeingBackupWrittenBytes()
    {
        return (int)$this->fileBeingBackupWrittenBytes;
    }

    /**
     * @param int $fileBeingBackupWrittenBytes
     */
    public function setFileBeingBackupWrittenBytes($fileBeingBackupWrittenBytes)
    {
        $this->fileBeingBackupWrittenBytes = (int)$fileBeingBackupWrittenBytes;
    }

    /**
     * @return array
     */
    public function getTablesToBackup()
    {
        return (array)$this->tablesToBackup;
    }

    /**
     * @param array $tablesToBackup
     */
    public function setTablesToBackup($tablesToBackup)
    {
        $this->tablesToBackup = (array)$tablesToBackup;
    }

    /**
     * @return array
     */
    public function getNonWpTables()
    {
        return (array)$this->nonWpTables;
    }

    /**
     * @param array $nonWpTables
     */
    public function setNonWpTables($nonWpTables)
    {
        $this->nonWpTables = (array)$nonWpTables;
    }

    /**
     * @return int
     */
    public function getTotalRowsOfTableBeingBackup()
    {
        return (int)$this->totalRowsOfTableBeingBackup;
    }

    /**
     * @param int $totalRowsOfTableBeingBackup
     */
    public function setTotalRowsOfTableBeingBackup($totalRowsOfTableBeingBackup)
    {
        $this->totalRowsOfTableBeingBackup = (int)$totalRowsOfTableBeingBackup;
    }

    /**
     * @return int
     */
    public function getDatabaseFileSize()
    {
        return $this->databaseFileSize;
    }

    /**
     * @param int $databaseFileSize
     */
    public function setDatabaseFileSize($databaseFileSize)
    {
        $this->databaseFileSize = $databaseFileSize;
    }

    /**
     * @return int
     */
    public function getFilesystemSize()
    {
        return $this->filesystemSize;
    }

    /**
     * @param int $filesystemSize
     */
    public function setFilesystemSize($filesystemSize)
    {
        $this->filesystemSize = $filesystemSize;
    }

    /**
     * @return int
     */
    public function getDiscoveringFilesRequests()
    {
        return $this->discoveringFilesRequests;
    }

    /**
     * @param int $discoveringFilesRequests
     */
    public function setDiscoveringFilesRequests($discoveringFilesRequests)
    {
        $this->discoveringFilesRequests = $discoveringFilesRequests;
    }

    /**
     * @see Cron For WP STAGING cron recurrences.
     *
     * @return string A WP STAGING cron schedule
     */
    public function getScheduleRecurrence()
    {
        return $this->scheduleRecurrence;
    }

    /**
     * @param string $scheduleRecurrence
     */
    public function setScheduleRecurrence($scheduleRecurrence)
    {
        $this->scheduleRecurrence = $scheduleRecurrence;
    }

    /**
     * @return array H:i time format, expected to be accurate to the site's timezone, example: 00:00
     */
    public function getScheduleTime()
    {
        return $this->scheduleTime;
    }

    /**
     * @param array $scheduleTime Hour and Minute ['00', '00']
     */
    public function setScheduleTime(array $scheduleTime)
    {
        $this->scheduleTime = $scheduleTime;
    }

    /**
     * @return int How many backups to keep, example: 1
     */
    public function getScheduleRotation()
    {
        return $this->scheduleRotation;
    }

    /**
     * @param int $scheduleRotation
     */
    public function setScheduleRotation($scheduleRotation)
    {
        $this->scheduleRotation = $scheduleRotation;
    }

    /**
     * @return string
     */
    public function getBackupFilePath()
    {
        return $this->backupFilePath;
    }

    /**
     * @param string $backupFilePath
     */
    public function setBackupFilePath($backupFilePath)
    {
        $this->backupFilePath = $backupFilePath;
    }

    /**
     * @return string|null
     */
    public function getScheduleId()
    {
        return $this->scheduleId;
    }

    /**
     * @param string $scheduleId
     */
    public function setScheduleId($scheduleId)
    {
        $this->scheduleId = $scheduleId;
    }

    /**
     * @return bool
     */
    public function getIsCreateScheduleBackupNow()
    {
        return $this->isCreateScheduleBackupNow;
    }

    /**
     * @param bool $isCreateScheduleBackupNow
     */
    public function setIsCreateScheduleBackupNow($isCreateScheduleBackupNow)
    {
        $this->isCreateScheduleBackupNow = (bool)$isCreateScheduleBackupNow;
    }

    /**
     * @return bool
     */
    public function getIsCreateBackupInBackground(): bool
    {
        return (bool)$this->isCreateBackupInBackground;
    }

    /**
     * Cannot strict type it yet, otherwise it might throw error for older scheduled backup
     * @param bool $isCreateBackupInBackground
     */
    public function setIsCreateBackupInBackground($isCreateBackupInBackground)
    {
        $this->isCreateBackupInBackground = (bool)$isCreateBackupInBackground;
    }

    /**
     * @return array|null
     */
    public function getSitesToBackup()
    {
        return (array)$this->sitesToBackup;
    }

    public function setSitesToBackup(array $sitesToBackup = [])
    {
        $this->sitesToBackup = $sitesToBackup;
    }

    /**
     * @return array
     */
    public function getDiscoveredFilesArray()
    {
        return $this->discoveredFilesArray;
    }

    /**
     * @param array $discoveredFiles
     */
    public function setDiscoveredFilesArray($discoveredFiles = [])
    {
        $this->discoveredFilesArray = $discoveredFiles;
    }

    /**
     * @param string $category
     * @return int
     */
    public function getDiscoveredFilesByCategory($category)
    {
        if (!array_key_exists($category, $this->discoveredFilesArray)) {
            return 0;
        }

        return $this->discoveredFilesArray[$category];
    }

    /**
     * @param string $category
     * @param int $discoveredFiles
     */
    public function setDiscoveredFilesByCategory($category, $discoveredFiles)
    {
        $this->discoveredFilesArray[$category] = $discoveredFiles;
    }

    /**
     * @return array
     */
    public function getFilesInParts()
    {
        return $this->filesInParts;
    }

    /**
     * @param array $filesInParts
     */
    public function setFilesInParts($filesInParts = [])
    {
        $this->filesInParts = $filesInParts;
    }

    /**
     * @param string $category
     * @param int $categoryIndex
     * @return int
     */
    public function getFilesInPart($category, $categoryIndex)
    {
        if (!array_key_exists($category, $this->filesInParts)) {
            return 0;
        }

        if (!isset($this->filesInParts[$category][$categoryIndex])) {
            return 0;
        }

        return $this->filesInParts[$category][$categoryIndex];
    }

    /**
     * @param string $category
     * @param int $categoryIndex
     * @param int $files
     */
    public function setFilesInPart($category, $categoryIndex, $files)
    {
        if (!array_key_exists($category, $this->filesInParts)) {
            $this->filesInParts[$category] = [];
        }

        $this->filesInParts[$category][$categoryIndex] = $files;
    }

    /**
     * @return array
     */
    public function getFileBackupIndices()
    {
        return $this->fileBackupIndices;
    }

    /**
     * @param array $fileBackupIndices
     */
    public function setFileBackupIndices($fileBackupIndices = [])
    {
        $this->fileBackupIndices = $fileBackupIndices;
    }

    /**
     * @return int
     */
    public function getMaxDbPartIndex()
    {
        return $this->maxDbPartIndex;
    }

    /**
     * @param int $maxDbPartIndex
     */
    public function setMaxDbPartIndex($maxDbPartIndex)
    {
        $this->maxDbPartIndex = $maxDbPartIndex;
    }

    /**
     * @return int
     */
    public function getCurrentMultipartFileInfoIndex()
    {
        return $this->currentMultipartFileInfoIndex;
    }

    /**
     * @param int $currentMultipartFileInfoIndex
     */
    public function setCurrentMultipartFileInfoIndex($currentMultipartFileInfoIndex)
    {
        $this->currentMultipartFileInfoIndex = $currentMultipartFileInfoIndex;
    }

    /**
     * @return array
     */
    public function getMultipartFilesInfo()
    {
        return $this->multipartFilesInfo;
    }

    /**
     * @param array $multipartFilesInfo
     */
    public function setMultipartFilesInfo($multipartFilesInfo)
    {
        $this->multipartFilesInfo = $multipartFilesInfo;
    }

    /**
     * @param array $multipartFileInfo
     */
    public function addMultipartFileInfo($multipartFileInfo)
    {
        $this->multipartFilesInfo[] = $multipartFileInfo;
    }

    /**
     * @param array $multipartFileInfo
     * @param int   $index
     */
    public function updateMultipartFileInfo($multipartFileInfo, $index)
    {
        $this->multipartFilesInfo[$index] = $multipartFileInfo;
    }

    /**
     * @param int $lastInsertId
     */
    public function setLastInsertId($lastInsertId)
    {
        $this->lastInsertId = $lastInsertId;
    }

    /**
     * @return int
     */
    public function getLastInsertId()
    {
        return $this->lastInsertId;
    }

    /**
     * @param array<string, int> $categorySizes
     */
    public function setCategorySizes($categorySizes)
    {
        $this->categorySizes = $categorySizes;
    }

    /**
     * @return array<string, int>
     */
    public function getCategorySizes()
    {
        return $this->categorySizes;
    }

    /**
     * @return int
     */
    public function getFilePartIndex(): int
    {
        return $this->filePartIndex;
    }

    /**
     * @param int $index
     * @return void
     */
    public function setFilePartIndex(int $index = 0)
    {
        $this->filePartIndex = $index;
    }

    /**
     * @param bool $isNetworkSiteBackup
     * @return void
     */
    public function setIsNetworkSiteBackup(bool $isNetworkSiteBackup)
    {
        $this->isNetworkSiteBackup = $isNetworkSiteBackup;
    }

    /**
     * @return bool
     */
    public function getIsNetworkSiteBackup(): bool
    {
        return (bool)$this->isNetworkSiteBackup;
    }

    /**
     * @param string $backupType
     * @return void
     */
    public function setBackupType(string $backupType)
    {
        $this->backupType = $backupType;
    }

    /**
     * @return string
     */
    public function getBackupType(): string
    {
        return $this->backupType;
    }

    /**
     * @param int|null $subsiteBlogId
     * @return void
     */
    public function setSubsiteBlogId($subsiteBlogId)
    {
        $this->subsiteBlogId = $subsiteBlogId;
    }

    /**
     * @return int
     */
    public function getSubsiteBlogId(): int
    {
        if (empty($this->subsiteBlogId)) {
            $this->subsiteBlogId = get_current_blog_id();
        }

        return (int)$this->subsiteBlogId;
    }

    /**
     * @return bool
     */
    public function getIsValidateBackupFiles(): bool
    {
        return (bool)$this->isValidateBackupFiles;
    }

    /**
     * Cannot strict type it yet, otherwise it might throw error for older scheduled backup
     * @param bool $isValidateBackupFiles
     */
    public function setIsValidateBackupFiles($isValidateBackupFiles)
    {
        $this->isValidateBackupFiles = (bool)$isValidateBackupFiles;
    }

    /**
     * @return bool
     */
    public function getIsBackupFormatV1(): bool
    {
        return Hooks::applyFilters(BackupMetadata::FILTER_BACKUP_FORMAT_V1, false);
    }

    /**
     * @return bool
     */
    public function getIsCompressedBackup(): bool
    {
        return WPStaging::make(ZlibCompressor::class)->isCompressionEnabled();
    }

    /**
     * @return bool
     */
    public function getIsContaining2GBFile(): bool
    {
        return $this->isContaining2GBFile;
    }

    /**
     * @param bool $isContaining2GBFile
     * @return void
     */
    public function setIsContaining2GBFile(bool $isContaining2GBFile)
    {
        $this->isContaining2GBFile = $isContaining2GBFile;
    }

    /**
     * @return int
     */
    public function getCurrentWrittenFileHeaderBytes(): int
    {
        return (int)$this->currentWrittenFileHeaderBytes;
    }

    /**
     * @param int $currentWrittenFileHeaderBytes
     * @return void
     */
    public function setCurrentWrittenFileHeaderBytes(int $currentWrittenFileHeaderBytes)
    {
        $this->currentWrittenFileHeaderBytes = (int)$currentWrittenFileHeaderBytes;
    }

    /**
     * @param int $timeLimit
     * @return void
     */
    public function setFileAppendTimeLimit(int $timeLimit)
    {
        $this->fileAppendTimeLimit = $timeLimit;
    }

    /**
     * @return int
     */
    public function getFileAppendTimeLimit(): int
    {
        return $this->fileAppendTimeLimit;
    }

    /**
     * @return void
     */
    public function incrementFileAppendTimeLimit()
    {
        $this->fileAppendTimeLimit += 5;
    }

    /**
     * @return void
     */
    public function resetFileAppendTimeLimit()
    {
        $this->fileAppendTimeLimit = 10;
    }
}
