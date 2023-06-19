<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Backup\Ajax;

use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;

class FileInfo extends AbstractTemplateComponent
{
    /** @var BackupsFinder */
    private $backupsFinder;

    public function __construct(TemplateEngine $templateEngine, BackupsFinder $backupsFinder)
    {
        parent::__construct($templateEngine);
        $this->backupsFinder = $backupsFinder;
    }

    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $filePath = isset($_POST['filePath']) ? sanitize_text_field($_POST['filePath']) : '';
        // Make sure path is inside Backups Directory
        $backupDir = wp_normalize_path($this->backupsFinder->getBackupsDirectory());
        $file = $backupDir . str_replace($backupDir, '', wp_normalize_path(untrailingslashit($filePath)));

        try {
            $info = (new BackupMetadata())->hydrateByFilePath($file);
        } catch (\Exception $e) {
            wp_send_json([
                'error'   => true,
                'message' => $e->getMessage(),
            ]);
        }

        $viewData = [
            'info' => $info
        ];

        $result = $this->templateEngine->render(
            'Backend/views/backup/modal/confirm-restore.php',
            $viewData
        );

        wp_send_json($result);
    }
}
