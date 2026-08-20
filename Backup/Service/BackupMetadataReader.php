<?php

namespace WPStaging\Backup\Service;

use WPStaging\Framework\Filesystem\FileObject;

class BackupMetadataReader
{
 
    const FILTER_MAX_BACKUP_METADATA_SIZE = 'wpstg_max_backup_metadata_size';

 
    private $existingMetadataPosition;

 
    private $fileObject;

    public function __construct(FileObject $fileObject)
    {
        $this->fileObject = $fileObject;
    }





    public function readBackupMetadata(): array
    {
 
        $maxBackupMetadataSize = $this->getExpectedMaxBackupMetadataSize();
 
        $negativeOffset = min($maxBackupMetadataSize, 1 * MB_IN_BYTES);
 
        $negativeOffset = max($negativeOffset, 32 * KB_IN_BYTES);

 
        $this->fileObject->fseek(max($this->fileObject->getSize() - $negativeOffset, 0), SEEK_SET);

        $backupMetadata = null;

        do {
            $this->existingMetadataPosition = $this->fileObject->ftell();
            $line                           = trim($this->fileObject->readAndMoveNext());
            if ($this->isValidMetadata($line)) {
                $backupMetadata = $this->extractMetadata($line);
            }
        } while ($this->fileObject->valid() && !is_array($backupMetadata));

        if (!is_array($backupMetadata)) {
            $error = sprintf('Could not find metadata in the backup file %s - This file could be corrupt.', $this->fileObject->getFilename());
            throw new \RuntimeException($error);
        }

        return $backupMetadata;
    }

    public function extractMetadata(string $line): array
    {
        $json = [];
        if (!$this->fileObject->isSqlFile()) {
            $json = json_decode($line, true);
        } else {
            $json = json_decode(substr($line, 3), true);
        }

        return empty($json) ? [] : $json;
    }





    public function isValidMetadata(string $line): bool
    {
        if ($this->fileObject->isSqlFile() && substr($line, 3, 1) !== '{') {
            return false;
        } elseif (!$this->fileObject->isSqlFile() && substr($line, 0, 1) !== '{') {
            return false;
        }

        $maybeMetadata = $this->extractMetadata($line);

        if (!is_array($maybeMetadata) || !array_key_exists('networks', $maybeMetadata) || !is_array($maybeMetadata['networks'])) {
            return false;
        }

 
        $network = array_pop($maybeMetadata['networks']);
        if (!is_array($network) || !array_key_exists('blogs', $network) || !is_array($network['blogs'])) {
            return false;
        }

        return true;
    }

    public function getExistingMetadataPosition(): int
    {
        if ($this->existingMetadataPosition === null) {
            $this->readBackupMetadata();
        }

        return $this->existingMetadataPosition;
    }

    private function getExpectedMaxBackupMetadataSize(): int
    {
        $maxBackupMetadataSize = 128 * KB_IN_BYTES;
        if (!function_exists('apply_filters')) {
            return $maxBackupMetadataSize;
        }

        return apply_filters(self::FILTER_MAX_BACKUP_METADATA_SIZE, $maxBackupMetadataSize);
    }
}
