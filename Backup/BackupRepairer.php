<?php

namespace WPStaging\Backup;

use Exception;
use RuntimeException;
use UnexpectedValueException;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Backup\Service\BackupMetadataEditor;
use WPStaging\Framework\Filesystem\FileObject;

class BackupRepairer
{
    /** @var BackupMetadataEditor */
    private $metadataEditor;

    /** @var string|false */
    private $error = false;

    public function __construct(BackupMetadataEditor $metadataEditor)
    {
        $this->metadataEditor = $metadataEditor;
    }

    /** @return string|false */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param string $filePath
     * @return bool true on success. False on error
     */
    public function repairMetadataSize($filePath)
    {
        $this->error = false;

        $file = null;
        try {
            $file = new FileObject($filePath, FileObject::MODE_APPEND_AND_READ);
        } catch (DiskNotWritableException $ex) {
            $this->error = $ex->getMessage();
            return false;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            return false;
        }

        // Early bail if file is not wpstg
        if ($file->getExtension() !== 'wpstg') {
            return true;
        }

        $backupMetadata = new BackupMetadata();
        try {
            $backupMetadata->hydrateByFile($file);
        } catch (RuntimeException $ex) {
            $this->error = $ex->getMessage();
            return false;
        }

        // Early bail if size is not zero
        if ($backupMetadata->getBackupSize() !== 0) {
            return true;
        }

        /*
         * The length of the backup file size in bytes is added to the total file size
         *
         * Before: "backupSize": "" // 2 bytes are already consumed by the string ""
         * After:  "backupSize": 123456 // 4 additional bytes are added = 6 (4+2)
         */
        $backupSize = $file->getSize() - 2 + strlen($file->getSize());
        if ($backupSize < 1) {
            $this->error = __('Backup size cannot be zero or less', 'wp-staging');
            return false;
        }

        $backupMetadata->setBackupSize($backupSize);

        try {
            $this->metadataEditor->setBackupMetadata($file, $backupMetadata);
        } catch (UnexpectedValueException $ex) {
            $this->error = $ex->getMessage();
            return false;
        }

        return true;
    }
}
