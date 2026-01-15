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
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Utils\Urls;

/**
 * Collects and prepares backup files for display in the backup list UI
 *
 * This class is responsible for:
 * - Finding all backup files in the backup directory (both generated and uploaded)
 * - Hydrating backup metadata from .wpstg files
 * - Validating backup integrity (file index, multipart structure, corruption checks)
 * - Converting backup files into ListableBackup entities for UI rendering
 * - Handling legacy .sql backup files
 * - Preventing duplicate listings when files exist in multiple locations
 *
 * The class works with BackupsFinder to locate files and BackupValidator to ensure
 * backup integrity before presenting them to users.
 */
class ListableBackupsCollection
{
    use WithBackupIdentifier;

    private $directory;
    private $dateTimeAdapter;
    private $backupsFinder;
    private $filesystem;
    private $urls;

    /** @var BackupValidator */
    private $backupValidator;

    public function __construct(DateTimeAdapter $dateTimeAdapter, BackupsFinder $backupsFinder, Directory $directory, Filesystem $filesystem, Urls $urls, BackupValidator $backupValidator)
    {
        $this->dateTimeAdapter = $dateTimeAdapter;
        $this->directory       = $directory;
        $this->backupsFinder   = $backupsFinder;
        $this->filesystem      = $filesystem;
        $this->urls            = $urls;
        $this->backupValidator = $backupValidator;
    }

    /**
     * @return array<ListableBackup>
     */
    public function getListableBackups()
    {
        $backupFiles = $this->backupsFinder->findBackups();

        // Early bail: No backup files found.
        if (empty($backupFiles)) {
            return [];
        }

        $backups = [];

        $this->clearListedMultipartBackups();

        /** @var SplFileInfo $file */
        foreach ($backupFiles as $file) {
            $md5Basename = md5($file->getBasename());

            /*
             * Prevent listing the same file twice if it's generated and also uploaded.
             * Uploaded files takes precedence as their iterator is appended first.
             */
            if (array_key_exists($md5Basename, $backups)) {
                continue;
            }

            $downloadUrl = $this->urls->getBackupUrl() . $file->getFilename();

            $relativePath = $file->getBasename();

            if ($this->isBackupPart($relativePath) && $this->isListedMultipartBackup($file->getFilename())) {
                continue;
            }

            if ($file->getExtension() === 'wpstg' || $this->isBackupPart($relativePath)) {
                try {
                    $backupMetadata = new BackupMetadata();
                    $backupMetadata = $backupMetadata->hydrateByFilePath($file->getRealPath());
                } catch (Throwable $e) {
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
                    $backups[$md5Basename]                 = $listableBackup;

                    continue;
                }

                $listableBackup = $this->populateListableBackup($file, $backupMetadata, $downloadUrl, $relativePath, $md5Basename);
            } elseif ($file->getExtension() === 'sql') {
                $listableBackup                      = new ListableBackup();
                $listableBackup->isLegacy            = true;
                $listableBackup->isExportingDatabase = true;
                $listableBackup->backupName          = $file->getBasename();
                $listableBackup->downloadUrl         = $downloadUrl;
                $listableBackup->name                = $file->getFilename();
                $listableBackup->size                = (int)$file->getSize();
                $listableBackup->md5BaseName         = $md5Basename;
            } else {
                continue;
            }

            $backups[$md5Basename] = $listableBackup;
        }

        return $backups;
    }

    protected function populateListableBackup(SplFileInfo $file, BackupMetadata $backupMetadata, string $downloadUrl, string $relativePath, string $md5Basename): ListableBackup
    {
        try {
            $fileObject                                       = new FileObject($file->getRealPath());
            $listableBackup                                   = new ListableBackup();
            $listableBackup->type                             = $backupMetadata->getBackupType();
            $listableBackup->subsiteType                      = $listableBackup->type === 'single' ? '' : ($backupMetadata->getSubdomainInstall() ? 'Subdomains' : 'Subdirectories');
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
            $listableBackup->isValidMultipartBackup           = $this->backupValidator->checkIfSplitBackupIsValid($backupMetadata);
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
            if (empty($backupMetadata->getBackupSize())) {
                $listableBackup->isUnsignedBackup = true;
            }

            return $listableBackup;
        }
    }
}
