<?php

// TODO PHP7.x; declare(strict_type=1);

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Exceptions\BackupRuntimeException;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Urls;

class Parts extends AbstractTemplateComponent
{
    /**
     * @var BackupsFinder
     */
    private $backupsFinder;

    /**
     * @var Urls
     */
    private $urls;

    public function __construct(TemplateEngine $templateEngine, BackupsFinder $backupsFinder, Urls $urls)
    {
        parent::__construct($templateEngine);
        $this->backupsFinder = $backupsFinder;
        $this->urls          = $urls;
    }

    /**
     * @throws BackupRuntimeException
     */
    public function render()
    {
        if (!$this->canRenderAjax()) {
            wp_send_json([
                'error'   => true,
                'message' => 'You are not allowed to access this page!',
            ]);
        }

        $backupDir = wp_normalize_path($this->backupsFinder->getBackupsDirectory());
        $indexFile = isset($_POST['filePath']) ? Sanitize::sanitizePath($_POST['filePath']) : '';

        if ($indexFile === '') {
            wp_send_json([
                'error'   => true,
                'message' => 'Backup file path not provided!',
            ]);
        }

        $file = $this->getFullPath($backupDir, $indexFile);
        $info = null;
        try {
            $info = (new BackupMetadata())->hydrateByFilePath($file);
        } catch (\Exception $e) {
            wp_send_json([
                'error'   => true,
                'message' => $e->getMessage(),
            ]);
        }

        $metadata = $info->getMultipartMetadata();

        $parts = array_merge(
            $this->addParts('Database', $metadata->getDatabaseParts(), $backupDir),
            $this->addParts('Medias', $metadata->getUploadsParts(), $backupDir),
            $this->addParts('Themes', $metadata->getThemesParts(), $backupDir),
            $this->addParts('Plugins', $metadata->getPluginsParts(), $backupDir),
            $this->addParts('Mu Plugins', $metadata->getMuPluginsParts(), $backupDir),
            $this->addParts('Others', $metadata->getOthersParts(), $backupDir),
            $this->addParts('Root Files', $metadata->getOtherWpRootParts(), $backupDir)
        );

        $result = $this->renderTemplate('backup/modal/backup-parts.php', [
            'backupParts' => $parts,
        ]);
        wp_send_json($result);
    }

    /**
     * @param string $backupDir
     * @param string $relativePath
     * @return string
     */
    private function getFullPath(string $backupDir, string $relativePath): string
    {
        return $backupDir . str_replace($backupDir, '', wp_normalize_path(untrailingslashit($relativePath)));
    }

    /**
     * @param string $type
     * @param int $key
     * @param string $fileName
     * @param string $fullPath
     * @param int $totalParts
     * @return array
     * @throws \WPStaging\Backup\Exceptions\BackupRuntimeException
     */
    private function getPart(string $type, int $key, string $fileName, string $fullPath, int $totalParts): array
    {
        $partName   = $type;
        $currentKey = $key + 1;
        $partType   = strtolower(str_replace(' ', '_', $type));
        $partIndex  = '';
        if ($totalParts > 1) {
            $partIndex .= " {$currentKey} / {$totalParts}";
        }

        return [
            'partType'     => $partType,
            'partIndex'    => $partIndex,
            'description'  => $this->getPartDescription($partType),
            'icon'         => $this->getIcon($partType),
            'name'         => $partName,
            'fileSize'     => size_format(filesize($fullPath), 2),
            'downloadLink' => $this->urls->getBackupUrl() . $fileName,
        ];
    }

    /**
     * @param string $type
     * @param array $files
     * @param string $backupDir
     * @return array
     * @throws \WPStaging\Backup\Exceptions\BackupRuntimeException
     */
    private function addParts(string $type, array $files, string $backupDir): array
    {
        $total = count($files);
        $parts = [];

        foreach ($files as $key => $fileName) {
            $fullPath = $this->getFullPath($backupDir, $fileName);
            $parts[]  = $this->getPart($type, $key, $fileName, $fullPath, $total);
        }

        return $parts;
    }

    /**
     * @param string $partType
     * @return string
     */
    private function getIcon(string $partType): string
    {
        $icons = [
            'database'   => 'database-new',
            'plugins'    => 'settings',
            'mu_plugins' => 'file',
            'themes'     => 'palette',
            'medias'     => 'images',
            'others'     => 'folder-new',
            'root_files' => 'hard-drive',
        ];
        if (isset($icons[$partType])) {
            return $icons[$partType];
        }

        return '';
    }

    private function getPartDescription(string $partType): string
    {
        $partsDesc = [
            'database'   => __('Complete WordPress database with all content and settings.', 'wp-staging'),
            'plugins'    => __('All installed WordPress plugins and their configurations.', 'wp-staging'),
            'mu_plugins' => __('Must-use plugins that are always active.', 'wp-staging'),
            'themes'     => __('WordPress themes, customizations, and design assets.', 'wp-staging'),
            'medias'     => __('Media files such as images or documents in the media library.', 'wp-staging'),
            'others'     => __('Files in wp-content excl. plugins, themes, uploads and mu-plugins.', 'wp-staging'),
            'root_files' => __('Root folders only: excludes wp-config.php and staging sites.', 'wp-staging'),
        ];

        if (isset($partsDesc[$partType])) {
            return $partsDesc[$partType];
        }

        return '';
    }
}
