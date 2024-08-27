<?php

namespace WPStaging\Backup\Dto\Task\Restore\Response;

use WPStaging\Framework\Job\Dto\TaskResponseDto;

class RestoreFinishResponseDto extends TaskResponseDto
{
    /**
     * @var bool
     */
    private $isDatabaseRestoreSkipped;

    /**
     * @param bool $isDatabaseRestoreSkipped
     * @return void
     */
    public function setIsDatabaseRestoreSkipped(bool $isDatabaseRestoreSkipped)
    {
        $this->isDatabaseRestoreSkipped = $isDatabaseRestoreSkipped;
    }

    /**
     * @return bool
     */
    public function getIsDatabaseRestoreSkipped(): bool
    {
        return $this->isDatabaseRestoreSkipped;
    }
}
