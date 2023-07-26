<?php

namespace WPStaging\Backup\Dto\Job;

use WPStaging\Backup\Dto\JobDataDto;
use WPStaging\Backup\Entity\BackupMetadata;

class JobRestoreDataDto extends JobDataDto
{
    /** @var string */
    private $file;

    /** @var BackupMetadata|null */
    private $backupMetadata;

    /** @var string */
    protected $tmpDirectory;

    /** @var int Number of extracted files */
    private $extractorFilesExtracted = 0;

    /** @var int Number of written bytes to process the current files */
    private $extractorFileWrittenBytes = 0;

    private $extractorMetadataIndexPosition = 0;

    /** @var string Database table prefix to use while restoring the backup */
    private $tmpDatabasePrefix;

    /** @var string Table being inserted during restore. */
    private $tableToRestore;

    /** @var bool Whether a transaction is started. */
    private $transactionStarted;

    /** @var array Store short names tables to drop */
    private $shortNamesTablesToDrop = [];

    /** @var array Store short names tables to restore */
    private $shortNamesTablesToRestore = [];

    /** @var bool */
    private $requireShortNamesForTablesToDrop = false;

    /** @var bool */
    private $requireShortNamesForTablesToRestore = false;

    /** @var int */
    private $databasePartIndex = 0;

    /** @var int */
    private $filePartIndex = 0;

    /** @var bool */
    private $isSameSiteBackupRestore = false;

    /** @var bool */
    private $isMissingDatabaseFile = false;

    /** @var int */
    private $currentFileHeaderStart = 0;

    /**
     * If a SQL query returns 0 due to mysql timeout count the failed attempts
     * in this property to increase the execution time
     * @var int
     */
    private $numberOfQueryAttemptsWithZeroResult = 0;

    /**
     * Current execution time in sec for database restore
     * @var int
     */
    private $currentExecutionTimeDatabaseRestore = 10;

    /** @var array */
    private $databaseDataToPreserve = [];

    /** @var int */
    private $totalTablesToRename = 0;

    /** @var int */
    private $totalTablesRenamed = 0;

    /**
     * Store checksum of some important files in the form of key value format i.e. file path => checksum
     *
     * @var array
     */
    private $filesChecksum = [];

    /** @var bool */
    private $objectCacheSkipped = false;

    /**
     * @return string The .wpstg backup file being restored.
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Called dynamically
     * @see \WPStaging\Backup\Ajax\Restore\PrepareRestore::setupInitialData
     *
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = untrailingslashit(wp_normalize_path($file));
    }

    /**
     * @return BackupMetadata|null
     */
    public function getBackupMetadata()
    {
        return $this->backupMetadata;
    }

    /**
     * @param $backupMetadata
     */
    public function setBackupMetadata($backupMetadata)
    {
        if ($backupMetadata instanceof BackupMetadata) {
            $this->backupMetadata = $backupMetadata;

            return;
        }
        if (is_array($backupMetadata)) {
            try {
                $this->backupMetadata = (new BackupMetadata())->hydrate($backupMetadata);
                $this->setCurrentFileHeaderStart($this->backupMetadata->getHeaderStart());

                return;
            } catch (\Exception $e) {
                $this->backupMetadata = null;

                return;
            }
        }

        $this->backupMetadata = null;
    }

    /**
     * @return string
     */
    public function getTmpDirectory()
    {
        return $this->tmpDirectory;
    }

    /**
     * @param string $tmpPath
     */
    public function setTmpDirectory($tmpPath)
    {
        $this->tmpDirectory = trailingslashit(wp_normalize_path($tmpPath));
    }

    /**
     * @return int
     */
    public function getExtractorFilesExtracted()
    {
        return $this->extractorFilesExtracted;
    }

    public function setExtractorFilesExtracted($extractorFilesExtracted)
    {
        $this->extractorFilesExtracted = (int)$extractorFilesExtracted;
    }

    public function incrementExtractorFilesExtracted()
    {
        $this->extractorFilesExtracted++;
    }

    /**
     * @return int
     */
    public function getExtractorFileWrittenBytes()
    {
        return $this->extractorFileWrittenBytes;
    }

