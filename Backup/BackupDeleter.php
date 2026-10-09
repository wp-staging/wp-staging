<?php

namespace WPStaging\Backup;

use SplFileInfo;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Transfer\TransferSessionLock;
use WPStaging\Backup\Utils\BackupPathResolver;
use WPStaging\Framework\Facades\Hooks;

use function WPStaging\functions\debug_log;




class BackupDeleter
{




    const FILTER_BEFORE_BACKUP_DELETED = 'wpstg.backup.before_backup_deleted';

 
    protected $backupsFinder;

 
    protected $backupMetadata;

 
    protected $backupPathResolver;

 
    protected $transferSessionLock;

 
    protected $errors = [];

    public function __construct(BackupsFinder $backupsFinder, BackupMetadata $backupMetadata, BackupPathResolver $backupPathResolver, TransferSessionLock $transferSessionLock)
    {
        $this->backupsFinder       = $backupsFinder;
        $this->backupMetadata      = $backupMetadata;
        $this->backupPathResolver  = $backupPathResolver;
        $this->transferSessionLock = $transferSessionLock;
    }

 
    public function getErrors()
    {
        return $this->errors;
    }

    public function clearErrors()
    {
        $this->errors = [];
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
                $metadata->getIsBeforePushBackup() &&
                empty($metadata->getScheduleId()) &&
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

            if (!$metadata->getIsAutomatedBackup() || !$metadata->getIsBeforePushBackup() || !empty($metadata->getScheduleId())) {
                continue;
            }

            $this->deleteBackup($backup, $metadata);
        }
    }





    public function deleteBackup($backup, $metadata = null)
    {
        if ($metadata === null) {
            $metadata = $this->backupMetadata->hydrateByFilePath($backup->getRealPath());
        }

        if (!$metadata->getIsMultipartBackup()) {
            $failureReason = $this->deleteBackupFile($backup->getRealPath());
            if ($failureReason !== '') {
                $this->errors[] = $failureReason;
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

            $failureReason = $this->deleteBackupFile($partPath);
            if ($failureReason !== '') {
                $this->errors[] = $failureReason;
            }
        }
    }

 
    public function getDeletionBlockReason(string $backupPath): string
    {
        return (string)Hooks::applyFilters(self::FILTER_BEFORE_BACKUP_DELETED, '', $backupPath);
    }







    public function deleteBackupFile(string $backupPath): string
    {
        $lockKey = $this->getTransferLockKey($backupPath);

        if (!$this->transferSessionLock->acquire($lockKey)) {
            return __('Another request is working on this backup right now, so it was kept. Try again in a moment.', 'wp-staging');
        }

        try {
            $blockReason = $this->getDeletionBlockReason($backupPath);
            if ($blockReason !== '') {
                return $blockReason;
            }

            if (!$this->unlinkBackupFileUnlessAlreadyGone($backupPath)) {
                return sprintf(__('Could not delete %s. Maybe a permission issue?', 'wp-staging'), wp_basename($backupPath));
            }

            return '';
        } finally {
            $this->transferSessionLock->release($lockKey);
        }
    }

 
    private function unlinkBackupFileUnlessAlreadyGone(string $backupPath): bool
    {
        clearstatcache(true, $backupPath);
        if (!file_exists($backupPath)) {
            return true;
        }

        set_error_handler(function ($severity, $message) {
            debug_log('WP STAGING: ' . $message);

            return true;
        });

        try {
            return unlink($backupPath);
        } finally {
            restore_error_handler();
        }
    }

 
    private function getTransferLockKey(string $backupPath): string
    {
        $realPath = realpath($backupPath);

        return wp_normalize_path($realPath === false ? $backupPath : $realPath);
    }
}
