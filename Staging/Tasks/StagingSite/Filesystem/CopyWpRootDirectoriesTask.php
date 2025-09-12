<?php

namespace WPStaging\Staging\Tasks\StagingSite\Filesystem;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Staging\Tasks\FileCopierTask;

class CopyWpRootDirectoriesTask extends FileCopierTask
{
    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . PartIdentifier::WP_ROOT_PART_IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Copying other directories from wp root (ABSPATH) to Staging Site';
    }

    protected function getFileIdentifier(): string
    {
        return PartIdentifier::WP_ROOT_PART_IDENTIFIER;
    }

    protected function getIsExcluded(): bool
    {
        return $this->jobDataDto->getIsRootDirectoriesExcluded();
    }
}
