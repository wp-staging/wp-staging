<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\Job\JobBackupProvider;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\ErrorHandler;
use WPStaging\Framework\Component\AbstractTemplateComponent;

class Backup extends AbstractTemplateComponent
{
    const WPSTG_REQUEST = 'wpstg_backup';

    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $tmpFileToDelete = $this->setupTmpErrorFile();

        $jobBackup = WPStaging::make(JobBackupProvider::class)->getJob();
        $jobBackup->setMemoryExhaustErrorTmpFile($tmpFileToDelete);

        wp_send_json($jobBackup->prepareAndExecute());
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
