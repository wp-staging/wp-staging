<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\BackupHeader;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Backup\Entity\BackupMetadata;

class BackupMetadataEditor
{




    public function setBackupMetadata(FileObject $backupFile, BackupMetadata $newMetadata)
    {
        $backupMetadataReader     = new BackupMetadataReader($backupFile);
        $existingMetadataPosition = $backupMetadataReader->getExistingMetadataPosition();

        $backupFile->fseek($existingMetadataPosition);

        $maybeMetadataLine = $backupFile->readAndMoveNext();

 
        if (!is_array($backupMetadataReader->extractMetadata($maybeMetadataLine))) {
            throw new \UnexpectedValueException('Could not find the existing metadata from the backup.');
        }

        $backupFile->ftruncate($existingMetadataPosition);
        $backupFile->fseek($existingMetadataPosition);

        $prepandForSql = '';
        if ($backupFile->isSqlFile()) {
            $prepandForSql = '-- ';
        }

        $backupFile->fwrite($prepandForSql . json_encode($newMetadata) . BackupHeader::LINE_TERMINATOR);
    }







    public function getLineTerminatorOverhead(FileObject $backupFile): int
    {
        $existingMetadataPosition = (new BackupMetadataReader($backupFile))->getExistingMetadataPosition();

        $backupFile->fseek($existingMetadataPosition);
        $existingLine = $backupFile->readAndMoveNext();

        $existingTerminatorLength = strlen($existingLine) - strlen(rtrim($existingLine, "\r\n"));

        return $existingTerminatorLength - strlen(BackupHeader::LINE_TERMINATOR);
    }
}
