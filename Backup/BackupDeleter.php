<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Framework\Filesystem\FileObject;

class BackupDeleter
{
    protected $backupsFinder;
    protected $backupMetadata;

    protected $errors = [];

    protected $deletingAutomatedDatabaseOnlyBackup = false;

    public function __construct(BackupsFinder $backupsFinder, BackupMetadata $backupMetadata)
    {
        $this->backupsFinder  = $backupsFinder;
        $this->backupMetadata = $backupMetadata;
    }

    /** @return array */
    public function getErrors()
    {
        return $this->errors;
    }

    public function clearErrors()
    {
        $this->errors = [];
        $this->deletingAutomatedDatabaseOnlyBackup = false;
    }

    /**
     * Used by unit test tests/wpunit/Backup/BackupSchedulerTest.php
     * @return void
     */
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

    /**
     * @param FileObject $backup
     * @param BackupMetadata $metadata
     */
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

        $backupDirectory = $this->backupsFinder->getBackupsDirectory();
        foreach ($metadata->getMultipartMetadata()->getBackupParts() as $part) {
            $partPath = $backupDirectory . $part;
            if (!file_exists($partPath)) {
                continue;
            }

            $partName = str_replace(dirname($partPath), '', $partPath);
            $deleted = unlink($partPath);
            if (!$deleted) {
                $this->errors[] = sprintf(__('Unable to delete %s split backup part: %s', 'wp-staging'), $additionalLog, $partName);
            }
        }
    }
}
