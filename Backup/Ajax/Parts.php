<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Urls;

class Parts extends AbstractTemplateComponent
{
    private $backupsFinder;
    private $urls;

    public function __construct(TemplateEngine $templateEngine, BackupsFinder $backupsFinder, Urls $urls)
    {
        parent::__construct($templateEngine);
        $this->backupsFinder = $backupsFinder;
        $this->urls = $urls;
    }

    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        // Make sure path is inside Backups Directory
        $backupDir = wp_normalize_path($this->backupsFinder->getBackupsDirectory());

        $indexFile = isset($_POST['filePath']) ? Sanitize::sanitizePath($_POST['filePath']) : '';

        if ($indexFile === '') {
            wp_send_json([
                'error'   => true,
                'message' => 'Backup file path not provided!',
            ]);
        }

        $file = $this->getFullPath($backupDir, $indexFile);

        try {
            $info = (new BackupMetadata())->hydrateByFilePath($file);
        } catch (\Exception $e) {
            wp_send_json([
                'error'   => true,
                'message' => $e->getMessage(),
            ]);
        }

        $parts = [];

        $metadata = $info->getMultipartMetadata();
        $pluginParts = $metadata->getPluginsParts();
        $totalPluginParts = count($pluginParts);
        foreach ($pluginParts as $key => $fileName) {
            $fullPath = $this->getFullPath($backupDir, $fileName);
            $parts[] = $this->getPart('Plugins', $key, $fileName, $fullPath, $totalPluginParts);
        }

        $themesParts = $metadata->getThemesParts();
        $totalThemesParts = count($themesParts);
        foreach ($themesParts as $key => $fileName) {
            $fullPath = $this->getFullPath($backupDir, $fileName);
            $parts[] = $this->getPart('Themes', $key, $fileName, $fullPath, $totalThemesParts);
        }

        $uploadsParts = $metadata->getUploadsParts();
        $totalUploadsParts = count($uploadsParts);
        foreach ($uploadsParts as $key => $fileName) {
            $fullPath = $this->getFullPath($backupDir, $fileName);
            $parts[] = $this->getPart('Medias', $key, $fileName, $fullPath, $totalUploadsParts);
        }

        $muPluginsParts = $metadata->getMuPluginsParts();
        $totalMuPluginsParts = count($muPluginsParts);
        foreach ($muPluginsParts as $key => $fileName) {
            $fullPath = $this->getFullPath($backupDir, $fileName);
            $parts[] = $this->getPart('Mu Plugins', $key, $fileName, $fullPath, $totalMuPluginsParts);
        }

        $othersParts = $metadata->getOthersParts();
        $totalOthersParts = count($othersParts);
        foreach ($othersParts as $key => $fileName) {
            $fullPath = $this->getFullPath($backupDir, $fileName);
            $parts[] = $this->getPart('Others', $key, $fileName, $fullPath, $totalOthersParts);
        }

        $databaseParts = $metadata->getDatabaseParts();
        $totalDatabaseParts = count($databaseParts);
        foreach ($databaseParts as $key => $fileName) {
            $fullPath = $this->getFullPath($backupDir, $fileName);
            $parts[] = $this->getPart('Database', $key, $fileName, $fullPath, $totalDatabaseParts);
        }

        $result = wp_send_json([
            'error' => false,
            'parts' => $parts,
        ]);

        wp_send_json($result);
    }

    private function getFullPath($backupDir, $relativePath)
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
     */
    private function getPart($type, $key, $fileName, $fullPath, $totalParts)
    {

        $pluginName = $type;
        $currentKey = ($key + 1);

        if ($totalParts > 1) {
            $pluginName = $type . ' ' . $currentKey . ' / ' . $totalParts;
        }

        return [
            'name' => $pluginName,
            'fileSize' => size_format(filesize($fullPath), 2),
            'downloadLink' => $this->urls->getBackupUrl() . $fileName
        ];
    }
}
