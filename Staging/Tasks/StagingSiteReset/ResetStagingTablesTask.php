<?php

namespace WPStaging\Staging\Tasks\StagingSiteReset;

use RuntimeException;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Staging\Dto\Job\StagingSiteJobsDataDto;
use WPStaging\Staging\Tasks\StagingSite\CleanupStagingTablesTask;
use WPStaging\Framework\Traits\WithUnfinishedCloneWpConfigGuard;




class ResetStagingTablesTask extends CleanupStagingTablesTask
{
    use WithUnfinishedCloneWpConfigGuard;

 
    protected $jobDataDto;





    public function execute()
    {
        $wpConfigPath = trailingslashit($this->jobDataDto->getStagingSitePath()) . 'wp-config.php';
        if (!$this->guardWpConfigBeforeReset(WPStaging::make(Filesystem::class), $wpConfigPath)) {
            throw new RuntimeException('Cannot reset staging tables because wp-config.php cannot be guarded: ' . $wpConfigPath);
        }

        return parent::execute();
    }
}
