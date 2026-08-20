<?php

namespace WPStaging\Staging\Traits;

use WPStaging\Staging\Service\StagingEngine;




trait WithStagingEnginePreference
{
 
    protected $stagingEngine;




    protected function persistStagingEnginePreference()
    {
        if (!is_object($this->jobDataDto) || !method_exists($this->jobDataDto, 'getStagingEngine')) {
            return;
        }

        $this->stagingEngine->saveEngine($this->jobDataDto->getStagingEngine());
    }
}
