<?php

namespace WPStaging\Staging\Tasks\StagingSite\Filesystem;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Staging\Tasks\FileCopierTask;

class CopyWpAdminTask extends FileCopierTask
{
    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . PartIdentifier::WP_ADMIN_PART_IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Copying "wp-admin" directory to Staging Site';
    }

    protected function getFileIdentifier(): string
    {
        return PartIdentifier::WP_ADMIN_PART_IDENTIFIER;
    }
}
