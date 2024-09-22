<?php

namespace WPStaging\Backup\Service;

use WPStaging\Framework\Filesystem\FileObject;

class BackupMetadataReader
{
    /** @var int */
    private $existingMetadataPosition;

    /** @var FileObject */
    private $fileObject;

    public function __construct(FileObject $fileObject)
    {
        $this->fileObject = $fileObject;
    }

    /**
     * @return array The backup metadata array
     * @throws \RuntimeException
     */
    public function readBackupMetadata(): array
    {
        // Default max size 128KB for backup metadata
        $maxBackupMetadataSize = $this->getExpectedMaxBackupMetadataSize();
        // Make sure the max size is never above 1MB
        $negativeOffset = min($maxBackupMetadataSize, 1 * MB_IN_BYTES);
        // Make sure the max size is never below 32KB
        $negativeOffset = max($negativeOffset, 32 * KB_IN_BYTES);

        // Set the pointer to the end of the file, minus the negative offset for which to start looking for the backup metadata.
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

    /**
     * @param string $line
     * @return bool
     */
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

        $network = $maybeMetadata['networks']['1'];
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

        return apply_filters('wpstg_max_backup_metadata_size', $maxBackupMetadataSize);
    }
}
