<?php

namespace WPStaging\Backup\Dto\Job;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Framework\Job\Dto\JobDataDto;

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

    /** @var array<string, string> Store short names tables to drop */
    private $shortNamesTablesToDrop = [];

    /** @var array<string, string> Store short names tables to restore */
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
    private $isUrlSchemeMatched = false;

    /** @var bool */
    private $isMissingDatabaseFile = false;

    /** @var int */
    private $currentFileHeaderStart = 0;

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

    /** @var bool */
    private $isDatabaseRestoreSkipped = false;

    /**
     * @return string The .wpstg backup file being restored.
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Called dynamically
     * @param string $file
     * @return void
     * @see \WPStaging\Backup\Ajax\Restore\PrepareRestore::setupInitialData
     */
    public function setFile(string $file)
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
     * @param BackupMetadata|array $backupMetadata
     * @return void
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
    public function getTmpDirectory(): string
    {
        return $this->tmpDirectory;
    }

    /**
     * @param string|null $tmpPath
     * @return void
     */
    public function setTmpDirectory($tmpPath)
    {
        $this->tmpDirectory = is_null($tmpPath) ? null : trailingslashit(wp_normalize_path($tmpPath));
    }

    /**
     * @return int
     */
    public function getExtractorFilesExtracted(): int
    {
        return $this->extractorFilesExtracted;
    }

    /**
     * @param int $extractorFilesExtracted
     * @return void
     */
    public function setExtractorFilesExtracted(int $extractorFilesExtracted)
    {
        $this->extractorFilesExtracted = $extractorFilesExtracted;
    }

    /**
     * @return void
     */
    public function incrementExtractorFilesExtracted()
    {
        $this->extractorFilesExtracted++;
    }

    /**
     * @return int
     */
    public function getExtractorFileWrittenBytes(): int
    {
        return $this->extractorFileWrittenBytes;
    }

    /**
     * @param int $fileWrittenBytes
     * @return void
     */
    public function setExtractorFileWrittenBytes(int $fileWrittenBytes)
    {
        $this->extractorFileWrittenBytes = $fileWrittenBytes;
    }

    /**
     * @return int
     */
    public function getExtractorMetadataIndexPosition(): int
    {
        return $this->extractorMetadataIndexPosition;
    }

    /**
     * @param int $extractorMetadataIndexPosition
     * @return void
     */
    public function setExtractorMetadataIndexPosition(int $extractorMetadataIndexPosition)
    {
        $this->extractorMetadataIndexPosition = $extractorMetadataIndexPosition;
    }

    /**
     * @return string
     */
    public function getTmpDatabasePrefix(): string
    {
        return $this->tmpDatabasePrefix;
    }

    /**
     * @param string $tmpDatabasePrefix
     * @return void
     */
    public function setTmpDatabasePrefix(string $tmpDatabasePrefix)
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
     * @param string|null $tableToRestore
     */
    public function setTableToRestore($tableToRestore)
    {
        $this->tableToRestore = $tableToRestore;
    }

    /**
     * @return bool
     */
    public function getTransactionStarted(): bool
    {
        return $this->transactionStarted;
    }

    /**
     * @param bool|null $transactionStarted
     * @return void
     */
    public function setTransactionStarted($transactionStarted)
    {
        $this->transactionStarted = $transactionStarted;
    }

    /**
     * @return array<string, string>
     */
    public function getShortNamesTablesToDrop(): array
    {
        return $this->shortNamesTablesToDrop;
    }

    /**
     * @param array<string, string> $tables
     * @return void
     */
    public function setShortNamesTablesToDrop(array $tables = [])
    {
        $this->shortNamesTablesToDrop = $tables;
    }

    /**
     * @param string $originalName
     * @param string $shorterName
     * @return void
     */
    public function addShortNameTableToDrop(string $originalName, string $shorterName)
    {
        $this->shortNamesTablesToDrop[$shorterName] = $originalName;
    }

    /**
     * @return array<string, string>
     */
    public function getShortNamesTablesToRestore(): array
    {
        return $this->shortNamesTablesToRestore;
    }

    /**
     * @param array<string, string> $tables
     * @return void
     */
    public function setShortNamesTablesToRestore(array $tables = [])
    {
        $this->shortNamesTablesToRestore = $tables;
    }

    /**
     * @param string $originalName
     * @param string $shorterName
     * @return void
     */
    public function addShortNameTableToRestore(string $originalName, string $shorterName)
    {
        $this->shortNamesTablesToRestore[$shorterName] = $originalName;
    }

    /**
     * @return bool
     */
    public function getRequireShortNamesForTablesToRestore(): bool
    {
        return $this->requireShortNamesForTablesToRestore;
    }

    /**
     * @param bool $require
     * @return void
     */
    public function setRequireShortNamesForTablesToRestore(bool $require = false)
    {
        $this->requireShortNamesForTablesToRestore = $require;
    }

    /**
     * @return bool
     */
    public function getRequireShortNamesForTablesToDrop(): bool
    {
        return $this->requireShortNamesForTablesToDrop;
    }

    /**
     * @param bool $require
     * @return void
     */
    public function setRequireShortNamesForTablesToDrop(bool $require = false)
    {
        $this->requireShortNamesForTablesToDrop = $require;
    }

    /**
     * @return int
     */
    public function getDatabasePartIndex(): int
    {
        return $this->databasePartIndex;
    }

    /**
     * @param int $index
     * @return void
     */
    public function setDatabasePartIndex(int $index = 0)
    {
        $this->databasePartIndex = $index;
    }

    /**
     * @return bool
     */
    public function getIsSameSiteBackupRestore(): bool
    {
        return $this->isSameSiteBackupRestore;
    }

    /**
     * @param bool $sameSite
     * @return void
     */
    public function setIsSameSiteBackupRestore(bool $sameSite)
    {
        $this->isSameSiteBackupRestore = $sameSite;
    }

    /**
     * @return bool
     */
    public function getIsUrlSchemeMatched(): bool
    {
        return $this->isUrlSchemeMatched;
    }

    /**
     * @param bool $matched
     * @return void
     */
    public function setIsUrlSchemeMatched(bool $matched)
    {
        $this->isUrlSchemeMatched = $matched;
    }

    /**
     * @return bool
     */
    public function getIsMissingDatabaseFile(): bool
    {
        return $this->isMissingDatabaseFile;
    }

    /**
     * @param bool $missingFile
     * @return void
     */
    public function setIsMissingDatabaseFile(bool $missingFile)
    {
        $this->isMissingDatabaseFile = $missingFile;
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
     * @return int
     */
    public function getCurrentFileHeaderStart(): int
    {
        return $this->currentFileHeaderStart;
    }

    /**
     * @param int|null $headerStart
     * @return void
     */
    public function setCurrentFileHeaderStart($headerStart = 0)
    {
        $this->currentFileHeaderStart = $headerStart;
    }

    /**
     * @return int
     */
    public function getCurrentExecutionTimeDatabaseRestore(): int
    {
        $time = $this->currentExecutionTimeDatabaseRestore;
        if ($time < 10) {
            return 10;
        }

        return $time;
    }

    /**
     * @return void
     */
    public function incrementCurrentExecutionTimeDatabaseRestore()
    {
        $this->currentExecutionTimeDatabaseRestore += 5;
    }

    /**
     * @param int $currentExecutionTimeDatabaseRestore
     * @return void
     */
    public function setCurrentExecutionTimeDatabaseRestore($currentExecutionTimeDatabaseRestore = 0)
    {
        $this->currentExecutionTimeDatabaseRestore = $currentExecutionTimeDatabaseRestore;
    }

    /**
     * @return array
     */
    public function getDatabaseDataToPreserve(): array
    {
        return $this->databaseDataToPreserve;
    }

    /**
     * @param array $databaseDataToPreserve
     * @return void
     */
    public function setDatabaseDataToPreserve(array $databaseDataToPreserve)
    {
        $this->databaseDataToPreserve = $databaseDataToPreserve;
    }

    /**
     * @return int
     */
    public function getTotalTablesToRename(): int
    {
        return $this->totalTablesToRename;
    }

    /**
     * @param int $totalTablesToRename
     * @return void
     */
    public function setTotalTablesToRename(int $totalTablesToRename)
    {
        $this->totalTablesToRename = $totalTablesToRename;
    }

    /**
     * @return int
     */
    public function getTotalTablesRenamed(): int
    {
        return $this->totalTablesRenamed;
    }

    /**
     * @param int $totalTablesRenamed
     * @return void
     */
    public function setTotalTablesRenamed(int $totalTablesRenamed)
    {
        $this->totalTablesRenamed = $totalTablesRenamed;
    }

    /**
     * @return array<string, string>
     */
    public function getFilesChecksum(): array
    {
        return $this->filesChecksum;
    }

    /**
     * @param array<string, string> $filesChecksum
     */
    public function setFilesChecksum(array $filesChecksum)
    {
        $this->filesChecksum = $filesChecksum;
    }

    /**
     * @param string $filePath
     * @param string $checksum
     */
    public function addFileChecksum(string $filePath, string $checksum)
    {
        $this->filesChecksum[$filePath] = $checksum;
    }

    /**
     * @param string $filePath
     * @return string
     */
    public function getFileChecksum(string $filePath): string
    {
        if (array_key_exists($filePath, $this->filesChecksum)) {
            return $this->filesChecksum[$filePath];
        }

        return '';
    }

    /**
     * @return bool
     */
    public function getObjectCacheSkipped(): bool
    {
        return $this->objectCacheSkipped;
    }

    /**
     * @param bool $objectCacheSkipped
     * @return void
     */
    public function setObjectCacheSkipped(bool $objectCacheSkipped)
    {
        $this->objectCacheSkipped = $objectCacheSkipped;
    }

    /**
     * @return bool
     */
    public function getIsDatabaseRestoreSkipped(): bool
    {
        return $this->isDatabaseRestoreSkipped;
    }

    /**
     * @param bool $isDatabaseRestoreSkipped
     * @return void
     */
    public function setIsDatabaseRestoreSkipped(bool $isDatabaseRestoreSkipped)
    {
        $this->isDatabaseRestoreSkipped = $isDatabaseRestoreSkipped;
    }
}
