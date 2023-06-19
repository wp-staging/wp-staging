<?php

namespace WPStaging\Backup\Service\Database\Importer;

use WPStaging\Backup\Dto\Job\JobRestoreDataDto;

interface DatabaseSearchReplacerInterface
{
    public function getSearchAndReplace(JobRestoreDataDto $jobDataDto, $homeURL, $siteURL, $absPath = ABSPATH, $destinationSiteUploadURL = null);
}
