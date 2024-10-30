<?php

namespace WPStaging\Staging\Tasks;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Job\Task\AbstractTask;
use WPStaging\Staging\Sites;
use WPStaging\Staging\Dto\StagingSiteDto;

abstract class StagingTask extends AbstractTask
{
    protected function getStagingSiteDto(string $cloneId): StagingSiteDto
    {
        /** @var Sites */
        $sites = WPStaging::make(Sites::class);
        return $sites->getStagingSiteDtoByCloneId($cloneId);
    }
}
