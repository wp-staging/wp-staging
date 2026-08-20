<?php

namespace WPStaging\Backup\Entity;

use WPStaging\Backup\Interfaces\IndexLineInterface;
use WPStaging\Framework\Filesystem\PathIdentifier;











class FileBeingExtracted
{





    private $identifiablePath;

 
    private $relativePath;

 
    private $start;

 
    private $totalBytes;

 
    private $writtenBytes = 0;

 
    private $readBytes = 0;

 
    protected $extractFolder;

 
    protected $pathIdentifier;

 
    protected $isCompressed;

 
    protected $headerBytesRemoved = 0;

    public function __construct(string $identifiablePath, string $extractFolder, PathIdentifier $pathIdentifier, IndexLineInterface $backupFileIndex)
    {
        $this->identifiablePath = $identifiablePath;
        $this->extractFolder    = rtrim($extractFolder, '/') . '/';
        $this->start            = $backupFileIndex->getContentStartOffset();
        $this->totalBytes       = $backupFileIndex->getUnCompressedSize();
        $this->pathIdentifier   = $pathIdentifier;
        $this->isCompressed     = (int)$backupFileIndex->getIsCompressed();
        $this->relativePath     = $this->pathIdentifier->getPathWithoutIdentifier($this->identifiablePath);
    }






    public function getBackupPath()
    {
        return $this->extractFolder . $this->relativePath;
    }







    public function findReadTo()
    {
        $maxLengthToRead = 512 * KB_IN_BYTES;

        $remainingBytesToRead = $this->totalBytes - $this->readBytes;

        return max(0, min($remainingBytesToRead, $maxLengthToRead));
    }




    public function getPath()
    {
        return $this->identifiablePath;
    }




    public function getRelativePath()
    {
        return $this->relativePath;
    }




    public function getStart()
    {
        return $this->start;
    }




    public function getTotalBytes()
    {
        return $this->totalBytes;
    }




    public function getWrittenBytes()
    {
        return $this->writtenBytes;
    }




    public function setWrittenBytes($writtenBytes)
    {
        $this->writtenBytes = $writtenBytes;
    }

    public function addWrittenBytes($writtenBytes)
    {
        $this->writtenBytes += $writtenBytes;
    }

    public function getReadBytes(): int
    {
        return $this->readBytes;
    }





    public function setReadBytes(int $readBytes)
    {
        $this->readBytes = $readBytes;
    }





    public function addReadBytes(int $readBytes)
    {
        $this->readBytes += $readBytes;
    }

    public function isFinished()
    {
        if (!$this->areHeaderBytesRemoved()) {
            return $this->writtenBytes >= $this->totalBytes;
        }

        return $this->writtenBytes >= ($this->totalBytes - $this->headerBytesRemoved);
    }




    public function getIsCompressed()
    {
        return $this->isCompressed;
    }

    public function getCurrentOffset(): int
    {
        return $this->start + $this->readBytes;
    }





    public function addHeaderBytesRemoved(int $headerBytesRemoved)
    {
        $this->headerBytesRemoved += $headerBytesRemoved;
    }

    public function getHeaderBytesRemoved(): int
    {
        return $this->headerBytesRemoved;
    }





    public function setHeaderBytesRemoved(int $headerBytesRemoved)
    {
        $this->headerBytesRemoved = $headerBytesRemoved;
    }

    public function areHeaderBytesRemoved(): bool
    {
        return $this->headerBytesRemoved > 0;
    }
}
