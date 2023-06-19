<?php

namespace WPStaging\Backup\Service\Database\Importer;

use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Framework\Database\SearchReplace;

class BasicDatabaseSearchReplacer implements DatabaseSearchReplacerInterface
{
    /**
     * @return SearchReplace
     */
    public function getSearchAndReplace(JobRestoreDataDto $jobDataDto, $destinationSiteUrl, $destinationHomeUrl, $absPath = ABSPATH, $destinationSiteUploadURL = null)
    {
        return (new SearchReplace())
            ->setSearch([])
            ->setReplace([])
            ->setWpBakeryActive($jobDataDto->getBackupMetadata()->getWpBakeryActive());
    }
}
