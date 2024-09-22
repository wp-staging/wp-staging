<?php

namespace WPStaging\Backup;

use WPStaging\Framework\Job\Exception\FileValidationException;
use WPStaging\Backup\Interfaces\IndexLineInterface;
use WPStaging\Framework\Traits\FormatTrait;

class BackupFileIndex implements IndexLineInterface
{
    use FormatTrait;

    /** @var int */
    public $bytesStart;

    /** @var int */
    public $bytesEnd;

    /** @var string */
    public $identifiablePath;

    /** @var int */
    public $isCompressed;

    public function __construct()
    {
        $this->bytesStart       = 0;
        $this->bytesEnd         = 0;
        $this->identifiablePath = '';
        $this->isCompressed     = 0;
    }

    /**
     * @param string $index
     * @return BackupFileIndex
     */
    public function readIndex(string $index): BackupFileIndex
    {
        /*
         * We start with a string that is the backup file index, like this:
         *
         *     wpstg_t_/twentytwentyone/readme.txt|9378469:4491
         *
         * We split it into two parts, using the pipe "|" character as the delimiter.
         * The first part is the identifiable path, the second is the metadata about the file.
         *
         * By "Identifiable Path", we mean a path that has a prefix that identifies
         * what kind of file it is, such as a plugin, mu-plugin, theme, etc.
         */
        list($identifiablePath, $entryMetadata) = explode('|', trim($index));

        $entryMetadata = explode(':', trim($entryMetadata));

        // This should never happen.
        if (count($entryMetadata) < 2) {
            // todo: Log this when we have a logger.
            throw new \UnexpectedValueException('Invalid backup file index.');
        }

        $offsetStart       = (int)$entryMetadata[0];
        $writtenPreviously = (int)$entryMetadata[1];

        if (count($entryMetadata) >= 3) {
            $isCompressed = (int)$entryMetadata[2];
        } else {
            $isCompressed = 0;
        }

        $backupFileIndex = new BackupFileIndex();

        // Replace the placeholder with the pipe character.
        $backupFileIndex->identifiablePath = str_replace(['{WPSTG_PIPE}', '{WPSTG_COLON}'], ['|', ':'], $identifiablePath);
        $backupFileIndex->bytesStart       = $offsetStart;
        $backupFileIndex->bytesEnd         = $writtenPreviously;
        $backupFileIndex->isCompressed     = $isCompressed;

        return $backupFileIndex;
    }

    /**
     * For compatibility with IndexLineInterface
     * @param string $indexLine
     * @return IndexLineInterface
     */
    public function readIndexLine(string $indexLine): IndexLineInterface
    {
        return $this->readIndex($indexLine);
    }

    /**
     * Creates an index entry for a file to be added to the backup's file index.
     *
     * @param string $identifiablePath The identifiable path to the file.
     * @param int $bytesStart The offset in the backup file where the file starts.
     * @param int $bytesEnd The offset in the backup file where the file ends.
     * @param int $isCompressed Whether the file is compressed.
     *
     * @see PathIdentifier For definition of identifiable path.
     *
     * @return BackupFileIndex
     */
    public function createIndex(string $identifiablePath, int $bytesStart, int $bytesEnd, int $isCompressed): BackupFileIndex
    {
        $backupFileIndex = new BackupFileIndex();

        // Replace the pipe character with a placeholder to avoid conflicts.
        $backupFileIndex->identifiablePath = str_replace(['|', ':'], ['{WPSTG_PIPE}', '{WPSTG_COLON}'], $identifiablePath);
        $backupFileIndex->bytesStart       = $bytesStart;
        $backupFileIndex->bytesEnd         = $bytesEnd;
        $backupFileIndex->isCompressed     = $isCompressed;

        return $backupFileIndex;
    }

    public function getIndex(): string
    {
        return "$this->identifiablePath|$this->bytesStart:$this->bytesEnd:$this->isCompressed";
    }

    public function isIndexLine(string $item): bool
    {
        return !empty($item) && strpos($item, ':') !== false && strpos($item, '|') !== false;
    }

    /**
     * Compatibility for new extractor on old file format
     * Points to start of file content
     *
     * @return int
     */
    public function getContentStartOffset(): int
    {
        return $this->bytesStart;
    }

    /**
     * Compatibility for new extractor on old file format
     * Points to start of file content (in new format, it points to File Header)
     *
     * @return int
     */
    public function getStartOffset(): int
    {
        return $this->bytesStart;
    }

    public function getIdentifiablePath(): string
    {
        return $this->identifiablePath;
    }

    /**
     * Compatibility for new extractor on old file format
     * Old format can either support compressed or uncompressed size
     *
     * @return int
     */
    public function getUncompressedSize(): int
    {
        return $this->bytesEnd;
    }

    /**
     * Compatibility for new extractor on old file format
     * Old format can either support compressed or uncompressed size
     *
     * @return int
     */
    public function getCompressedSize(): int
    {
        return $this->bytesEnd;
    }

    public function getIsCompressed(): bool
    {
        return $this->isCompressed === 1;
    }

    /**
     * @param string $filePath
     * @param string $pathForErrorLogging
     * @return void
     * @throws FileValidationException
     * Doesn't support crc32 validation
     */
    public function validateFile(string $filePath, string $pathForErrorLogging = '')
    {
        if (empty($pathForErrorLogging)) {
            $pathForErrorLogging = $filePath;
        }

        if (!file_exists($filePath)) {
            throw new FileValidationException(sprintf('File doesn\'t exist: %s.', $pathForErrorLogging));
        }

        // Doesn't support file size validation for compressed files
        if ($this->getIsCompressed()) {
            return;
        }

        $fileSize = filesize($filePath);
        if ($this->getUncompressedSize() !== $fileSize) {
            throw new FileValidationException(sprintf('Filesize validation failed for file %s. Expected: %s. Actual: %s', $pathForErrorLogging, $this->formatSize($this->getUncompressedSize(), 2), $this->formatSize($fileSize, 2)));
        }
    }
}
