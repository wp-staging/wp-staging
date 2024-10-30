<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Traits\MemoryExhaustTrait;
use WPStaging\Staging\Jobs\StagingSiteDelete;

class Delete extends AbstractTemplateComponent
{
    use MemoryExhaustTrait;

    /**
     * @var string
     */
    const WPSTG_REQUEST = 'wpstg_delete';

    /**
     * @return void
     */
    public function ajaxDelete()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $tmpFileToDelete = $this->getMemoryExhaustErrorTmpFile(self::WPSTG_REQUEST);

        $jobBackup = WPStaging::make(StagingSiteDelete::class);
        $jobBackup->setMemoryExhaustErrorTmpFile($tmpFileToDelete);

        wp_send_json($jobBackup->prepareAndExecute());
    }
}
