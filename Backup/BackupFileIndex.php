<?php

namespace WPStaging\Backup;

use WPStaging\Framework\Job\Exception\FileValidationException;
use WPStaging\Backup\Interfaces\IndexLineInterface;
use WPStaging\Framework\Traits\FormatTrait;

class BackupFileIndex implements IndexLineInterface
{
    use FormatTrait;

 
    public $bytesStart;

 
    public $bytesEnd;

 
    public $identifiablePath;

 
    public $isCompressed;

    public function __construct()
    {
        $this->bytesStart       = 0;
        $this->bytesEnd         = 0;
        $this->identifiablePath = '';
        $this->isCompressed     = 0;
    }





    public function readIndex(string $index): BackupFileIndex
    {











        list($identifiablePath, $entryMetadata) = explode('|', trim($index));

        $entryMetadata = explode(':', trim($entryMetadata));

 
        if (count($entryMetadata) < 2) {
 
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

 
        $backupFileIndex->identifiablePath = str_replace(['{WPSTG_PIPE}', '{WPSTG_COLON}'], ['|', ':'], $identifiablePath);
        $backupFileIndex->bytesStart       = $offsetStart;
        $backupFileIndex->bytesEnd         = $writtenPreviously;
        $backupFileIndex->isCompressed     = $isCompressed;

        return $backupFileIndex;
    }






    public function readIndexLine(string $indexLine): IndexLineInterface
    {
        return $this->readIndex($indexLine);
    }













    public function createIndex(string $identifiablePath, int $bytesStart, int $bytesEnd, int $isCompressed): BackupFileIndex
    {
        $backupFileIndex = new BackupFileIndex();

 
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







    public function getContentStartOffset(): int
    {
        return $this->bytesStart;
    }







    public function getStartOffset(): int
    {
        return $this->bytesStart;
    }

    public function getIdentifiablePath(): string
    {
        return $this->identifiablePath;
    }







    public function getUncompressedSize(): int
    {
        return $this->bytesEnd;
    }







    public function getCompressedSize(): int
    {
        return $this->bytesEnd;
    }

    public function getIsCompressed(): bool
    {
        return $this->isCompressed === 1;
    }








    public function validateFile(string $filePath, string $pathForErrorLogging = '')
    {
        if (empty($pathForErrorLogging)) {
            $pathForErrorLogging = $filePath;
        }

        if (!file_exists($filePath)) {
            throw new FileValidationException(sprintf('File doesn\'t exist: %s.', $pathForErrorLogging));
        }

 
        if ($this->getIsCompressed()) {
            return;
        }

        $fileSize = filesize($filePath);
        if ($this->getUncompressedSize() !== $fileSize) {
            throw new FileValidationException(sprintf('Filesize validation failed for file %s. Expected: %s. Actual: %s', $pathForErrorLogging, $this->formatSize($this->getUncompressedSize(), 2), $this->formatSize($fileSize, 2)));
        }
    }
}
