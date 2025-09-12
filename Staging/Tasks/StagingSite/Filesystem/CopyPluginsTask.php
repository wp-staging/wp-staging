<?php

namespace WPStaging\Staging\Tasks\StagingSite\Filesystem;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Staging\Tasks\FileCopierTask;

class CopyPluginsTask extends FileCopierTask
{
    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . PartIdentifier::PLUGIN_PART_IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Copying Plugins to Staging Site';
    }

    protected function getFileIdentifier(): string
    {
        return PartIdentifier::PLUGIN_PART_IDENTIFIER;
    }

    /** @return bool */
    protected function getIsWpContent(): bool
    {
        return true;
    }

    protected function getIsExcluded(): bool
    {
        return $this->jobDataDto->getIsPluginsExcluded();
    }
}
