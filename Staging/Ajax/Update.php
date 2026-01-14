<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Traits\MemoryExhaustTrait;
use WPStaging\Staging\Jobs\StagingSiteUpdate;

class Update extends AbstractTemplateComponent
{
    use MemoryExhaustTrait;

    /**
     * @var string
     */
    const WPSTG_REQUEST = 'wpstg_update_staging';

    /**
     * @return void
     */
    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $tmpFileToDelete = $this->getMemoryExhaustErrorTmpFile(self::WPSTG_REQUEST);

        $jobUpdate = $this->getUpdateJob();
        $jobUpdate->setMemoryExhaustErrorTmpFile($tmpFileToDelete);

        wp_send_json($jobUpdate->prepareAndExecute());
    }

    /**
     * @return StagingSiteUpdate
     */
    protected function getUpdateJob()
    {
        return WPStaging::make(StagingSiteUpdate::class);
    }
}
