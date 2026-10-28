<?php

namespace WPStaging\Backup\Utils;

use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\WithBackupIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;




class BackupPathResolver
{
    use WithBackupIdentifier;




    private $backupsFinder;




    private $pathIdentifier;





    public function __construct(BackupsFinder $backupsFinder, PathIdentifier $pathIdentifier)
    {
        $this->backupsFinder  = $backupsFinder;
        $this->pathIdentifier = $pathIdentifier;
    }







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









    public function resolveRestorePartPath(string $partName, string $backupFilename): string
    {
        $isTemporaryPart = substr($partName, -strlen('.wpstgtmp')) === '.wpstgtmp';
        if (!$isTemporaryPart) {
            return $this->resolveBackupPartPath($partName, $backupFilename);
        }

        $jobId = strstr($partName, '.', true);
        if ($partName !== wp_basename($partName) || strpos($partName, "\0") !== false || $jobId === false || $jobId === '' || strpos($backupFilename, $jobId . '.') !== 0) {
            return '';
        }

        $path = wp_normalize_path(trailingslashit($this->backupsFinder->getBackupsDirectory()) . $partName);

        return is_link($path) ? '' : $path;
    }
}
