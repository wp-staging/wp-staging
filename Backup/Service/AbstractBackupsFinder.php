<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\WithBackupIdentifier;
use WPStaging\Framework\Traits\DebugLogTrait;
use WPStaging\Framework\Traits\WindowsOsTrait;









abstract class AbstractBackupsFinder
{
    use WithBackupIdentifier;
    use DebugLogTrait;
    use WindowsOsTrait;

 
    const MAX_BACKUP_FILE_TO_SCAN = 1000;

 
    protected $backupsDirectory;

 
    protected $backupsCount;

    public function resetBackupsCount()
    {
        $this->backupsCount = 0;
    }





    public function setBackupsDirectory(string $backupsDirectory)
    {
        $this->backupsDirectory = $backupsDirectory;
    }





    public function getBackupsDirectory(bool $refresh = false): string
    {
        return $this->backupsDirectory;
    }




    public function findBackups(): array
    {
        try {
            $it = new \DirectoryIterator($this->getBackupsDirectory(true));
        } catch (\Exception $e) {
            $this->debugLog('WP STAGING: Could not find backup directory ' . $e->getMessage());
            return [];
        }

        $backups = [];

        $this->clearListedMultipartBackups();

 
        foreach ($it as $file) {
            if (($file->getExtension() === 'wpstg' || $file->getExtension() === 'sql') && !$file->isLink()) {
                if ($this->backupsCount >= self::MAX_BACKUP_FILE_TO_SCAN) {
                    break;
                }

                if ($this->isBackupPart($file->getFilename()) && $this->isListedMultipartBackup($file->getFilename())) {
                    continue;
                }

 
                if ($this->isWindowsOs() && !file_exists($file->getPathname())) {
                    continue;
                }

                $backups[] = clone $file;

                $this->backupsCount++;
            }
        }

        return $backups;
    }






    public function findBackupByMd5Hash(string $md5): \SplFileInfo
    {
        $backup = array_filter($this->findBackups(), function ($splFileInfo) use ($md5) {
            return md5($splFileInfo->getBasename()) === $md5;
        });

        if (empty($backup)) {
            throw new \UnexpectedValueException('WP STAGING: Could not find backup by hash ' . $md5);
        }

        return array_shift($backup);
    }
}