    /**
     * @param int $fileWrittenBytes
     */
    public function setExtractorFileWrittenBytes($fileWrittenBytes)
    {
        $this->extractorFileWrittenBytes = (int)$fileWrittenBytes;
    }

    /**
     * @return int
     */
    public function getExtractorMetadataIndexPosition()
    {
        return $this->extractorMetadataIndexPosition;
    }

    /**
     * @param int $extractorMetadataIndexPosition
     */
    public function setExtractorMetadataIndexPosition($extractorMetadataIndexPosition)
    {
        $this->extractorMetadataIndexPosition = (int)$extractorMetadataIndexPosition;
    }

    /**
     * @return string
     */
    public function getTmpDatabasePrefix()
    {
        return $this->tmpDatabasePrefix;
    }

    /**
     * @param string $tmpDatabasePrefix
     */
    public function setTmpDatabasePrefix($tmpDatabasePrefix)
    {
        $this->tmpDatabasePrefix = $tmpDatabasePrefix;
    }

    /**
     * @return string
     */
    public function getTableToRestore()
    {
        return $this->tableToRestore;
    }

    /**
     * @param string $tableToRestore
     */
    public function setTableToRestore($tableToRestore)
    {
        $this->tableToRestore = $tableToRestore;
    }

    /**
     * @return bool
     */
    public function getTransactionStarted()
    {
        return $this->transactionStarted;
    }

    /**
     * @param bool $transactionStarted
     */
    public function setTransactionStarted($transactionStarted)
    {
        $this->transactionStarted = $transactionStarted;
    }

    /**
     * @return array
     */
    public function getShortNamesTablesToDrop()
    {
        return $this->shortNamesTablesToDrop;
    }

    /**
     * @param array $tables
     */
    public function setShortNamesTablesToDrop($tables = [])
    {
        $this->shortNamesTablesToDrop = $tables;
    }

    /**
     * @param string $originalName
     * @param string $shorterName
     */
    public function addShortNameTableToDrop($originalName, $shorterName)
    {
        $this->shortNamesTablesToDrop[$shorterName] = $originalName;
    }

    /**
     * @return array
     */
    public function getShortNamesTablesToRestore()
    {
        return $this->shortNamesTablesToRestore;
    }

    /**
     * @param array $tables
     */
    public function setShortNamesTablesToRestore($tables = [])
    {
        $this->shortNamesTablesToRestore = $tables;
    }

    /**
     * @param string $originalName
     * @param string $shorterName
     */
    public function addShortNameTableToRestore($originalName, $shorterName)
    {
        $this->shortNamesTablesToRestore[$shorterName] = $originalName;
    }

    /**
     * @return bool
     */
    public function getRequireShortNamesForTablesToRestore()
    {
        return $this->requireShortNamesForTablesToRestore;
    }

    /**
     * @param bool $require
     */
    public function setRequireShortNamesForTablesToRestore($require = false)
    {
        $this->requireShortNamesForTablesToRestore = $require;
    }

    /**
     * @return bool
     */
    public function getRequireShortNamesForTablesToDrop()
    {
        return $this->requireShortNamesForTablesToDrop;
    }

    /**
     * @param bool $require
     */
    public function setRequireShortNamesForTablesToDrop($require = false)
    {
        $this->requireShortNamesForTablesToDrop = $require;
    }

    /**
     * @return int
     */
    public function getDatabasePartIndex()
    {
        return $this->databasePartIndex;
    }

    /**
     * @param int $index
     */
    public function setDatabasePartIndex($index = 0)
    {
        $this->databasePartIndex = $index;
    }

    /**
     * @return bool
     */
    public function getIsSameSiteBackupRestore()
    {
        return $this->isSameSiteBackupRestore;
    }

    /**
     * @param bool $sameSite
     */
    public function setIsSameSiteBackupRestore($sameSite)
    {
        $this->isSameSiteBackupRestore = $sameSite;
    }

    /**
     * @return bool
     */
    public function getIsMissingDatabaseFile()
    {
        return $this->isMissingDatabaseFile;
    }

