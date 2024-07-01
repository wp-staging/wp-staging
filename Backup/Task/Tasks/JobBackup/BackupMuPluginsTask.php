<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use WPStaging\Backup\Task\FileBackupTask;

class BackupMuPluginsTask extends FileBackupTask
{
    const IDENTIFIER = 'muplugins';

    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . self::IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Adding Mu-Plugins to Backup';
    }

    protected function getFileIdentifier(): string
    {
        return self::IDENTIFIER;
    }
}
