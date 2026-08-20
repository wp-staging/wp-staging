<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Traits\MemoryExhaustTrait;
use WPStaging\Staging\Jobs\StagingSiteUpdate;
use WPStaging\Framework\Traits\JobResponseTrait;

class Update extends AbstractTemplateComponent
{
    use JobResponseTrait;
    use MemoryExhaustTrait;




    const WPSTG_REQUEST = 'wpstg_staging_update';




    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $tmpFileToDelete = $this->getMemoryExhaustErrorTmpFile(self::WPSTG_REQUEST);

        $jobUpdate = $this->getUpdateJob();
        $jobUpdate->setMemoryExhaustErrorTmpFile($tmpFileToDelete);

        $this->sendJobResponse($jobUpdate);
    }




    protected function getUpdateJob()
    {
        return WPStaging::make(StagingSiteUpdate::class);
    }
}
