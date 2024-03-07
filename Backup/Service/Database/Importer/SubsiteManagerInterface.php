<?php

namespace WPStaging\Backup\Service\Database\Importer;

use WPStaging\Backup\Dto\Job\JobRestoreDataDto;

interface SubsiteManagerInterface
{
    /**
     * @param JobRestoreDataDto $jobRestoreDataDto
     * @return void
     */
    public function initialize(JobRestoreDataDto $jobRestoreDataDto);

    /** @return void */
    public function updateSubsiteId();

    /** @return bool */
    public function isTableFromDifferentSubsite(string $query): bool;
}
