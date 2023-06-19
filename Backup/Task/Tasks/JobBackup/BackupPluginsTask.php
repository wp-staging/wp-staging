<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use WPStaging\Backup\Task\FileBackupTask;

class BackupPluginsTask extends FileBackupTask
{
    const IDENTIFIER = 'plugins';

    public static function getTaskName()
    {
        return parent::getTaskName() . '_' . self::IDENTIFIER;
    }

    public static function getTaskTitle()
    {
        return 'Adding Plugins to Backup';
    }

    protected function getFileIdentifier()
    {
        return self::IDENTIFIER;
    }
}
