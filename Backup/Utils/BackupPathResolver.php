<?php

namespace WPStaging\Backup\Utils;

use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\WithBackupIdentifier;

/**
 * Utility for resolving backup file paths securely within the backups directory.
 */
class BackupPathResolver
{
    use WithBackupIdentifier;

    /**
     * @var BackupsFinder
     */
    private $backupsFinder;

    /**
     * @param BackupsFinder $backupsFinder
     */
    public function __construct(BackupsFinder $backupsFinder)
    {
        $this->backupsFinder = $backupsFinder;
    }

    /**
     * Resolve a backup file path securely within the backups directory.
     *
     * @param string $filePath
     * @return string Empty string if invalid, or the resolved path
     */
    public function resolveBackupPath(string $filePath): string
    {
        $backupDir = wp_normalize_path($this->backupsFinder->getBackupsDirectory());
        $filePath  = wp_normalize_path(untrailingslashit($filePath));

        $resolvedPath = $backupDir . str_replace($backupDir, '', $filePath);
        $resolvedPath = wp_normalize_path($resolvedPath);

        if (strpos($resolvedPath, $backupDir) !== 0) {
            return '';
        }

        $basename  = wp_basename($resolvedPath);
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        if (!in_array($extension, ['wpstg', 'sql'], true) && !$this->isBackupPart($basename)) {
            return '';
        }

        return $resolvedPath;
    }
}
