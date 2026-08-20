<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Traits\MemoryExhaustTrait;
use WPStaging\Staging\Jobs\StagingSiteReset;
use WPStaging\Framework\Traits\JobResponseTrait;

class Reset extends AbstractTemplateComponent
{
    use JobResponseTrait;
    use MemoryExhaustTrait;




    const WPSTG_REQUEST = 'wpstg_staging_reset';




    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $tmpFileToDelete = $this->getMemoryExhaustErrorTmpFile(self::WPSTG_REQUEST);

        $jobReset = $this->getResetJob();
        $jobReset->setMemoryExhaustErrorTmpFile($tmpFileToDelete);

        $this->sendJobResponse($jobReset);
    }




    protected function getResetJob()
    {
        return WPStaging::make(StagingSiteReset::class);
    }
}
