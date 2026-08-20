<?php

namespace WPStaging\Backup\Ajax\FileList;

use SplFileInfo;
use Throwable;
use WPStaging\Backup\BackupValidator;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Entity\ListableBackup;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\WithBackupIdentifier;
use WPStaging\Framework\Adapter\DateTimeAdapter;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Utils\Urls;












class ListableBackupsCollection
{
    use WithBackupIdentifier;

 
    private $dateTimeAdapter;

 
    private $backupsFinder;

 
    private $urls;

 
    private $backupValidator;

    public function __construct(
        DateTimeAdapter $dateTimeAdapter,
        BackupsFinder $backupsFinder,
        Urls $urls,
        BackupValidator $backupValidator
    ) {
        $this->dateTimeAdapter = $dateTimeAdapter;
        $this->backupsFinder   = $backupsFinder;
        $this->urls            = $urls;
        $this->backupValidator = $backupValidator;
    }




    public function getListableBackups()
    {
        $backupFiles = $this->backupsFinder->findBackups();

        if (empty($backupFiles)) {
            return [];
        }

        $backups = [];

        $this->clearListedMultipartBackups();

 
        foreach ($backupFiles as $file) {
            $md5Basename = md5($file->getBasename());

            if (array_key_exists($md5Basename, $backups)) {
                continue;
            }

            $downloadUrl  = $this->urls->getBackupUrl() . $file->getFilename();
            $relativePath = $file->getBasename();

            if ($this->isBackupPart($relativePath) && $this->isListedMultipartBackup($file->getFilename())) {
                continue;
            }

            if ($file->getExtension() === 'wpstg' || $this->isBackupPart($relativePath)) {
                $listableBackup = $this->getWpstgBackup($file, $md5Basename, $downloadUrl, $relativePath);
            } elseif ($file->getExtension() === 'sql') {
                $listableBackup = $this->getSqlBackup($file, $md5Basename, $downloadUrl);
            } else {
                continue;
            }

            $backups[$md5Basename] = $listableBackup;
        }

        return $backups;
    }









    public function getSortedListableBackups(): array
    {
        $backups = $this->getListableBackups();

        usort($backups, function ($a, $b) {
 
 
            $timestampA = max($a->dateUploadedTimestamp, $a->dateCreatedTimestamp);
            $timestampB = max($b->dateUploadedTimestamp, $b->dateCreatedTimestamp);
            return $timestampB - $timestampA;
        });

        return $backups;
    }

    private function getWpstgBackup(SplFileInfo $file, string $md5Basename, string $downloadUrl, string $relativePath): ListableBackup
    {
        try {
            $backupMetadata = new BackupMetadata();
            $backupMetadata = $backupMetadata->hydrateByFilePath($file->getRealPath());
        } catch (Throwable $e) {
            return $this->createCorruptBackup($file, $md5Basename, $downloadUrl, $relativePath);
        }

        return $this->populateListableBackup($file, $backupMetadata, $downloadUrl, $relativePath, $md5Basename);
    }

    private function createCorruptBackup(SplFileInfo $file, string $md5Basename, string $downloadUrl, string $relativePath): ListableBackup
    {
        $listableBackup                        = new ListableBackup();
        $listableBackup->dateCreatedTimestamp  = $file->getMTime();
        $listableBackup->dateCreatedFormatted  = $this->dateTimeAdapter->transformToWpFormat((new \DateTime())->setTimestamp($file->getMTime()));
        $listableBackup->dateUploadedTimestamp = $file->getCTime();
        $listableBackup->dateUploadedFormatted = $this->dateTimeAdapter->transformToWpFormat((new \DateTime())->setTimestamp($file->getCTime()));
        $listableBackup->downloadUrl           = $downloadUrl;
        $listableBackup->relativePath          = $relativePath;
        $listableBackup->backupName            = $relativePath;
        $listableBackup->name                  = $file->getFilename();
        $listableBackup->size                  = (int)$file->getSize();
        $listableBackup->id                    = $md5Basename;
        $listableBackup->md5BaseName           = $md5Basename;
        $listableBackup->isCorrupt             = true;
        $listableBackup->isMultipartBackup     = false;

        return $listableBackup;
    }

    private function getSqlBackup(SplFileInfo $file, string $md5Basename, string $downloadUrl): ListableBackup
    {
        $listableBackup                      = new ListableBackup();
        $listableBackup->isLegacy            = true;
        $listableBackup->isExportingDatabase = true;
        $listableBackup->backupName          = $file->getBasename();
        $listableBackup->downloadUrl         = $downloadUrl;
        $listableBackup->name                = $file->getFilename();
        $listableBackup->size                = (int)$file->getSize();
        $listableBackup->md5BaseName         = $md5Basename;

        return $listableBackup;
    }

