<?php

namespace WPStaging\Backup\Service;

use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Backup\Entity\BackupMetadata;

class BackupMetadataEditor
{
    /**
     * @param FileObject     $backupFile It must be opened with File::MODE_APPEND
     * @param BackupMetadata $newMetadata
     */
    public function setBackupMetadata(FileObject $backupFile, BackupMetadata $newMetadata)
    {
        $backupMetadataReader     = new BackupMetadataReader($backupFile);
        $existingMetadataPosition = $backupMetadataReader->getExistingMetadataPosition();

        $backupFile->fseek($existingMetadataPosition);

        $maybeMetadataLine = $backupFile->readAndMoveNext();

        // Validate metadata position
        if (!is_array($backupMetadataReader->extractMetadata($maybeMetadataLine))) {
            throw new \UnexpectedValueException('Could not find the existing metadata from the backup.');
        }

        $backupFile->ftruncate($existingMetadataPosition);
        $backupFile->fseek($existingMetadataPosition);

        $prepandForSql = '';
        if ($backupFile->isSqlFile()) {
            $prepandForSql = '-- ';
        }

        $backupFile->fwrite($prepandForSql . json_encode($newMetadata) . "\n");
    }
}
