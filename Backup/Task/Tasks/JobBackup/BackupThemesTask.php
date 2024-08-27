<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Backup\Task\FileBackupTask;

class BackupThemesTask extends FileBackupTask
{
    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . PartIdentifier::THEME_PART_IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Adding Themes to Backup';
    }

    protected function getFileIdentifier(): string
    {
        return PartIdentifier::THEME_PART_IDENTIFIER;
    }
}
