<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupMetadataEditor;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Utils\BackupPathResolver;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Sanitize;

class Edit extends AbstractTemplateComponent
{
    const MAX_SIZE_WRITE_ATTEMPTS = 10;

 
    private $backupMetadataEditor;

 
    private $backupsFinder;

 
    private $backupPathResolver;

 
    private $sanitize;

    public function __construct(
        BackupsFinder $backupsFinder,
        BackupMetadataEditor $backupMetadataEditor,
        BackupPathResolver $backupPathResolver,
        Sanitize $sanitize,
        TemplateEngine $templateEngine
    ) {
        parent::__construct($templateEngine);
        $this->backupsFinder        = $backupsFinder;
        $this->backupMetadataEditor = $backupMetadataEditor;
        $this->backupPathResolver   = $backupPathResolver;
        $this->sanitize             = $sanitize;
    }

    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $md5 = sanitize_text_field(isset($_POST['md5']) ? $_POST['md5'] : '');

        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : 'Backup';

        $name = mb_substr(sanitize_text_field(html_entity_decode($name)), 0, 100);
        $name = htmlentities($name, ENT_QUOTES);
        $name = str_replace('\\\'', '\'', $name);
        $name = str_replace('\\\"', '\"', $name);

        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        $notes = mb_substr($notes, 0, 1000);

        if (strlen($md5) !== 32) {
            wp_send_json([
                'error'   => true,
                'message' => __('Invalid request.', 'wp-staging'),
            ]);
        }

        $backups = $this->backupsFinder->findBackups();

 
        if (empty($backups)) {
            wp_send_json([
                'error'   => true,
                'message' => __('No backups found, nothing to edit.', 'wp-staging'),
            ]);
        }

 
        if (empty($name)) {
            $name = __('Backup', 'wp-staging');
        }

 
        foreach ($backups as $backup) {
            if ($md5 === md5($backup->getBasename())) {
                try {
                    $file     = new FileObject($backup->getPathname(), FileObject::MODE_APPEND_AND_READ);
                    $metaData = (new BackupMetadata())->hydrateByFile($file);

                    if ($metaData->getIsMultipartBackup()) {
                        $this->updateBackupParts($metaData, $name, $notes, $backup->getFilename());
                        return;
                    }

                    $metaData->setName($name);
                    $metaData->setNote($notes);

                    $this->writeMetadataUntilSizesHold([['file' => $file, 'metadata' => $metaData]]);
                } catch (\Exception $e) {
                    wp_send_json([
                        'error'   => true,
 
                        'message' => esc_html__($e->getMessage(), 'wp-staging'),
                    ]);
                }
            }
        }

        wp_send_json(true);
    }







    protected function updateBackupParts($metaData, $name, $notes, $backupFilename)
    {
        $backupParts = [];
        foreach ($metaData->getMultipartMetadata()->getBackupParts() as $part) {
            $backupPart = $this->backupPathResolver->resolveBackupPartPath($part, $backupFilename);
            if ($backupPart === '') {
                continue;
            }

            $partFile     = new FileObject($backupPart, FileObject::MODE_APPEND_AND_READ);
            $partMetadata = (new BackupMetadata())->hydrateByFile($partFile);
            $partMetadata->setName($name);
            $partMetadata->setNote($notes);

            $backupParts[] = [
                'file'     => $partFile,
                'metadata' => $partMetadata,
            ];
        }

        $this->writeMetadataUntilSizesHold($backupParts);
    }








    private function writeMetadataUntilSizesHold(array $backupFiles)
    {
        $sizes = [];
        foreach ($backupFiles as $index => $backupFile) {
            $sizes[$index] = (int)filesize($backupFile['file']->getPathname());
        }

        for ($attempt = 0; $attempt < self::MAX_SIZE_WRITE_ATTEMPTS; $attempt++) {
            $measured = [];
            foreach ($backupFiles as $index => $backupFile) {
 
                $metadata = $backupFile['metadata'];
                $metadata->setBackupSize($metadata->getIsMultipartBackup() ? array_sum($sizes) : $sizes[$index]);
                if ($metadata->getIsMultipartBackup()) {
                    $multipartMetadata = $metadata->getMultipartMetadata();
                    $multipartMetadata->setPartSize($sizes[$index]);
                    $metadata->setMultipartMetadata($multipartMetadata);
                }

                $this->backupMetadataEditor->setBackupMetadata($backupFile['file'], $metadata);
                clearstatcache(true, $backupFile['file']->getPathname());
                $measured[$index] = (int)filesize($backupFile['file']->getPathname());
            }

            if ($measured === $sizes) {
                return;
            }

            $sizes = $measured;
        }

        throw new \RuntimeException('Cannot determine the size to record in the backup metadata.');
    }
}
