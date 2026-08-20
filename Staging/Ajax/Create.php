<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Traits\MemoryExhaustTrait;
use WPStaging\Staging\Jobs\StagingSiteCreate;
use WPStaging\Framework\Traits\JobResponseTrait;

class Create extends AbstractTemplateComponent
{
    use JobResponseTrait;
    use MemoryExhaustTrait;




    const WPSTG_REQUEST = 'wpstg_staging_create';




    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $tmpFileToDelete = $this->getMemoryExhaustErrorTmpFile(self::WPSTG_REQUEST);

        $jobCreate = $this->getCreateJob();
        $jobCreate->setMemoryExhaustErrorTmpFile($tmpFileToDelete);

        $this->sendJobResponse($jobCreate);
    }




    protected function getCreateJob()
    {
        return WPStaging::make(StagingSiteCreate::class);
    }
}
