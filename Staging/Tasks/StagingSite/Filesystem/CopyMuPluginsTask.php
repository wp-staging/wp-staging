<?php

namespace WPStaging\Staging\Tasks\StagingSite\Filesystem;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Staging\Tasks\FileCopierTask;

class CopyMuPluginsTask extends FileCopierTask
{
    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . PartIdentifier::MU_PLUGIN_PART_IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Copying Mu-Plugins to Staging Site';
    }

    protected function getFileIdentifier(): string
    {
        return PartIdentifier::MU_PLUGIN_PART_IDENTIFIER;
    }

    /** @return bool */
    protected function getIsWpContent(): bool
    {
        return true;
    }
}
