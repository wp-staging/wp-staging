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
 
    private $metadataEditor;

 
    private $error = false;

    public function __construct(BackupMetadataEditor $metadataEditor)
    {
        $this->metadataEditor = $metadataEditor;
    }

 
    public function getError()
    {
        return $this->error;
    }





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

 
        if ($backupMetadata->getBackupSize() !== 0) {
            return true;
        }







        $fileSize = $file->getSize();
        if ($fileSize === false || $fileSize < 1) {
            $this->error = __('Backup size cannot be determined or is zero', 'wp-staging');
            return false;
        }

        $backupSize = $fileSize - 2 + strlen((string)$fileSize);
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
