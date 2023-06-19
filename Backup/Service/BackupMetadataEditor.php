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
        $existingMetadataPosition = $backupFile->getExistingMetadataPosition();

        $backupFile->fseek($existingMetadataPosition);

        $maybeMetadataLine = $backupFile->readAndMoveNext();

        // Validate metadata position
        if (!is_array($backupFile->extractMetadata($maybeMetadataLine))) {
            throw new \UnexpectedValueException('Could not find the existing metadata from the backup.');
        }

        $backupFile->ftruncate($existingMetadataPosition);
        $backupFile->fseek($existingMetadataPosition);

        $prepandForSql = '';
        if ($backupFile->isSqlFile()) {
            $prepandForSql = '-- ';
        }

        $backupFile->fwrite($prepandForSql . json_encode($newMetadata) . PHP_EOL);
    }
}
