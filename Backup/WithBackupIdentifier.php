<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Task\Tasks\JobBackup\DatabaseBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupMuPluginsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupOtherFilesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupPluginsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupThemesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupUploadsTask;

trait WithBackupIdentifier
{
    protected $listedMultipartBackups = [];

    /**
     * @param string $identifier
     * @param string $input
     * @return bool
     */
    public function checkPartByIdentifier($identifier, $input)
    {
        return preg_match("#{$identifier}(.[0-9]+)?.wpstg$#", $input);
    }

    public function isBackupPart($name)
    {
        $dbExtension = DatabaseBackupTask::FILE_FORMAT;
        $dbIdentifier = DatabaseBackupTask::PART_IDENTIFIER;
        if (preg_match("#{$dbIdentifier}(.[0-9]+)?.{$dbExtension}$#", $name)) {
            return true;
        }

        $pluginIdentifier = BackupPluginsTask::IDENTIFIER;
        $mupluginIdentifier = BackupMuPluginsTask::IDENTIFIER;
        $themeIdentifier = BackupThemesTask::IDENTIFIER;
        $uploadIdentifier = BackupUploadsTask::IDENTIFIER;
        $otherIdentifier = BackupOtherFilesTask::IDENTIFIER;
        if ($this->checkPartByIdentifier("({$pluginIdentifier}|{$mupluginIdentifier}|{$themeIdentifier}|{$uploadIdentifier}|{$otherIdentifier})", $name)) {
            return true;
        }

        return false;
    }

    /**
     * @return void
     */
    public function clearListedMultipartBackups()
    {
        $this->listedMultipartBackups = [];
    }

    public function isListedMultipartBackup($filename, $shouldAddBackup = true)
    {
        $id = $this->extractBackupIdFromFilename($filename);
        if (in_array($id, $this->listedMultipartBackups)) {
            return true;
        }

        if ($shouldAddBackup) {
            $this->listedMultipartBackups[] = $id;
        }

        return false;
    }

    /**
     * @var string $filename
     * @return string
     */
    public function extractBackupIdFromFilename($filename)
    {
        $fileInfos = explode('_', $filename);
        $fileInfos = $fileInfos[count($fileInfos) - 1];
        return explode('.', $fileInfos)[0];
    }
}
