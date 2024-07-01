<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use WPStaging\Backup\Task\FileBackupTask;

class BackupPluginsTask extends FileBackupTask
{
    const IDENTIFIER = 'plugins';

    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . self::IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Adding Plugins to Backup';
    }

    protected function getFileIdentifier(): string
    {
        return self::IDENTIFIER;
    }
}
