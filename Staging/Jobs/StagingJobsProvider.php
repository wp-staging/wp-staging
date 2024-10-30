<?php

namespace WPStaging\Staging\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Job\AbstractJob;

class StagingJobsProvider
{
    /** @var string */
    const JOB_STAGING_DELETE = 'staging_site_delete';

    public function getJob(string $jobName): AbstractJob
    {
        if ($jobName === self::JOB_STAGING_DELETE) {
            return WPStaging::make(StagingSiteDelete::class);
        }

        throw new \Exception('Not a valid job name!');
    }
}
