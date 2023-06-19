<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\Job\JobRestoreProvider;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\ErrorHandler;
use WPStaging\Framework\Component\AbstractTemplateComponent;

class Restore extends AbstractTemplateComponent
{
    const WPSTG_REQUEST = 'wpstg_restore';

    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $tmpFileToDelete = $this->setupTmpErrorFile();

        $jobRestore = WPStaging::make(JobRestoreProvider::class)->getJob();
        $jobRestore->setMemoryExhaustErrorTmpFile($tmpFileToDelete);

        wp_send_json($jobRestore->prepareAndExecute());
    }

    /**
     * @return string|false
     */
    protected function setupTmpErrorFile()
    {
        if (!defined('WPSTG_UPLOADS_DIR')) {
            return false;
        }

        if (!defined('WPSTG_REQUEST')) {
            define('WPSTG_REQUEST', self::WPSTG_REQUEST);
        }

        return WPSTG_UPLOADS_DIR . self::WPSTG_REQUEST . ErrorHandler::ERROR_FILE_EXTENSION;
    }
}
