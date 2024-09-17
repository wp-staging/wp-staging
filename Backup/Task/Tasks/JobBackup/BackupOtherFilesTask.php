<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Backup\Task\FileBackupTask;

class BackupOtherFilesTask extends FileBackupTask
{
    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . PartIdentifier::OTHER_WP_CONTENT_PART_IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Adding Other Files to Backup';
    }

    protected function getFileIdentifier(): string
    {
        return PartIdentifier::OTHER_WP_CONTENT_PART_IDENTIFIER;
    }
}
