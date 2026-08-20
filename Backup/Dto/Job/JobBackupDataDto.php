<?php

namespace WPStaging\Backup\Dto\Job;

use WPStaging\Backup\Dto\Interfaces\RemoteUploadDtoInterface;
use WPStaging\Backup\Dto\Traits\IsExportingTrait;
use WPStaging\Backup\Dto\Traits\IsExcludingTrait;
use WPStaging\Backup\Dto\Traits\RemoteUploadTrait;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\JobDataDto;

class JobBackupDataDto extends JobDataDto implements RemoteUploadDtoInterface
{
    use IsExportingTrait;
    use IsExcludingTrait;
    use RemoteUploadTrait;

 
    private $name;

 
    private $isBeforeUpdateBackup = false;

 
    private $excludedDirectories = [];

 
    private $totalDirectories;

 
    private $totalFiles;

 
    private $filesInParts = [];

 
    private $discoveredFiles = 0;

 
    private $discoveredFilesArray = [];

 
    private $invalidFiles = 0;

 
    private $databaseFile;






    private $fileBeingBackupWrittenBytes;




    private $currentWrittenFileHeaderBytes = 0;




    private $currentFileStartOffset = 0;

 
    private $totalRowsBackup = 0;

 
    private $tableRowsOffset = 0;

 
    private $sqlCheckpointFile = '';

 
    private $sqlWrittenBytes = 0;

 
    private $totalRowsOfTableBeingBackup = 0;

 
    private $lastInsertId = PHP_INT_MIN;

 
    private $tablesToBackup = [];

 
    private $nonWpTables = [];

 
    private $databaseFileSize = 0;

 
    private $filesystemSize = 0;

 
    private $discoveringFilesRequests = 0;

 
    private $scheduleRecurrence = '';

 
    private $scheduleTime = [];

 
    private $scheduleRotation = 0;

 
    private $backupFilePath = '';

 
    private $scheduleId = '';

 
    private $isValidateBackupFiles = false;

 
    private $isCreateScheduleBackupNow = false;

 
    private $isCreateBackupInBackground = false;

 
    private $sitesToBackup = [];




    private $isNetworkSiteBackup = false;





    private $fileBackupIndices = [];

 
    private $maxDbPartIndex = 0;







    private $bigFileSourceBytesWritten = 0;






    private $bigFileIsContinuation = false;

 
    private $currentMultipartFileInfoIndex = 0;

 
    private $multipartFilesInfo = [];





    private $categorySizes = [];

 
    private $backupType = '';

 
    private $subsiteBlogId = 0;

 
    private $filePartIndex = 0;

 
    private $isContaining2GBFile = false;

 
    private $isGlitchInBackup = false;

 
    private $glitchReason = '';

 
    private $fileAppendTimeLimit = 10;

 
    private $isCompressed = false;




    private $backupSizeUncompressed = 0;




    private $backupSizeCompressed = 0;




    private $totalFilesCompressed = 0;

 
    private $pushPrepareData = [];




    public function isScheduledBackup(): bool
    {
        return $this->getRepeatBackupOnSchedule() || !empty($this->getScheduleId());
    }




    public function getName()
    {
        return $this->name;
    }






    public function setName($name)
    {
        $this->name = $name;
    }




    public function getIsBeforeUpdateBackup(): bool
    {
        return $this->isBeforeUpdateBackup;
    }





    public function setIsBeforeUpdateBackup(bool $isBeforeUpdateBackup)
    {
        $this->isBeforeUpdateBackup = $isBeforeUpdateBackup;
    }




    public function getPushPrepareData(): array
    {
        return $this->pushPrepareData;
    }





    public function setPushPrepareData(array $pushPrepareData)
    {
        $this->pushPrepareData = $pushPrepareData;
    }




    public function getExcludedDirectories()
    {
        return (array)$this->excludedDirectories;
    }

    public function setExcludedDirectories(array $excludedDirectories = [])
    {
        $this->excludedDirectories = $excludedDirectories;
    }




    public function getTotalDirectories()
    {
        return $this->totalDirectories;
    }




    public function setTotalDirectories($totalDirectories)
    {
        $this->totalDirectories = $totalDirectories;
    }




    public function getTotalFiles()
    {
        return $this->totalFiles;
    }




    public function setTotalFiles($totalFiles)
    {
        $this->totalFiles = $totalFiles;
    }




    public function getDiscoveredFiles()
    {
        return $this->discoveredFiles;
    }




    public function setDiscoveredFiles($discoveredFiles)
    {
        $this->discoveredFiles = $discoveredFiles;
    }




