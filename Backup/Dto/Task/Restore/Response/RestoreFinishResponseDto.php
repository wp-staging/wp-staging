<?php

namespace WPStaging\Backup\Dto\Task\Restore\Response;

use WPStaging\Framework\Job\Dto\TaskResponseDto;

class RestoreFinishResponseDto extends TaskResponseDto
{



    private $isDatabaseRestoreSkipped;





    public function setIsDatabaseRestoreSkipped(bool $isDatabaseRestoreSkipped)
    {
        $this->isDatabaseRestoreSkipped = $isDatabaseRestoreSkipped;
    }




    public function getIsDatabaseRestoreSkipped(): bool
    {
        return $this->isDatabaseRestoreSkipped;
    }
}
