<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use WPStaging\Backup\Task\FileBackupTask;

class BackupUploadsTask extends FileBackupTask
{
    const IDENTIFIER = 'uploads';

    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . self::IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Adding Medias to Backup';
    }

    protected function getFileIdentifier(): string
    {
        return self::IDENTIFIER;
    }
}
