<?php

namespace WPStaging\Backup\Dto\Task\Restore\Response;

use WPStaging\Framework\Job\Dto\TaskResponseDto;




class RestoreFinishResponseDto extends TaskResponseDto
{



    private $isDatabaseRestoreSkipped = false;




    private $skippedViews = [];





    public function setIsDatabaseRestoreSkipped(bool $isDatabaseRestoreSkipped)
    {
        $this->isDatabaseRestoreSkipped = $isDatabaseRestoreSkipped;
    }




    public function getIsDatabaseRestoreSkipped(): bool
    {
        return $this->isDatabaseRestoreSkipped;
    }





    public function setSkippedViews(array $skippedViews)
    {
        $this->skippedViews = $skippedViews;
    }
}
