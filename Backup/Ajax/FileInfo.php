<?php

namespace WPStaging\Backup\Ajax;

use Exception;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Backup\Task\Tasks\JobRestore\CleanExistingMediaTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreMuPluginsTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreOtherFilesInWpContentTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestorePluginsTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreThemesTask;
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

        $excluded = [
            'database'  => $this->isBackupPartExcluded(PartIdentifier::DATABASE_PART_IDENTIFIER),
            'plugins'   => $this->isBackupPartExcluded(PartIdentifier::PLUGIN_PART_IDENTIFIER),
            'themes'    => $this->isBackupPartExcluded(PartIdentifier::THEME_PART_IDENTIFIER),
            'muPlugins' => $this->isBackupPartExcluded(PartIdentifier::MU_PLUGIN_PART_IDENTIFIER),
            'uploads'   => $this->isBackupPartExcluded(PartIdentifier::UPLOAD_PART_IDENTIFIER),
            'wpContent' => $this->isBackupPartExcluded(PartIdentifier::WP_CONTENT_PART_IDENTIFIER),
            'wpRoot'    => $this->isBackupPartExcluded(PartIdentifier::WP_ROOT_PART_IDENTIFIER)
        ];

        $replaced = [
            'plugins'   => $this->isBackupPartReplaced(RestorePluginsTask::FILTER_KEEP_EXISTING_PLUGINS, $info),
            'themes'    => $this->isBackupPartReplaced(RestoreThemesTask::FILTER_KEEP_EXISTING_THEMES, $info),
            'muPlugins' => $this->isBackupPartReplaced(RestoreMuPluginsTask::FILTER_KEEP_EXISTING_MUPLUGINS, $info),
            'wpContent' => $this->isBackupPartReplaced(RestoreOtherFilesInWpContentTask::FILTER_KEEP_EXISTING_OTHER_FILES, $info),
            // Each subsite has separate uploads folder, so only filter check is needed!
            'uploads'   => !Hooks::applyFilters(CleanExistingMediaTask::FILTER_KEEP_EXISTING_MEDIA, false),
        ];

        $viewData = [
            'info'     => $info,
            'excluded' => $excluded,
            'replaced' => $replaced
        ];

        $result = $this->templateEngine->render(
            'backup/modal/confirm-restore.php',
            $viewData
        );

        wp_send_json($result);
    }

    protected function isBackupPartExcluded(string $partName): bool
    {
        if (empty($this->excludedBackupParts)) {
            return false;
        }

        return in_array($partName, $this->excludedBackupParts);
    }

    protected function isBackupPartReplaced(string $filter, BackupMetadata $metadata): bool
    {
        // Should only be allowed to replace when restoring on single site or when restoring a full network backup
        if (!is_multisite() || $metadata->getBackupType() === BackupMetadata::BACKUP_TYPE_MULTISITE) {
            // If filter returns true, it means the existing files should be kept not replaced
            return !Hooks::applyFilters($filter, false);
        }

        return false;
    }
}
