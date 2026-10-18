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

        $encodedMetadata = json_encode($newMetadata);
        if ($encodedMetadata === false) {
            throw new \UnexpectedValueException('Could not encode the new metadata of the backup.');
        }

        $backupFile->ftruncate($existingMetadataPosition);
        $backupFile->fseek($existingMetadataPosition);

        $prepandForSql = '';
        if ($backupFile->isSqlFile()) {
            $prepandForSql = '-- ';
        }

        $backupFile->fwrite($prepandForSql . $encodedMetadata . BackupHeader::LINE_TERMINATOR);
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
