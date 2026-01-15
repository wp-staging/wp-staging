<?php

namespace WPStaging\Staging\Tasks\StagingSiteReset;

use WPStaging\Staging\Tasks\StagingSiteUpdate\FinishStagingSiteUpdateTask;

class FinishStagingSiteResetTask extends FinishStagingSiteUpdateTask
{
    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'staging_site_reset_finish';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Finishing Staging Site Reset';
    }

    /**
     * @param string $stagingSiteName
     * @return void
     */
    protected function logFinishHeader(string $stagingSiteName)
    {
        $this->logger->info(sprintf(
            'Staging Site "%s" reset.',
            $stagingSiteName
        ));
    }
}
