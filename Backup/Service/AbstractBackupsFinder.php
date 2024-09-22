<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\WithBackupIdentifier;
use WPStaging\Framework\Traits\DebugLogTrait;

/**
 * Class AbstractBackupsFinder
 * This class should not use any wp core functions or classes.
 *
 * Finds the .wpstg backups in the filesystem.
 *
 * @package WPStaging\Backup
 */
abstract class AbstractBackupsFinder
{
    use WithBackupIdentifier;
    use DebugLogTrait;

    /** @var int */
    const MAX_BACKUP_FILE_TO_SCAN = 1000;

    /** @var string */
    protected $backupsDirectory;

    /** @var int */
    protected $backupsCount;

    public function resetBackupsCount()
    {
        $this->backupsCount = 0;
    }

    /**
     * @param string $backupsDirectory
     * @return void
     */
    public function setBackupsDirectory(string $backupsDirectory)
    {
        $this->backupsDirectory = $backupsDirectory;
    }

    /**
     * @param bool $refresh
     * @return string
     */
    public function getBackupsDirectory(bool $refresh = false): string
    {
        return $this->backupsDirectory;
    }

    /**
     * @return array An array of SplFileInfo objects of .wpstg backup files.
     */
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

        /** @var \SplFileInfo $file */
        foreach ($it as $file) {
            if (($file->getExtension() === 'wpstg' || $file->getExtension() === 'sql') && !$file->isLink()) {
                if ($this->backupsCount >= self::MAX_BACKUP_FILE_TO_SCAN) {
                    break;
                }

                if ($this->isBackupPart($file->getFilename()) && $this->isListedMultipartBackup($file->getFilename())) {
                    continue;
                }

                $backups[] = clone $file;

                $this->backupsCount++;
            }
        }

        return $backups;
    }

    /**
     * @param string $md5
     *
     * @return \SplFileInfo
     */
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
