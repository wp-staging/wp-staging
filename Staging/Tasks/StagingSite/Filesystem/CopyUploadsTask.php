<?php

namespace WPStaging\Staging\Tasks\StagingSite\Filesystem;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Staging\Tasks\FileCopierTask;

class CopyUploadsTask extends FileCopierTask
{
    public static function getTaskName(): string
    {
        return parent::getTaskName() . '_' . PartIdentifier::UPLOAD_PART_IDENTIFIER;
    }

    public static function getTaskTitle(): string
    {
        return 'Copying Media to Staging Site';
    }

    protected function getFileIdentifier(): string
    {
        return PartIdentifier::UPLOAD_PART_IDENTIFIER;
    }

    /** @return bool */
    protected function getIsWpContent(): bool
    {
        return true;
    }

    protected function getIsExcluded(): bool
    {
        return $this->jobDataDto->getIsUploadsExcluded();
    }
}
