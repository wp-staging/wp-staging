<?php

namespace WPStaging\Backup\Utils;

use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\WithBackupIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;

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
     * @var PathIdentifier
     */
    private $pathIdentifier;

    /**
     * @param BackupsFinder $backupsFinder
     * @param PathIdentifier $pathIdentifier
     */
    public function __construct(BackupsFinder $backupsFinder, PathIdentifier $pathIdentifier)
    {
        $this->backupsFinder  = $backupsFinder;
        $this->pathIdentifier = $pathIdentifier;
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

        $relativePath = ltrim(str_replace($backupDir, '', $filePath), '/');
        $resolvedPath = wp_normalize_path(trailingslashit($backupDir) . $relativePath);

        if (!$this->pathIdentifier->isPathWithinRoot($resolvedPath, $backupDir)) {
            return '';
        }

        $basename  = wp_basename($resolvedPath);
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        if (!in_array($extension, ['wpstg', 'sql'], true) && !$this->isBackupPart($basename)) {
            return '';
        }

        return $resolvedPath;
    }

    /**
     * @param string $partName Part name as listed in the multipart metadata.
     * @param string $backupFilename Filename of the backup the part must belong to.
     * @return string Empty string if invalid, or the resolved path
     */
    public function resolveBackupPartPath(string $partName, string $backupFilename): string
    {
        if ($partName === '' || $partName !== wp_basename($partName)) {
            return '';
        }

        if (!$this->isBackupPart($partName)) {
            return '';
        }

        if ($this->extractBackupIdFromFilename($partName) !== $this->extractBackupIdFromFilename($backupFilename)) {
            return '';
        }

        return $this->resolveBackupPath($partName);
    }
}
