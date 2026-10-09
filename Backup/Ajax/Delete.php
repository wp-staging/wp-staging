<?php

namespace WPStaging\Backup\Ajax;

use Exception;
use SplFileInfo;
use WPStaging\Backup\BackupDeleter;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Exceptions\BackupRuntimeException;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Utils\BackupPathResolver;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Cache\TransientCache;

use function WPStaging\functions\debug_log;

class Delete extends AbstractTemplateComponent
{
 
    private $backupsFinder;

 
    private $backupPathResolver;

 
    private $backupDeleter;

    public function __construct(BackupsFinder $backupsFinder, BackupPathResolver $backupPathResolver, TemplateEngine $templateEngine, BackupDeleter $backupDeleter)
    {
        parent::__construct($templateEngine);
        $this->backupsFinder      = $backupsFinder;
        $this->backupPathResolver = $backupPathResolver;
        $this->backupDeleter      = $backupDeleter;
    }

    public function render()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $md5 = isset($_POST['md5']) ? sanitize_text_field($_POST['md5']) : '';

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
                'message' => __('No backups found, nothing to delete.', 'wp-staging'),
            ]);
        }

        foreach ($backups as $backup) {
            if ($md5 === md5($backup->getBasename())) {
                $this->deleteBackup($backup);
            }
        }
    }





    protected function deleteBackup($backup)
    {
        if (!$this->deleteSplitBackupParts($backup)) {
            return;
        }

 
        $failureReason = $this->backupDeleter->deleteBackupFile($backup->getPathname());
        if ($failureReason !== '') {
            debug_log('WP STAGING: User tried to delete backup ' . $backup->getPathname() . ' but it was kept. ' . $failureReason);

            wp_send_json([
                'error'   => true,
                'message' => esc_html($failureReason),
            ]);

            return;
        }

        delete_transient(TransientCache::KEY_INVALID_BACKUP_FILE_INDEX);
        wp_send_json([
            'error'   => false,
            'message' => __('Successfully deleted the backup.', 'wp-staging'),
        ]);
    }






    protected function deleteSplitBackupParts($backup)
    {
        clearstatcache();

        try {
            $file           = new FileObject($backup->getRealPath(), FileObject::MODE_APPEND_AND_READ);
            $backupMetadata = new BackupMetadata();
            $backupMetadata = $backupMetadata->hydrateByFile($file);
        } catch (Exception $e) {
 
            debug_log('WP STAGING: User tried to delete backup but "unlink" returned false on deleting backup parts. Backup that couldn\'t be deleted: ' . $backup->getRealPath());

            return true;
        }

 
        if (!$backupMetadata->getIsMultipartBackup()) {
            return true;
        }

        $errors = [];

        foreach ($backupMetadata->getMultipartMetadata()->getBackupParts() as $part) {
            $backupPart = $this->backupPathResolver->resolveBackupPartPath($part, $backup->getFilename());
            if ($backupPart === '') {
                debug_log('WP STAGING: Refused to delete a backup part that does not belong to this backup: ' . $part);
                continue;
            }

            if (!file_exists($backupPart)) {
                continue;
            }

            $failureReason = $this->backupDeleter->deleteBackupFile($backupPart);
            if ($failureReason !== '') {
                debug_log('WP STAGING: ' . $failureReason . ' Part: ' . $backupPart);

                $errors[] = $failureReason;
            }
        }

        if (count($errors) === 0) {
            return true;
        }

        wp_send_json([
            'error'    => true,
            'message'  => esc_html(implode(' ', array_unique($errors))),
            'messages' => $errors,
        ]);

        return false;
    }
}
