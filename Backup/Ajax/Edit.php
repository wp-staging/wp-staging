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

        $name = substr(sanitize_text_field(html_entity_decode($name)), 0, 100);
        $name = htmlentities($name, ENT_QUOTES);
        $name = str_replace('\\\'', '\'', $name);
        $name = str_replace('\\\"', '\"', $name);

        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        $notes = substr($notes, 0, 1000);

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

                    $increment = strlen($name) + strlen($notes);

                    if ($metaData->getIsMultipartBackup()) {
                        $this->updateBackupParts($metaData, $name, $notes, $increment, $backup->getFilename());
                        return;
                    }

                    $oldNote       = $metaData->getNote();
                    $oldNoteLength = 2; 
                    if ($oldNote !== null) {
                        $oldNoteLength = strlen($oldNote);
                    }

                    $backupSize = $metaData->getBackupSize();
                    $backupSize = $backupSize + $increment - strlen($metaData->getName()) - $oldNoteLength;

                    $metaData->setName($name);
                    $metaData->setNote($notes);
                    $metaData->setBackupSize($backupSize);

                    $this->backupMetadataEditor->setBackupMetadata($file, $metaData);
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








    protected function updateBackupParts($metaData, $name, $notes, $incrementSize, $backupFilename)
    {
        $backupSize  = 0;
        $backupParts = [];
        foreach ($metaData->getMultipartMetadata()->getBackupParts() as $part) {
            $backupPart = $this->backupPathResolver->resolveBackupPartPath($part, $backupFilename);
            if ($backupPart === '') {
                continue;
            }

            $partFile     = new FileObject($backupPart, FileObject::MODE_APPEND_AND_READ);
            $partMetadata = (new BackupMetadata())->hydrateByFile($partFile);
            $partSize     = filesize($backupPart);

            $oldNote       = $partMetadata->getNote();
            $oldNoteLength = 2;
            if ($oldNote !== null) {
                $oldNoteLength = strlen($oldNote);
            }

            $partSize = $partSize + $incrementSize - strlen($partMetadata->getName()) - $oldNoteLength;
            $backupSize += $partSize;
            $backupParts[] = [
                'metadata' => $partMetadata,
                'file'     => $partFile,
                'size'     => $partSize,
            ];
        }

        foreach ($backupParts as $part) {
 
            $partMetadata = $part['metadata'];
            $partMetadata->setName($name);
            $partMetadata->setNote($notes);
            $partMetadata->setBackupSize($backupSize);

            $multipartMetadata = $partMetadata->getMultipartMetadata();
            $multipartMetadata->setPartSize($part['size']);
            $partMetadata->setMultipartMetadata($multipartMetadata);

            $this->backupMetadataEditor->setBackupMetadata($part['file'], $partMetadata);
        }
    }
}
