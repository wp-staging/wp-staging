<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Backup\Ajax;

use Exception;
use SplFileInfo;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Exceptions\BackupRuntimeException;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Cache\TransientCache;

use function WPStaging\functions\debug_log;

class Delete extends AbstractTemplateComponent
{
    private $backupsFinder;

    public function __construct(BackupsFinder $backupsFinder, TemplateEngine $templateEngine)
    {
        parent::__construct($templateEngine);
        $this->backupsFinder = $backupsFinder;
    }

    /**
     * @throws BackupRuntimeException
     */
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

        // Early bail: No backups found, nothing to delete
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

    /**
     * @param SplFileInfo $backup
     * @throws BackupRuntimeException
     */
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

    /**
     * @param SplFileInfo $backup
     *
     * @return bool
     * @throws BackupRuntimeException
     */
    protected function deleteSplitBackupParts($backup)
    {
        clearstatcache();

        try {
            $file = new FileObject($backup->getRealPath(), FileObject::MODE_APPEND_AND_READ);
            $backupMetadata = new BackupMetadata();
            $backupMetadata = $backupMetadata->hydrateByFile($file);
        } catch (Exception $e) {
            // Couldn't read backup metadata, continue deleting the main file but log error
            debug_log('WP STAGING: User tried to delete backup but "unlink" returned false on deleting backup parts. Backup that couldn\'t be deleted: ' . $backup->getRealPath());

            return true;
        }

        // Early bail: Not a split backup
        if (!$backupMetadata->getIsMultipartBackup()) {
            return true;
        }

        $errors = [];

        $backupsDirectory = WPStaging::make(BackupsFinder::class)->getBackupsDirectory();

        foreach ($backupMetadata->getMultipartMetadata()->getBackupParts() as $part) {
            $backupPart = $backupsDirectory . $part;
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
            'error'   => true,
            'message' => '',
            'messages' => $errors
        ]);

        return false;
    }
}
