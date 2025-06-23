<?php

namespace WPStaging\Staging\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Framework\Job\JobTransientCache;

class StagingJobsProvider
{
    public function getJob(string $jobType): AbstractJob
    {
        if ($jobType === JobTransientCache::JOB_TYPE_STAGING_DELETE) {
            return WPStaging::make(StagingSiteDelete::class);
        }

        if ($jobType === JobTransientCache::JOB_TYPE_STAGING_CREATE) {
            return WPStaging::make(StagingSiteCreate::class);
        }

        throw new \Exception('Not a valid job name!');
    }
}
