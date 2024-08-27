<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Backup\Task\FileBackupTask;

class BackupPluginsTask extends FileBackupTask
{
    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . PartIdentifier::PLUGIN_PART_IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Adding Plugins to Backup';
    }

    protected function getFileIdentifier(): string
    {
        return PartIdentifier::PLUGIN_PART_IDENTIFIER;
    }
}
