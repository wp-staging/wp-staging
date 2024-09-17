<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Backup\Task\FileBackupTask;

class BackupMuPluginsTask extends FileBackupTask
{
    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . PartIdentifier::MU_PLUGIN_PART_IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Adding Mu-Plugins to Backup';
    }

    protected function getFileIdentifier(): string
    {
        return PartIdentifier::MU_PLUGIN_PART_IDENTIFIER;
    }
}
