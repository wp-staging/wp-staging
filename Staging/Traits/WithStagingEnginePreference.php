<?php

namespace WPStaging\Staging\Traits;

use WPStaging\Staging\Service\StagingEngine;

/**
 * Persists the staging engine selected for the current job.
 */
trait WithStagingEnginePreference
{
    /** @var StagingEngine */
    protected $stagingEngine;

    /**
     * @return void
     */
    protected function persistStagingEnginePreference()
    {
        if (!is_object($this->jobDataDto) || !method_exists($this->jobDataDto, 'getStagingEngine')) {
            return;
        }

        $this->stagingEngine->saveEngine($this->jobDataDto->getStagingEngine());
    }
}
