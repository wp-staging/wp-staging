<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Backup\Task\FileBackupTask;

class BackupOtherWpRootFilesTask extends FileBackupTask
{
    protected function getFileIdentifier(): string
    {
        return PartIdentifier::OTHER_WP_ROOT_PART_IDENTIFIER;
    }




    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . PartIdentifier::OTHER_WP_ROOT_PART_IDENTIFIER;
    }




    public static function getTaskTitle(): string
    {
        return 'Adding Other Files In WP Root to Backup';
    }




    protected function isOtherWpRootFilesTask(): bool
    {
        return true;
    }
}