    public function getInvalidFiles(): int
    {
        return $this->invalidFiles;
    }





    public function setInvalidFiles(int $invalidFiles)
    {
        $this->invalidFiles = $invalidFiles;
    }




    public function incrementInvalidFiles()
    {
        $this->invalidFiles++;
    }




    public function getDatabaseFile()
    {
        return $this->databaseFile;
    }




    public function setDatabaseFile($databaseFile)
    {
        $this->databaseFile = $databaseFile;
    }




    public function getTableRowsOffset()
    {
        return (int)$this->tableRowsOffset;
    }




    public function setTableRowsOffset($tableRowsOffset)
    {
        $this->tableRowsOffset = (int)$tableRowsOffset;
    }




    public function getSqlCheckpointFile()
    {
        return (string)$this->sqlCheckpointFile;
    }




    public function setSqlCheckpointFile($sqlCheckpointFile)
    {
        $this->sqlCheckpointFile = (string)$sqlCheckpointFile;
    }




    public function getSqlWrittenBytes()
    {
        return (int)$this->sqlWrittenBytes;
    }




    public function setSqlWrittenBytes($sqlWrittenBytes)
    {
        $this->sqlWrittenBytes = (int)$sqlWrittenBytes;
    }




    public function getTotalRowsBackup()
    {
        return (int)$this->totalRowsBackup;
    }




    public function setTotalRowsBackup($totalRowsBackup)
    {
        $this->totalRowsBackup = (int)$totalRowsBackup;
    }




    public function getFileBeingBackupWrittenBytes()
    {
        return (int)$this->fileBeingBackupWrittenBytes;
    }




    public function setFileBeingBackupWrittenBytes($fileBeingBackupWrittenBytes)
    {
        $this->fileBeingBackupWrittenBytes = (int)$fileBeingBackupWrittenBytes;
    }




    public function getTablesToBackup()
    {
        return (array)$this->tablesToBackup;
    }




    public function setTablesToBackup($tablesToBackup)
    {
        $this->tablesToBackup = (array)$tablesToBackup;
    }




    public function getNonWpTables()
    {
        return (array)$this->nonWpTables;
    }




    public function setNonWpTables($nonWpTables)
    {
        $this->nonWpTables = (array)$nonWpTables;
    }




    public function getTotalRowsOfTableBeingBackup()
    {
        return (int)$this->totalRowsOfTableBeingBackup;
    }




    public function setTotalRowsOfTableBeingBackup($totalRowsOfTableBeingBackup)
    {
        $this->totalRowsOfTableBeingBackup = (int)$totalRowsOfTableBeingBackup;
    }




    public function getDatabaseFileSize()
    {
        return $this->databaseFileSize;
    }




    public function setDatabaseFileSize($databaseFileSize)
    {
        $this->databaseFileSize = $databaseFileSize;
    }




    public function getFilesystemSize()
    {
        return $this->filesystemSize;
    }




    public function setFilesystemSize($filesystemSize)
    {
        $this->filesystemSize = $filesystemSize;
    }




    public function getDiscoveringFilesRequests()
    {
        return $this->discoveringFilesRequests;
    }




    public function setDiscoveringFilesRequests($discoveringFilesRequests)
    {
        $this->discoveringFilesRequests = $discoveringFilesRequests;
    }






    public function getScheduleRecurrence()
    {
        return $this->scheduleRecurrence;
    }




    public function setScheduleRecurrence($scheduleRecurrence)
    {
        $this->scheduleRecurrence = $scheduleRecurrence;
    }




    public function getScheduleTime()
    {
        return $this->scheduleTime;
    }




    public function setScheduleTime(array $scheduleTime)
    {
        $this->scheduleTime = $scheduleTime;
    }




    public function getScheduleRotation()
    {
        return $this->scheduleRotation;
    }




    public function setScheduleRotation($scheduleRotation)
    {
        $this->scheduleRotation = $scheduleRotation;
    }




    public function getBackupFilePath()
    {
        return $this->backupFilePath;
    }




    public function setBackupFilePath($backupFilePath)
    {
        $this->backupFilePath = $backupFilePath;
    }




    public function getScheduleId()
    {
        return $this->scheduleId;
    }




    public function setScheduleId($scheduleId)
    {
        $this->scheduleId = $scheduleId;
    }




    public function getIsCreateScheduleBackupNow()
    {
        return $this->isCreateScheduleBackupNow;
    }




