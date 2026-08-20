<?php

namespace WPStaging\Backup\Ajax;

use Exception;
use SplFileInfo;
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

    public function __construct(BackupsFinder $backupsFinder, BackupPathResolver $backupPathResolver, TemplateEngine $templateEngine)
    {
        parent::__construct($templateEngine);
        $this->backupsFinder      = $backupsFinder;
        $this->backupPathResolver = $backupPathResolver;
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

        $deleted = unlink($backup->getRealPath());

        if ($deleted) {
            delete_transient(TransientCache::KEY_INVALID_BACKUP_FILE_INDEX);
            wp_send_json([
                'error'   => false,
                'message' => __('Successfully deleted the backup.', 'wp-staging'),
            ]);
        } else {
            debug_log('WP STAGING: User tried to delete backup but "unlink" returned false. Backup that couldn\'t be deleted: ' . $backup->getRealPath());

            wp_send_json([
                'error'   => true,
                'message' => __('Could not delete the backup. Maybe a permission issue?', 'wp-staging'),
            ]);
        }
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

            $deleted = unlink($backupPart);
            if (!$deleted) {
                $error = "Couldn't delete backup part. Maybe Permission Issue? Part: " . $backupPart;
                debug_log('WP STAGING: ' . $error);

                $errors[] = $error;
            }
        }

        if (count($errors) === 0) {
            return false;
        }

        wp_send_json([
            'error'    => true,
            'message'  => '',
            'messages' => $errors,
        ]);

        return false;
    }
}
