<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Backup\Task\FileBackupTask;

class BackupUploadsTask extends FileBackupTask
{
    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . PartIdentifier::UPLOAD_PART_IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Adding Medias to Backup';
    }

    protected function getFileIdentifier(): string
    {
        return PartIdentifier::UPLOAD_PART_IDENTIFIER;
    }
}
