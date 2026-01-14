<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Traits\MemoryExhaustTrait;
use WPStaging\Staging\Jobs\StagingSiteReset;

class Reset extends AbstractTemplateComponent
{
    use MemoryExhaustTrait;

    /**
     * @var string
     */
    const WPSTG_REQUEST = 'wpstg_reset_staging';

    /**
     * @return void
     */
    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $tmpFileToDelete = $this->getMemoryExhaustErrorTmpFile(self::WPSTG_REQUEST);

        $jobReset = $this->getResetJob();
        $jobReset->setMemoryExhaustErrorTmpFile($tmpFileToDelete);

        wp_send_json($jobReset->prepareAndExecute());
    }

    /**
     * @return StagingSiteReset
     */
    protected function getResetJob()
    {
        return WPStaging::make(StagingSiteReset::class);
    }
}