    public function setIsCreateScheduleBackupNow($isCreateScheduleBackupNow)
    {
        $this->isCreateScheduleBackupNow = (bool)$isCreateScheduleBackupNow;
    }




    public function getIsCreateBackupInBackground(): bool
    {
        return (bool)$this->isCreateBackupInBackground;
    }





    public function setIsCreateBackupInBackground($isCreateBackupInBackground)
    {
        $this->isCreateBackupInBackground = (bool)$isCreateBackupInBackground;
    }




    public function getSitesToBackup()
    {
        return (array)$this->sitesToBackup;
    }

    public function setSitesToBackup(array $sitesToBackup = [])
    {
        $this->sitesToBackup = $sitesToBackup;
    }




    public function getDiscoveredFilesArray()
    {
        return $this->discoveredFilesArray;
    }




    public function setDiscoveredFilesArray($discoveredFiles = [])
    {
        $this->discoveredFilesArray = $discoveredFiles;
    }





    public function getDiscoveredFilesByCategory($category)
    {
        if (!array_key_exists($category, $this->discoveredFilesArray)) {
            return 0;
        }

        return $this->discoveredFilesArray[$category];
    }





    public function setDiscoveredFilesByCategory($category, $discoveredFiles)
    {
        $this->discoveredFilesArray[$category] = $discoveredFiles;
    }




    public function getFilesInParts()
    {
        return $this->filesInParts;
    }




    public function setFilesInParts($filesInParts = [])
    {
        $this->filesInParts = $filesInParts;
    }






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






    public function setFilesInPart($category, $categoryIndex, $files)
    {
        if (!array_key_exists($category, $this->filesInParts)) {
            $this->filesInParts[$category] = [];
        }

        $this->filesInParts[$category][$categoryIndex] = $files;
    }






    public function incrementFilesInPart(string $category, int $categoryIndex = 0)
    {
        if (!array_key_exists($category, $this->filesInParts)) {
            $this->filesInParts[$category] = [];
        }

        if (!array_key_exists($categoryIndex, $this->filesInParts[$category])) {
            $this->filesInParts[$category][$categoryIndex] = 0;
        }

        $this->filesInParts[$category][$categoryIndex]++;
    }




    public function getFileBackupIndices()
    {
        return $this->fileBackupIndices;
    }




    public function setFileBackupIndices($fileBackupIndices = [])
    {
        $this->fileBackupIndices = $fileBackupIndices;
    }




    public function getMaxDbPartIndex()
    {
        return $this->maxDbPartIndex;
    }




    public function setMaxDbPartIndex($maxDbPartIndex)
    {
        $this->maxDbPartIndex = $maxDbPartIndex;
    }

    public function getBigFileSourceBytesWritten(): int
    {
        return (int) $this->bigFileSourceBytesWritten;
    }

    public function setBigFileSourceBytesWritten(int $bigFileSourceBytesWritten)
    {
        $this->bigFileSourceBytesWritten = $bigFileSourceBytesWritten;
    }

    public function getBigFileIsContinuation(): bool
    {
        return (bool) $this->bigFileIsContinuation;
    }

    public function setBigFileIsContinuation(bool $bigFileIsContinuation)
    {
        $this->bigFileIsContinuation = $bigFileIsContinuation;
    }




    public function getCurrentMultipartFileInfoIndex()
    {
        return $this->currentMultipartFileInfoIndex;
    }




    public function setCurrentMultipartFileInfoIndex($currentMultipartFileInfoIndex)
    {
        $this->currentMultipartFileInfoIndex = $currentMultipartFileInfoIndex;
    }




    public function getMultipartFilesInfo()
    {
        return $this->multipartFilesInfo;
    }




    public function setMultipartFilesInfo($multipartFilesInfo)
    {
        $this->multipartFilesInfo = $multipartFilesInfo;
    }




    public function addMultipartFileInfo($multipartFileInfo)
    {
        $this->multipartFilesInfo[] = $multipartFileInfo;
    }





    public function updateMultipartFileInfo($multipartFileInfo, $index)
    {
        $this->multipartFilesInfo[$index] = $multipartFileInfo;
    }




    public function setLastInsertId($lastInsertId)
    {
        $this->lastInsertId = $lastInsertId;
    }




    public function getLastInsertId()
    {
        return $this->lastInsertId;
    }




    public function setCategorySizes($categorySizes)
    {
        $this->categorySizes = $categorySizes;
    }




    public function getCategorySizes()
    {
        return $this->categorySizes;
    }




    public function getFilePartIndex(): int
    {
        return $this->filePartIndex;
    }





