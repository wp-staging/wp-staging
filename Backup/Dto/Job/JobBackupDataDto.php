<?php

namespace WPStaging\Backup\Dto\Job;

use WPStaging\Backup\Dto\JobDataDto;
use WPStaging\Backup\Dto\Traits\IsExportingTrait;

class JobBackupDataDto extends JobDataDto
{
    use IsExportingTrait;

    /** @var string|null */
    private $name;

    /** @var array */
    private $excludedDirectories = [];

    /** @var bool */
    private $isAutomatedBackup = false;

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

    /** @var array */
    private $filesToUpload = [];

    /** @var array */
    private $uploadedFiles = [];

    /** @var string */
    private $databaseFile;

    /**
     * @var int If a file couldn't be processed in a single request,
     *          this property holds how many bytes were written thus far
     *          so that the backup can start writing from this byte onwards.
     */
    private $fileBeingBackupWrittenBytes;

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

    /** @var int The size of all backup multipart files or single full backup file */
    private $totalBackupSize = 0;

    /** @var int The number of requests that the Discovering Files task has executed so far */
    private $discoveringFilesRequests = 0;

    /** @var bool True if this backup should be repeated on a schedule, false if it should run only once. */
    private $repeatBackupOnSchedule;

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

    /** @var array Selected storages for backup. */
    private $storages;

    /**
     * @var array The meta data used by used by Remote Storages to help uploading.
     * Stores ResumeURI for Google Drive
     * Stores UploadId and UploadedParts Meta for Amazon S3
     */
    private $remoteStorageMeta;

    /** @var bool Should this scheduled backup be created right now. Matters only if this backup is repeated on schedule */
    private $isCreateScheduleBackupNow;

    /** @var array Site selected to backup */
    private $sitesToBackup = [];

    /** @var bool */
    private $isMultipartBackup = false;

    /** @var int */
    private $maxMultipartBackupSize = 2147483647; // 2GB - 1 Byte

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
     * @return bool
     */
    public function getIsAutomatedBackup()
    {
        return (bool)$this->isAutomatedBackup;
    }

    /**
     * Hydrated dynamically.
     *
     * @param bool $isAutomatedBackup
     */
    public function setIsAutomatedBackup($isAutomatedBackup)
    {
        $this->isAutomatedBackup = $isAutomatedBackup;
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
    public function getTotalBackupSize()
    {
        return $this->totalBackupSize;
    }

    /**
     * @param int $totalBackupSize
     */
    public function setTotalBackupSize($totalBackupSize)
    {
        $this->totalBackupSize = $totalBackupSize;
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
     * @return bool
     */
    public function getRepeatBackupOnSchedule()
    {
        return $this->repeatBackupOnSchedule;
    }

    /**
     * @param bool $repeatBackupOnSchedule
     */
    public function setRepeatBackupOnSchedule($repeatBackupOnSchedule)
    {
        $this->repeatBackupOnSchedule = $repeatBackupOnSchedule;
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
     * @return string
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
     * @return array
     */
    public function getStorages()
    {
        return $this->storages;
    }

    /**
     * @param string|array $storages
     */
    public function setStorages($storages)
    {
        if (!is_array($storages)) {
            $storages = json_decode($storages, true);
        }

        $this->storages = $storages;
    }

    /**
     * @return array
     */
    public function getRemoteStorageMeta()
    {
        return $this->remoteStorageMeta;
    }

    /**
     * @param array $remoteStorageMeta
     */
    public function setRemoteStorageMeta($remoteStorageMeta)
    {
        $this->remoteStorageMeta = $remoteStorageMeta;
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

    /** @return bool */
    public function isLocalBackup()
    {
        return in_array('localStorage', $this->getStorages());
    }

    /** @return bool */
    public function isUploadToGoogleDrive()
    {
        return in_array('googleDrive', $this->getStorages());
    }

    /** @return bool */
    public function isUploadToAmazonS3()
    {
        return in_array('amazonS3', $this->getStorages());
    }

    /** @return bool */
    public function isUploadToDropbox()
    {
        return in_array('dropbox', $this->getStorages());
    }

    /** @return bool */
    public function isUploadToSftp()
    {
        return in_array('sftp', $this->getStorages());
    }

    /**
     * @return bool
     */
    public function getIsMultipartBackup()
    {
        return apply_filters('wpstg.backup.isMultipartBackup', $this->isMultipartBackup);
    }

    /**
     * @param bool $isMultipartBackup
     */
    public function setIsMultipartBackup($isMultipartBackup)
    {
        $this->isMultipartBackup = $isMultipartBackup;
    }

    /**
     * @return int
     */
    public function getMaxMultipartBackupSize()
    {
        return apply_filters('wpstg.backup.maxMultipartBackupSize', $this->maxMultipartBackupSize);
    }

    /**
     * @param int $maxMultipartBackupSize
     */
    public function setMaxMultipartBackupSize($maxMultipartBackupSize)
    {
        $this->maxMultipartBackupSize = $maxMultipartBackupSize;
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
    public function getFilesToUpload()
    {
        return $this->filesToUpload;
    }

    /**
     * @param array $filesToUpload
     */
    public function setFilesToUpload($filesToUpload = [])
    {
        $this->filesToUpload = $filesToUpload;
    }

    /**
     * @return array
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * @param array $uploadedFiles
     */
    public function setUploadedFiles($uploadedFiles = [])
    {
        $this->uploadedFiles = $uploadedFiles;
    }

    /**
     * @param string $uploadedFile
     * @param int    $fileSize
     */
    public function setUploadedFile($uploadedFile, $fileSize)
    {
        $this->uploadedFiles[$uploadedFile] = $fileSize;
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

    /** @return bool */
    public function isUploadToWasabi()
    {
        return in_array('wasabi-s3', $this->getStorages());
    }

    /** @return bool */
    public function isUploadToDigitalOceanSpaces()
    {
        return in_array('digitalocean-spaces', $this->getStorages());
    }

    /** @return bool */
    public function isUploadToGenericS3()
    {
        return in_array('generic-s3', $this->getStorages());
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
}
