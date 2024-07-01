<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use WPStaging\Backup\Task\FileBackupTask;

class BackupThemesTask extends FileBackupTask
{
    const IDENTIFIER = 'themes';

    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . self::IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Adding Themes to Backup';
    }

    protected function getFileIdentifier(): string
    {
        return self::IDENTIFIER;
    }
}
