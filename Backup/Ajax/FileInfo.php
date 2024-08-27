<?php

namespace WPStaging\Backup\Ajax;

use Exception;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\PartIdentifier;

class FileInfo extends AbstractTemplateComponent
{
    /** @var BackupsFinder */
    private $backupsFinder;

    /** @var string[] */
    private $excludedBackupParts;

    public function __construct(TemplateEngine $templateEngine, BackupsFinder $backupsFinder)
    {
        parent::__construct($templateEngine);
        $this->backupsFinder = $backupsFinder;
    }

    /**
     * @return void
     */
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
        } catch (Exception $e) {
            wp_send_json([
                'error'   => true,
                'message' => $e->getMessage(),
            ]);
        }

        $this->excludedBackupParts = Hooks::applyFilters(RestoreTask::FILTER_EXCLUDE_BACKUP_PARTS, []);

        $filters = [
            'database'  => $this->isBackupPartSkipped(PartIdentifier::DATABASE_PART_IDENTIFIER),
            'plugins'   => $this->isBackupPartSkipped(PartIdentifier::PLUGIN_PART_IDENTIFIER),
            'themes'    => $this->isBackupPartSkipped(PartIdentifier::THEME_PART_IDENTIFIER),
            'muPlugins' => $this->isBackupPartSkipped(PartIdentifier::MU_PLUGIN_PART_IDENTIFIER),
            'uploads'   => $this->isBackupPartSkipped(PartIdentifier::UPLOAD_PART_IDENTIFIER),
            'wpContent' => $this->isBackupPartSkipped(PartIdentifier::WP_CONTENT_PART_IDENTIFIER),
            'wpRoot'    => $this->isBackupPartSkipped(PartIdentifier::WP_ROOT_PART_IDENTIFIER)
        ];

        $viewData = [
            'info'    => $info,
            'filters' => $filters
        ];

        $result = $this->templateEngine->render(
            'backup/modal/confirm-restore.php',
            $viewData
        );

        wp_send_json($result);
    }

    protected function isBackupPartSkipped(string $partName): bool
    {
        if (empty($this->excludedBackupParts)) {
            return false;
        }

        return in_array($partName, $this->excludedBackupParts);
    }
}
