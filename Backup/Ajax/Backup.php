<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\Job\JobBackupProvider;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Traits\MemoryExhaustTrait;
use WPStaging\Framework\Traits\JobResponseTrait;

class Backup extends AbstractTemplateComponent
{
    use JobResponseTrait;
    use MemoryExhaustTrait;

    /**
     * @var string
     */
    const WPSTG_REQUEST = 'wpstg_backup';

    /**
     * @return void
     */
    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $tmpFileToDelete = $this->getMemoryExhaustErrorTmpFile(self::WPSTG_REQUEST);

        $jobBackup = WPStaging::make(JobBackupProvider::class)->getJob();
        $jobBackup->setMemoryExhaustErrorTmpFile($tmpFileToDelete);

        $this->sendJobResponse($jobBackup);
    }
}
