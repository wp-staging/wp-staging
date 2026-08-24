<?php

namespace WPStaging\Backup\Dto\Job;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Framework\Job\Dto\JobDataDto;

class JobRestoreDataDto extends JobDataDto
{
 
    protected $file;

 
    protected $isDataDownloaded = false;

 
    protected $backupMetadata;

 
    protected $tmpDirectory;

 
    protected $extractorFilesExtracted = 0;

 
    protected $databaseFileOffset = 0;

 
    protected $databaseFileOffsetLine = 0;

 
    protected $extractorFileWrittenBytes = 0;

    protected $extractorMetadataIndexPosition = 0;

 
    protected $tmpDatabasePrefix;

 
    protected $tableToRestore;

 
    protected $transactionStarted;

 
    protected $shortNamesTablesToDrop = [];

 
    protected $shortNamesTablesToRestore = [];

 
    protected $requireShortNamesForTablesToDrop = false;

 
    protected $requireShortNamesForTablesToRestore = false;

 
    protected $databasePartIndex = 0;

 
    protected $filePartIndex = 0;

 
    protected $isSameSiteBackupRestore = false;

 
    protected $isUrlSchemeMatched = false;

 
    protected $isMissingDatabaseFile = false;

 
    protected $currentFileHeaderStart = 0;

 
    protected $databaseDataToPreserve = [];

 
    protected $totalTablesToRename = 0;

 
    protected $totalTablesRenamed = 0;






    protected $filesChecksum = [];

 
    protected $objectCacheSkipped = false;

 
    protected $isDatabaseRestoreSkipped = false;

 
    protected $caughtExceptionRetries = 0;




    public function getFile(): string
    {
        return $this->file;
    }







    public function setFile(string $file)
    {
        $this->file = untrailingslashit(wp_normalize_path($file));
    }




    public function getIsDataDownloaded(): bool
    {
        return $this->isDataDownloaded;
    }





    public function setIsDataDownloaded(bool $isDataDownloaded)
    {
        $this->isDataDownloaded = $isDataDownloaded;
    }




    public function getBackupMetadata()
    {
        return $this->backupMetadata;
    }





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




    public function getTmpDirectory(): string
    {
        return $this->tmpDirectory;
    }





    public function setTmpDirectory($tmpPath)
    {
        $this->tmpDirectory = is_null($tmpPath) ? null : trailingslashit(wp_normalize_path($tmpPath));
    }




    public function getExtractorFilesExtracted(): int
    {
        return $this->extractorFilesExtracted;
    }





    public function setExtractorFilesExtracted(int $extractorFilesExtracted)
    {
        $this->extractorFilesExtracted = $extractorFilesExtracted;
    }




    public function incrementExtractorFilesExtracted()
    {
        $this->extractorFilesExtracted++;
    }




    public function getExtractorFileWrittenBytes(): int
    {
        return $this->extractorFileWrittenBytes;
    }





    public function setExtractorFileWrittenBytes(int $fileWrittenBytes)
    {
        $this->extractorFileWrittenBytes = $fileWrittenBytes;
    }




    public function getExtractorMetadataIndexPosition(): int
    {
        return $this->extractorMetadataIndexPosition;
    }





    public function setExtractorMetadataIndexPosition(int $extractorMetadataIndexPosition)
    {
        $this->extractorMetadataIndexPosition = $extractorMetadataIndexPosition;
    }




    public function getDatabaseFileOffset(): int
    {
        return $this->databaseFileOffset;
    }





    public function setDatabaseFileOffset(int $databaseFileOffset)
    {
        $this->databaseFileOffset = $databaseFileOffset;
    }




    public function getDatabaseFileOffsetLine(): int
    {
        return $this->databaseFileOffsetLine;
    }





    public function setDatabaseFileOffsetLine(int $databaseFileOffsetLine)
    {
        $this->databaseFileOffsetLine = $databaseFileOffsetLine;
    }




    public function getTmpDatabasePrefix(): string
    {
        return $this->tmpDatabasePrefix;
    }





    public function setTmpDatabasePrefix(string $tmpDatabasePrefix)
    {
        $this->tmpDatabasePrefix = $tmpDatabasePrefix;
    }




    public function getTableToRestore()
    {
        return $this->tableToRestore;
    }




    public function setTableToRestore($tableToRestore)
    {
        $this->tableToRestore = $tableToRestore;
    }




    public function getTransactionStarted(): bool
    {
        return $this->transactionStarted;
    }





    public function setTransactionStarted($transactionStarted)
    {
        $this->transactionStarted = $transactionStarted;
    }




    public function getShortNamesTablesToDrop(): array
    {
        return $this->shortNamesTablesToDrop;
    }





    public function setShortNamesTablesToDrop(array $tables = [])
    {
        $this->shortNamesTablesToDrop = $tables;
    }






    public function addShortNameTableToDrop(string $originalName, string $shorterName)
    {
        $this->shortNamesTablesToDrop[$shorterName] = $originalName;
    }




    public function getShortNamesTablesToRestore(): array
    {
        return $this->shortNamesTablesToRestore;
    }





    public function setShortNamesTablesToRestore(array $tables = [])
    {
        $this->shortNamesTablesToRestore = $tables;
    }






    public function addShortNameTableToRestore(string $originalName, string $shorterName)
    {
        $this->shortNamesTablesToRestore[$shorterName] = $originalName;
    }




