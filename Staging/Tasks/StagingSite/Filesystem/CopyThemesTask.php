<?php

namespace WPStaging\Staging\Tasks\StagingSite\Filesystem;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Staging\Tasks\FileCopierTask;

class CopyThemesTask extends FileCopierTask
{
    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . PartIdentifier::THEME_PART_IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Copying Themes to Staging Site';
    }

    protected function getFileIdentifier(): string
    {
        return PartIdentifier::THEME_PART_IDENTIFIER;
    }

    /** @return bool */
    protected function getIsWpContent(): bool
    {
        return true;
    }
}