    /**
     * @param bool $missingFile
     */
    public function setIsMissingDatabaseFile($missingFile)
    {
        $this->isMissingDatabaseFile = $missingFile;
    }

    /**
     * @return int
     */
    public function getFilePartIndex()
    {
        return $this->filePartIndex;
    }

    /**
     * @param int $index
     */
    public function setFilePartIndex($index = 0)
    {
        $this->filePartIndex = $index;
    }

    /**
     * @return int
     */
    public function getCurrentFileHeaderStart()
    {
        return $this->currentFileHeaderStart;
    }

    /**
     * @param int $headerStart
     */
    public function setCurrentFileHeaderStart($headerStart = 0)
    {
        $this->currentFileHeaderStart = $headerStart;
    }

    /**
     * @return int
     */
    public function getNumberOfQueryAttemptsWithZeroResult()
    {
        return $this->numberOfQueryAttemptsWithZeroResult;
    }

    public function incrementNumberOfQueryAttemptsWithZeroResult()
    {
        $this->numberOfQueryAttemptsWithZeroResult++;
    }

    public function resetNumberOfQueryAttemptsWithZeroResult()
    {
        $this->numberOfQueryAttemptsWithZeroResult = 0;
    }

    /**
     * @param int $numberOfQueryAttemptsWithZeroResult
     */
    public function setNumberOfQueryAttemptsWithZeroResult($numberOfQueryAttemptsWithZeroResult = 0)
    {
        $this->numberOfQueryAttemptsWithZeroResult = $numberOfQueryAttemptsWithZeroResult;
    }

    /**
     * @return int
     */
    public function getCurrentExecutionTimeDatabaseRestore()
    {
        $time = $this->currentExecutionTimeDatabaseRestore;
        if ($time < 10) {
            return 10;
        }

        return $time;
    }

    public function incrementCurrentExecutionTimeDatabaseRestore()
    {
        $this->currentExecutionTimeDatabaseRestore += 5;
    }

    /**
     * @param int $currentExecutionTimeDatabaseRestore
     */
    public function setCurrentExecutionTimeDatabaseRestore($currentExecutionTimeDatabaseRestore = 0)
    {
        $this->currentExecutionTimeDatabaseRestore = $currentExecutionTimeDatabaseRestore;
    }

    /**
     * @return array
     */
    public function getDatabaseDataToPreserve()
    {
        return $this->databaseDataToPreserve;
    }

    /**
     * @param array $databaseDataToPreserve
     */
    public function setDatabaseDataToPreserve($databaseDataToPreserve)
    {
        $this->databaseDataToPreserve = $databaseDataToPreserve;
    }

    /**
     * @return int
     */
    public function getTotalTablesToRename()
    {
        return $this->totalTablesToRename;
    }

    /**
     * @param int $totalTablesToRename
     */
    public function setTotalTablesToRename($totalTablesToRename)
    {
        $this->totalTablesToRename = $totalTablesToRename;
    }

    /**
     * @return int
     */
    public function getTotalTablesRenamed()
    {
        return $this->totalTablesRenamed;
    }

    /**
     * @param int $totalTablesRenamed
     */
    public function setTotalTablesRenamed($totalTablesRenamed)
    {
        $this->totalTablesRenamed = $totalTablesRenamed;
    }

    /**
     * @return array
     */
    public function getFilesChecksum()
    {
        return $this->filesChecksum;
    }

    /**
     * @param array $filesChecksum
     */
    public function setFilesChecksum($filesChecksum)
    {
        $this->filesChecksum = $filesChecksum;
    }

    /**
     * @param string $filePath
     * @param string $checksum
     */
    public function addFileChecksum($filePath, $checksum)
    {
        $this->filesChecksum[$filePath] = $checksum;
    }

    /**
     * @param string $filePath
     */
    public function getFileChecksum($filePath)
    {
        return $this->filesChecksum[$filePath];
    }

    /**
     * @return bool
     */
    public function getObjectCacheSkipped()
    {
        return $this->objectCacheSkipped;
    }

    /**
     * @param bool $objectCacheSkipped
     */
    public function setObjectCacheSkipped($objectCacheSkipped)
    {
        $this->objectCacheSkipped = $objectCacheSkipped;
    }
}
