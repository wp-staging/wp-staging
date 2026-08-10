<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\Job\JobRestoreProvider;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Traits\MemoryExhaustTrait;
use WPStaging\Framework\Traits\JobResponseTrait;

class Restore extends AbstractTemplateComponent
{
    use JobResponseTrait;
    use MemoryExhaustTrait;

    /**
     * @var string
     */
    const WPSTG_REQUEST = 'wpstg_restore';

    /**
     * @return void
     */
    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $tmpFileToDelete = $this->getMemoryExhaustErrorTmpFile(self::WPSTG_REQUEST);

        $jobRestore = WPStaging::make(JobRestoreProvider::class)->getJob();
        $jobRestore->setMemoryExhaustErrorTmpFile($tmpFileToDelete);

        $this->sendJobResponse($jobRestore);
    }
}
