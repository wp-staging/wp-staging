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
                    $listableBackup->size                  = size_format($file->getSize(), 2); // @phpstan-ignore-line
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
                $listableBackup->size                = size_format($file->getSize(), 2); // @phpstan-ignore-line
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
            $listableBackup->backupName                       = $backupMetadata->getName();
            $listableBackup->dateCreatedTimestamp             = intval($backupMetadata->getDateCreated());
            $listableBackup->dateCreatedFormatted             = $this->dateTimeAdapter->transformToWpFormat((new \DateTime())->setTimestamp($backupMetadata->getDateCreated()));
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
            $listableBackup->size                             = size_format($backupMetadata->getBackupSize(), 2); // @phpstan-ignore-line
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
            $listableBackup->size                  = size_format($file->getSize(), 2); // @phpstan-ignore-line
            $listableBackup->id                    = $md5Basename;
            $listableBackup->md5BaseName           = $md5Basename;
            $listableBackup->isCorrupt             = true;
            $listableBackup->isMultipartBackup     = false;

            return $listableBackup;
        }
    }
}
