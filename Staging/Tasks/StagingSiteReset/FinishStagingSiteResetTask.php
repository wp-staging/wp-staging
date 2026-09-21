<?php

namespace WPStaging\Staging\Tasks\StagingSiteReset;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingReset;
use WPStaging\Staging\Tasks\StagingSiteUpdate\FinishStagingSiteUpdateTask;

class FinishStagingSiteResetTask extends FinishStagingSiteUpdateTask
{



    public static function getTaskName()
    {
        return 'staging_site_reset_finish';
    }




    public static function getTaskTitle()
    {
        return 'Finishing Staging Site Reset';
    }




    protected function enqueueFinishEvent()
    {
        WPStaging::make(AnalyticsStagingReset::class)->enqueueFinishEvent($this->jobDataDto->getId(), $this->jobDataDto);
    }





    protected function logFinishHeader(string $stagingSiteName)
    {
        $this->logger->info(sprintf(
            'Staging Site "%s" reset.',
            $stagingSiteName
        ));
    }
}
