<?php

namespace WPStaging\Staging\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Traits\MemoryExhaustTrait;
use WPStaging\Staging\Jobs\StagingSiteCreate;

class Create extends AbstractTemplateComponent
{
    use MemoryExhaustTrait;

    /**
     * @var string
     */
    const WPSTG_REQUEST = 'wpstg_create_staging';

    /**
     * @return void
     */
    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $tmpFileToDelete = $this->getMemoryExhaustErrorTmpFile(self::WPSTG_REQUEST);

        $jobCreate = WPStaging::make(StagingSiteCreate::class); // @phpstan-ignore-line
        $jobCreate->setMemoryExhaustErrorTmpFile($tmpFileToDelete); // @phpstan-ignore-line

        wp_send_json($jobCreate->prepareAndExecute());
    }
}
