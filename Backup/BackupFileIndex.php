<?php

namespace WPStaging\Backup;

use WPStaging\Framework\Filesystem\PathIdentifier;

class BackupFileIndex
{
    /** @var int */
    public $bytesStart;

    /** @var int */
    public $bytesEnd;

    /** @var string */
    public $identifiablePath;

    /** @var int */
    public $isCompressed;

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

    public function isIndexLine($item): bool
    {
        return !empty($item) && strpos($item, ':') !== false && strpos($item, '|') !== false;
    }
}
