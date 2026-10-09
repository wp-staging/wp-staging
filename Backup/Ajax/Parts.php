<?php

 

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Exceptions\BackupRuntimeException;
use WPStaging\Backup\Security\BackupDirectoryProtectionService;
use WPStaging\Backup\Transfer\TransferSessionService;
use WPStaging\Backup\Utils\BackupPathResolver;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Urls;

use function WPStaging\functions\debug_log;

class Parts extends AbstractTemplateComponent
{



    private $backupPathResolver;




    private $transferSessionService;




    private $urls;




    private $protectionService;

    public function __construct(TemplateEngine $templateEngine, BackupPathResolver $backupPathResolver, TransferSessionService $transferSessionService, Urls $urls, BackupDirectoryProtectionService $protectionService)
    {
        parent::__construct($templateEngine);
        $this->backupPathResolver     = $backupPathResolver;
        $this->transferSessionService = $transferSessionService;
        $this->urls                   = $urls;
        $this->protectionService      = $protectionService;
    }




    public function render()
    {
        if (!$this->canRenderAjax()) {
            wp_send_json([
                'error'   => true,
                'message' => 'You are not allowed to access this page!',
            ]);
        }

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

        $metadata       = $info->getMultipartMetadata();
        $backupFilename = wp_basename($file);

        $parts = array_merge(
            $this->addParts('Database', $metadata->getDatabaseParts(), $backupFilename),
            $this->addParts('Medias', $metadata->getUploadsParts(), $backupFilename),
            $this->addParts('Themes', $metadata->getThemesParts(), $backupFilename),
            $this->addParts('Plugins', $metadata->getPluginsParts(), $backupFilename),
            $this->addParts('Mu Plugins', $metadata->getMuPluginsParts(), $backupFilename),
            $this->addParts('Others', $metadata->getOthersParts(), $backupFilename),
            $this->addParts('Root Files', $metadata->getOtherWpRootParts(), $backupFilename)
        );

        $result = $this->renderTemplate('backup/modal/backup-parts.php', [
            'backupParts'                 => $parts,
            'isTransferSessionEnabled'    => $this->transferSessionService->isEnabled(),
            'isPermanentBackupUrlOffered' => $this->protectionService->isPermanentBackupUrlOffered(),
        ]);
        wp_send_json($result);
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
            'backupId'     => md5(basename($fullPath)),
            'downloadLink' => $this->urls->getBackupUrl() . $fileName,
        ];
    }











    private function addParts(string $type, array $files, string $backupFilename): array
    {
        $total = count($files);
        $parts = [];

        foreach ($files as $key => $fileName) {
            $fullPath = $this->backupPathResolver->resolveBackupPartPath($fileName, $backupFilename);
            if ($fullPath === '' || !file_exists($fullPath)) {
                debug_log('WP STAGING: Skipped a backup part that does not belong to this backup: ' . $fileName);
                continue;
            }

            $parts[] = $this->getPart($type, $key, $fileName, $fullPath, $total);
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
