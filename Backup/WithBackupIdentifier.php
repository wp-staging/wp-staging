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
    /**
     * @var array<string>
     */
    protected $listedMultipartBackups = [];

    /**
     * @param string $identifier
     * @param string $input
     * @return bool
     */
    public function checkPartByIdentifier(string $identifier, string $input)
    {
        return preg_match("#{$identifier}(.[0-9]+)?.wpstg$#", $input);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isBackupPart(string $name)
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

    public function isListedMultipartBackup(string $filename, bool $shouldAddBackup = true)
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
     * @param string $filename
     * @return string
     */
    public function extractBackupIdFromFilename(string $filename)
    {
        if (strpos($filename, '.' . DatabaseBackupTask::PART_IDENTIFIER . '.' . DatabaseBackupTask::FILE_FORMAT) !== false) {
            return $this->extractBackupIdFromDatabaseBackupFilename($filename);
        }

        $fileInfos = explode('_', $filename);
        $fileInfos = $fileInfos[count($fileInfos) - 1];
        return explode('.', $fileInfos)[0];
    }

    /**
     * @param string $filename
     * @return string
     */
    protected function extractBackupIdFromDatabaseBackupFilename(string $filename)
    {
        // This is required if the table prefix contains underscore like wp_some
        $filename = str_replace('.' . DatabaseBackupTask::PART_IDENTIFIER . '.' . DatabaseBackupTask::FILE_FORMAT, '', $filename);
        // Get position of last dot . in filename
        $lastDotPosition = strrpos($filename, '.');
        // Get filename until last dot to remove the table prefix
        $filename = substr($filename, 0, $lastDotPosition);

        $fileInfos = explode('_', $filename);
        return $fileInfos[count($fileInfos) - 1];
    }
}
