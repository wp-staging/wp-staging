<?php

namespace WPStaging\Backup;

use SplFileInfo;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Utils\BackupPathResolver;

class BackupDeleter
{
 
    protected $backupsFinder;

 
    protected $backupMetadata;

 
    protected $backupPathResolver;

 
    protected $errors = [];

 
    protected $deletingAutomatedDatabaseOnlyBackup = false;

    public function __construct(BackupsFinder $backupsFinder, BackupMetadata $backupMetadata, BackupPathResolver $backupPathResolver)
    {
        $this->backupsFinder      = $backupsFinder;
        $this->backupMetadata     = $backupMetadata;
        $this->backupPathResolver = $backupPathResolver;
    }

 
    public function getErrors()
    {
        return $this->errors;
    }

    public function clearErrors()
    {
        $this->errors                              = [];
        $this->deletingAutomatedDatabaseOnlyBackup = false;
    }





    public function deleteAllBackups()
    {
        $this->clearErrors();

        foreach ($this->backupsFinder->findBackups() as $backup) {
            $this->deleteBackup($backup);
        }
    }

    public function deleteAllAutomatedDbOnlyBackups()
    {
        $this->clearErrors();
        $this->deletingAutomatedDatabaseOnlyBackup = true;

        foreach ($this->backupsFinder->findBackups() as $backup) {
            $metadata = $this->backupMetadata->hydrateByFilePath($backup->getRealPath());
            if (
                $metadata->getIsAutomatedBackup() &&
                $metadata->getIsExportingDatabase() &&
                !$metadata->getIsExportingMuPlugins() &&
                !$metadata->getIsExportingPlugins() &&
                !$metadata->getIsExportingThemes() &&
                !$metadata->getIsExportingUploads() &&
                !$metadata->getIsExportingOtherWpContentFiles() &&
                !$metadata->getIsExportingOtherWpRootFiles()
            ) {
                $this->deleteBackup($backup, $metadata);
            }
        }
    }





    public function deleteAllAutomatedUploadsOnlyBackups()
    {
        $this->clearErrors();

        foreach ($this->backupsFinder->findBackups() as $backup) {
            $metadata = $this->backupMetadata->hydrateByFilePath($backup->getRealPath());
            if (
                $metadata->getIsAutomatedBackup() &&
                $metadata->getIsExportingUploads() &&
                !$metadata->getIsExportingDatabase() &&
                !$metadata->getIsExportingMuPlugins() &&
                !$metadata->getIsExportingPlugins() &&
                !$metadata->getIsExportingThemes() &&
                !$metadata->getIsExportingOtherWpContentFiles() &&
                !$metadata->getIsExportingOtherWpRootFiles()
            ) {
                $this->deleteBackup($backup, $metadata);
            }
        }
    }





    public function deleteAllAutomatedPushBackups()
    {
        $this->clearErrors();

        foreach ($this->backupsFinder->findBackups() as $backup) {
            $metadata = $this->backupMetadata->hydrateByFilePath($backup->getRealPath());
            if (
                $metadata->getIsAutomatedBackup() &&
                $metadata->getIsExportingDatabase() &&
                $metadata->getIsExportingUploads() &&
                !$metadata->getIsExportingMuPlugins() &&
                !$metadata->getIsExportingPlugins() &&
                !$metadata->getIsExportingThemes() &&
                !$metadata->getIsExportingOtherWpContentFiles() &&
                !$metadata->getIsExportingOtherWpRootFiles()
            ) {
                $this->deleteBackup($backup, $metadata);
            }
        }
    }





    public function deleteBackup($backup, $metadata = null)
    {
        $additionalLog = '';
        if ($this->deletingAutomatedDatabaseOnlyBackup) {
            $additionalLog = 'database-only automated';
        }

        if ($metadata === null) {
            $metadata = $this->backupMetadata->hydrateByFilePath($backup->getRealPath());
        }

        if (!$metadata->getIsMultipartBackup()) {
            $deleted = unlink($backup->getRealPath());
            if (!$deleted) {
                $this->errors[] = sprintf(__('Unable to delete %s backup: %s', 'wp-staging'), $additionalLog, $backup->getFilename());
            }

            return;
        }

        foreach ($metadata->getMultipartMetadata()->getBackupParts() as $part) {
            $partPath = $this->backupPathResolver->resolveBackupPartPath($part, $backup->getFilename());
            if ($partPath === '') {
                $this->errors[] = sprintf(__('Skipped a backup part that does not belong to this backup: %s', 'wp-staging'), esc_html($part));
                continue;
            }

            if (!file_exists($partPath)) {
                continue;
            }

            $deleted = unlink($partPath);
            if (!$deleted) {
                $this->errors[] = sprintf(__('Unable to delete %s split backup part: %s', 'wp-staging'), $additionalLog, wp_basename($partPath));
            }
        }
    }
}
