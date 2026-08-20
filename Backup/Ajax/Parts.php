<?php

 

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Exceptions\BackupRuntimeException;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Utils\BackupPathResolver;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Urls;

class Parts extends AbstractTemplateComponent
{



    private $backupsFinder;




    private $backupPathResolver;




    private $urls;

    public function __construct(TemplateEngine $templateEngine, BackupsFinder $backupsFinder, BackupPathResolver $backupPathResolver, Urls $urls)
    {
        parent::__construct($templateEngine);
        $this->backupsFinder      = $backupsFinder;
        $this->backupPathResolver = $backupPathResolver;
        $this->urls               = $urls;
    }




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

        $file = $this->backupPathResolver->resolveBackupPath($indexFile);
        if ($file === '') {
            wp_send_json([
                'error'   => true,
                'message' => 'Invalid backup file path!',
            ]);
        }

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






    private function getFullPath(string $backupDir, string $relativePath): string
    {
        return trailingslashit($backupDir) . basename(wp_normalize_path($relativePath));
    }










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





    private function getIcon(string $partType): string
    {
        $icons = [
            'database'   => 'database',
            'plugins'    => 'admin-plugins',
            'mu_plugins' => 'plugins-checked',
            'themes'     => 'layout',
            'medias'     => 'images-alt',
            'others'     => 'admin-generic',
            'root_files' => 'root-folder',
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