    protected function populateListableBackup(SplFileInfo $file, BackupMetadata $backupMetadata, string $downloadUrl, string $relativePath, string $md5Basename): ListableBackup
    {
        try {
            $fileObject                                       = new FileObject($file->getRealPath());
            $listableBackup                                   = new ListableBackup();
            $listableBackup->type                             = $backupMetadata->getBackupType();
            $listableBackup->subsiteType                      = $listableBackup->type === 'single' ? '' : ($backupMetadata->getSubdomainInstall() ? 'Subdomains' : 'Subdirectories');
            $listableBackup->networkSitesCount                = $this->getNetworkSitesCount($backupMetadata);
            $listableBackup->automatedBackup                  = $backupMetadata->getIsAutomatedBackup();
            $listableBackup->scheduleRecurrence               = $backupMetadata->getScheduleRecurrence();
            $listableBackup->backupName                       = $backupMetadata->getName();
            $listableBackup->dateCreatedTimestamp             = intval($backupMetadata->getDateCreated());
            $listableBackup->dateCreatedFormatted             = $this->dateTimeAdapter->transformToWpFormat((new \DateTime())->setTimestamp((int)$backupMetadata->getDateCreated()));
            $listableBackup->dateUploadedTimestamp            = $file->getCTime();
            $listableBackup->dateUploadedFormatted            = $this->dateTimeAdapter->transformToWpFormat((new \DateTime())->setTimestamp($file->getCTime()));
            $listableBackup->downloadUrl                      = $downloadUrl;
            $listableBackup->relativePath                     = $relativePath;
            $listableBackup->id                               = $backupMetadata->getId();
            $listableBackup->isExportingDatabase              = $backupMetadata->getIsExportingDatabase();
            $listableBackup->isExportingMuPlugins             = $backupMetadata->getIsExportingMuPlugins();
            $listableBackup->isExportingOtherWpContentFiles   = $backupMetadata->getIsExportingOtherWpContentFiles();
            $listableBackup->isExportingOtherWpRootFiles      = $backupMetadata->getIsExportingOtherWpRootFiles();
            $listableBackup->isExportingPlugins               = $backupMetadata->getIsExportingPlugins();
            $listableBackup->isExportingThemes                = $backupMetadata->getIsExportingThemes();
            $listableBackup->isExportingUploads               = $backupMetadata->getIsExportingUploads();
            $listableBackup->isMultipartBackup                = $backupMetadata->getIsMultipartBackup();
            $listableBackup->generatedOnWPStagingVersion      = $backupMetadata->getWpstgVersion();
            $listableBackup->generatedOnBackupVersion         = $backupMetadata->getBackupVersion();
            $listableBackup->createdOnPro                     = $backupMetadata->getCreatedOnPro();
            $listableBackup->name                             = $file->getFilename();
            $listableBackup->notes                            = $backupMetadata->getNote();
            $listableBackup->size                             = $backupMetadata->getBackupSize();
            $listableBackup->md5BaseName                      = $md5Basename;
            $listableBackup->isValidFileIndex                 = $this->backupValidator->validateFileIndex($fileObject, $backupMetadata);
            $listableBackup->isValidMultipartBackup           = $this->backupValidator->checkIfSplitBackupIsValid($backupMetadata, $file->getFilename());
            $listableBackup->isUnsupported                    = $this->backupValidator->isUnsupportedBackupVersion($backupMetadata);
            $listableBackup->validationIssues['sizeIssues']   = $this->backupValidator->getPartSizeIssues();
            $listableBackup->validationIssues['missingParts'] = $this->backupValidator->getMissingPartIssues();
            $listableBackup->existingBackupParts              = $listableBackup->isMultipartBackup ? $backupMetadata->getMultipartMetadata()->getBackupParts() : [];
            $listableBackup->errorMessage                     = $this->backupValidator->getErrorMessage();
            $listableBackup->indexPartSize                    = $backupMetadata->getIndexPartSize();
            $listableBackup->isZlibCompressed                 = $backupMetadata->getIsZlibCompressed();
            $listableBackup->isContaining2GBFile              = $backupMetadata->getIsContaining2GBFile();

            if (empty($backupMetadata->getBackupSize())) {
                $listableBackup->size             = (int)$file->getSize();
                $listableBackup->isUnsignedBackup = true;
            }

            return $listableBackup;
        } catch (Throwable $exception) {
            $listableBackup = $this->createCorruptBackup($file, $md5Basename, $downloadUrl, $relativePath);
            if (empty($backupMetadata->getBackupSize())) {
                $listableBackup->isUnsignedBackup = true;
            }

            return $listableBackup;
        }
    }





    private function getNetworkSitesCount(BackupMetadata $backupMetadata): int
    {
        if ($backupMetadata->getBackupType() !== BackupMetadata::BACKUP_TYPE_MULTISITE) {
            return 0;
        }

        $sites = $backupMetadata->getSites();

        return is_array($sites) ? count($sites) : 0;
    }
}