    public function setFilePartIndex(int $index = 0)
    {
        $this->filePartIndex = $index;
    }





    public function setIsNetworkSiteBackup(bool $isNetworkSiteBackup)
    {
        $this->isNetworkSiteBackup = $isNetworkSiteBackup;
    }




    public function getIsNetworkSiteBackup(): bool
    {
        return (bool)$this->isNetworkSiteBackup;
    }





    public function setBackupType(string $backupType)
    {
        $this->backupType = $backupType;
    }




    public function getBackupType(): string
    {
        return $this->backupType;
    }





    public function setSubsiteBlogId($subsiteBlogId)
    {
        $this->subsiteBlogId = $subsiteBlogId;
    }




    public function getSubsiteBlogId(): int
    {
        if (empty($this->subsiteBlogId)) {
            $this->subsiteBlogId = get_current_blog_id();
        }

        return (int)$this->subsiteBlogId;
    }




    public function getIsValidateBackupFiles(): bool
    {
        return (bool)$this->isValidateBackupFiles;
    }





    public function setIsValidateBackupFiles($isValidateBackupFiles)
    {
        $this->isValidateBackupFiles = (bool)$isValidateBackupFiles;
    }




    public function getIsBackupFormatV1(): bool
    {
        return Hooks::applyFilters(BackupMetadata::FILTER_BACKUP_FORMAT_V1, false);
    }




    public function getIsContaining2GBFile(): bool
    {
        return $this->isContaining2GBFile;
    }





    public function setIsContaining2GBFile(bool $isContaining2GBFile)
    {
        $this->isContaining2GBFile = $isContaining2GBFile;
    }

    public function getIsGlitchInBackup(): bool
    {
        return $this->isGlitchInBackup;
    }





    public function setIsGlitchInBackup(bool $isGlitchInBackup)
    {
        $this->isGlitchInBackup = $isGlitchInBackup;
    }

    public function getGlitchReason(): string
    {
        return $this->glitchReason;
    }





    public function setGlitchReason(string $glitchReason)
    {
        $this->glitchReason = $glitchReason;
    }




    public function getCurrentWrittenFileHeaderBytes(): int
    {
        return (int)$this->currentWrittenFileHeaderBytes;
    }





    public function setCurrentWrittenFileHeaderBytes(int $currentWrittenFileHeaderBytes)
    {
        $this->currentWrittenFileHeaderBytes = (int)$currentWrittenFileHeaderBytes;
    }

    public function getCurrentFileStartOffset(): int
    {
        return $this->currentFileStartOffset;
    }





    public function setCurrentFileStartOffset(int $currentFileStartOffset)
    {
        $this->currentFileStartOffset = (int)$currentFileStartOffset;
    }





    public function setFileAppendTimeLimit(int $timeLimit)
    {
        $this->fileAppendTimeLimit = $timeLimit;
    }




    public function getFileAppendTimeLimit(): int
    {
        return $this->fileAppendTimeLimit;
    }




    public function incrementFileAppendTimeLimit()
    {
        $this->fileAppendTimeLimit += 5;
    }




    public function resetFileAppendTimeLimit()
    {
        $this->fileAppendTimeLimit = 10;
    }




    public function getIsCompressed(): bool
    {
        return $this->isCompressed;
    }





    public function setIsCompressed(bool $isCompressed)
    {
        $this->isCompressed = $isCompressed;
    }




    public function getBackupSizeUncompressed(): int
    {
        return $this->backupSizeUncompressed;
    }





    public function setBackupSizeUncompressed(int $backupSizeUncompressed)
    {
        $this->backupSizeUncompressed = $backupSizeUncompressed;
    }





    public function addBackupSizeUncompressed(int $backupSizeUncompressed)
    {
        $this->backupSizeUncompressed += $backupSizeUncompressed;
    }




    public function getBackupSizeCompressed(): int
    {
        return $this->backupSizeCompressed;
    }





    public function setBackupSizeCompressed(int $backupSizeCompressed)
    {
        $this->backupSizeCompressed = $backupSizeCompressed;
    }





    public function addBackupSizeCompressed(int $backupSizeCompressed)
    {
        $this->backupSizeCompressed += $backupSizeCompressed;
    }




    public function getTotalFilesCompressed(): int
    {
        return $this->totalFilesCompressed;
    }





    public function setTotalFilesCompressed(int $totalFilesCompressed)
    {
        $this->totalFilesCompressed = $totalFilesCompressed;
    }




    public function incrementTotalFilesCompressed()
    {
        $this->totalFilesCompressed++;
    }
}
