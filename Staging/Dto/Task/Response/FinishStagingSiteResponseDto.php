<?php

/**
 * @noinspection PhpPropertyOnlyWrittenInspection
 * @see          \WPStaging\Framework\Traits\ArrayableTrait::toArray
 */

namespace WPStaging\Staging\Dto\Task\Response;

use WPStaging\Framework\Job\Dto\TaskResponseDto;

class FinishStagingSiteResponseDto extends TaskResponseDto
{
    /**
     * @var string
     */
    private $cloneId = '';

    /** @var string */
    private $stagingSiteUrl = '';

    /**
     * @param string $cloneId
     * @return void
     */
    public function setCloneId(string $cloneId)
    {
        $this->cloneId = $cloneId;
    }

    public function getCloneId(): string
    {
        return $this->cloneId;
    }

    public function setStagingSiteUrl(string $stagingSiteUrl)
    {
        $this->stagingSiteUrl = $stagingSiteUrl;
    }

    public function getStagingSiteUrl(): string
    {
        return $this->stagingSiteUrl;
    }
}
