<?php

namespace WPStaging\Staging\Tasks\StagingSite\Filesystem;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Staging\Tasks\FileCopierTask;

class CopyWpRootFilesTask extends FileCopierTask
{
    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . PartIdentifier::WP_ROOT_FILES_PART_IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Copying only wp root (ABSPATH) files to Staging Site';
    }

    protected function getFileIdentifier(): string
    {
        return PartIdentifier::WP_ROOT_FILES_PART_IDENTIFIER;
    }
}
