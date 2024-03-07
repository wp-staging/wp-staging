<?php

namespace WPStaging\Backup\Service\Database\Importer;

use WPStaging\Backup\Dto\Job\JobRestoreDataDto;

class BasicSubsiteManager implements SubsiteManagerInterface
{
    /**
     * @param JobRestoreDataDto $jobRestoreDataDto
     * @return void
     */
    public function initialize(JobRestoreDataDto $jobRestoreDataDto)
    {
        // no-op
    }

    /** @return void */
    public function updateSubsiteId()
    {
        // no-op
    }

    /** @return bool */
    public function isTableFromDifferentSubsite(string $query): bool
    {
        return false;
    }
}
