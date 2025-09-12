<?php

namespace WPStaging\Staging\Traits;

use WPStaging\Staging\Tasks\StagingSite\DatabaseAdjustment\UpdateOptionsInOptionsTableTask;
use WPStaging\Staging\Tasks\StagingSite\DatabaseAdjustment\UpdatePrefixInOptionsTableTask;
use WPStaging\Staging\Tasks\StagingSite\DatabaseAdjustment\UpdatePrefixInUserMetaTableTask;
use WPStaging\Staging\Tasks\StagingSite\DatabaseAdjustment\UpdateSiteUrlAndHomeTask;
use WPStaging\Staging\Tasks\StagingSite\FileAdjustment\AdjustThirdPartyFilesTask;
use WPStaging\Staging\Tasks\StagingSite\FileAdjustment\UpdateWpConfigConstantsTask;
use WPStaging\Staging\Tasks\StagingSite\FileAdjustment\UpdateWpConfigTask;
use WPStaging\Staging\Tasks\StagingSite\FileAdjustment\VerifyIndexTask;
use WPStaging\Staging\Tasks\StagingSite\FileAdjustment\VerifyWpConfigTask;

trait WithDataAdjustmentTasks
{
    public function addDataAdjustmentTasks()
    {
        $this->tasks[] = VerifyWpConfigTask::class;
        if (!$this->jobDataDto->getAllTablesExcluded()) {
            $this->tasks[] = UpdateSiteUrlAndHomeTask::class;
            $this->tasks[] = UpdateOptionsInOptionsTableTask::class;
            $this->tasks[] = UpdatePrefixInUserMetaTableTask::class;
        }

        $this->tasks[] = UpdateWpConfigTask::class;
        $this->tasks[] = VerifyIndexTask::class;
        if (!$this->jobDataDto->getAllTablesExcluded()) {
            $this->tasks[] = UpdatePrefixInOptionsTableTask::class;
        }

        $this->tasks[] = UpdateWpConfigConstantsTask::class;
        $this->tasks[] = AdjustThirdPartyFilesTask::class;
    }
}