    public function getRequireShortNamesForTablesToRestore(): bool
    {
        return $this->requireShortNamesForTablesToRestore;
    }





    public function setRequireShortNamesForTablesToRestore(bool $require = false)
    {
        $this->requireShortNamesForTablesToRestore = $require;
    }




    public function getRequireShortNamesForTablesToDrop(): bool
    {
        return $this->requireShortNamesForTablesToDrop;
    }





    public function setRequireShortNamesForTablesToDrop(bool $require = false)
    {
        $this->requireShortNamesForTablesToDrop = $require;
    }




    public function getDatabasePartIndex(): int
    {
        return $this->databasePartIndex;
    }





    public function setDatabasePartIndex(int $index = 0)
    {
        $this->databasePartIndex = $index;
    }




    public function getIsSameSiteBackupRestore(): bool
    {
        return $this->isSameSiteBackupRestore;
    }





    public function setIsSameSiteBackupRestore(bool $sameSite)
    {
        $this->isSameSiteBackupRestore = $sameSite;
    }




    public function getIsUrlSchemeMatched(): bool
    {
        return $this->isUrlSchemeMatched;
    }





    public function setIsUrlSchemeMatched(bool $matched)
    {
        $this->isUrlSchemeMatched = $matched;
    }




    public function getIsMissingDatabaseFile(): bool
    {
        return $this->isMissingDatabaseFile;
    }





    public function setIsMissingDatabaseFile(bool $missingFile)
    {
        $this->isMissingDatabaseFile = $missingFile;
    }




    public function getFilePartIndex(): int
    {
        return $this->filePartIndex;
    }





    public function setFilePartIndex(int $index = 0)
    {
        $this->filePartIndex = $index;
    }




    public function getCurrentFileHeaderStart(): int
    {
        return $this->currentFileHeaderStart;
    }





    public function setCurrentFileHeaderStart($headerStart = 0)
    {
        $this->currentFileHeaderStart = $headerStart;
    }




    public function getDatabaseDataToPreserve(): array
    {
        return $this->databaseDataToPreserve;
    }





    public function setDatabaseDataToPreserve(array $databaseDataToPreserve)
    {
        $this->databaseDataToPreserve = $databaseDataToPreserve;
    }




    public function getTotalTablesToRename(): int
    {
        return $this->totalTablesToRename;
    }





    public function setTotalTablesToRename(int $totalTablesToRename)
    {
        $this->totalTablesToRename = $totalTablesToRename;
    }




    public function getTotalTablesRenamed(): int
    {
        return $this->totalTablesRenamed;
    }





    public function setTotalTablesRenamed(int $totalTablesRenamed)
    {
        $this->totalTablesRenamed = $totalTablesRenamed;
    }




    public function getFilesChecksum(): array
    {
        return $this->filesChecksum;
    }




    public function setFilesChecksum(array $filesChecksum)
    {
        $this->filesChecksum = $filesChecksum;
    }





    public function addFileChecksum(string $filePath, string $checksum)
    {
        $this->filesChecksum[$filePath] = $checksum;
    }





    public function getFileChecksum(string $filePath): string
    {
        if (array_key_exists($filePath, $this->filesChecksum)) {
            return $this->filesChecksum[$filePath];
        }

        return '';
    }




    public function getObjectCacheSkipped(): bool
    {
        return $this->objectCacheSkipped;
    }





    public function setObjectCacheSkipped(bool $objectCacheSkipped)
    {
        $this->objectCacheSkipped = $objectCacheSkipped;
    }




    public function getIsDatabaseRestoreSkipped(): bool
    {
        return $this->isDatabaseRestoreSkipped;
    }





    public function setIsDatabaseRestoreSkipped(bool $isDatabaseRestoreSkipped)
    {
        $this->isDatabaseRestoreSkipped = $isDatabaseRestoreSkipped;
    }




    public function getCaughtExceptionRetries(): int
    {
        return $this->caughtExceptionRetries;
    }





    public function setCaughtExceptionRetries(int $caughtExceptionRetries)
    {
        $this->caughtExceptionRetries = $caughtExceptionRetries;
    }






    public function determineIsSameSiteRestore()
    {
        $this->setIsUrlSchemeMatched(true);

 
        if (is_multisite() && is_subdomain_install() !== $this->backupMetadata->getSubdomainInstall()) {
            $this->setIsSameSiteBackupRestore(false);
            return;
        }

 
        if (ABSPATH !== $this->backupMetadata->getAbsPath()) {
            $this->setIsSameSiteBackupRestore(false);
            return;
        }

        $currentSiteURL = site_url();
        $backupSiteURL  = $this->backupMetadata->getSiteUrl();
        if ($currentSiteURL === $backupSiteURL) {
            $this->setIsSameSiteBackupRestore(true);
            return;
        }

        $currentSiteURLWithoutScheme = preg_replace('#^https?://#', '', rtrim($currentSiteURL, '/'));
        $backupSiteURLWithoutScheme  = preg_replace('#^https?://#', '', rtrim($backupSiteURL, '/'));
        if ($currentSiteURLWithoutScheme === $backupSiteURLWithoutScheme) {
            $this->setIsUrlSchemeMatched(false);
        }

        $this->setIsSameSiteBackupRestore(false);
    }
}
