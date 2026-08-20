<?php

namespace WPStaging\Backup\Dto\Service;

use WPStaging\Backup\Entity\BackupMetadata;

class ArchiverDto
{
 
    private $filePath;

 
    private $indexPath;

 
    private $fileHeaderSizeInBytes = 0;

 
    private $writtenBytesTotal = 0;

 
    private $startOffset = 0;

 
    private $fileSize;

 
    private $indexPositionCreated = [];

 
    private $backupMetadata;






    private $isContinuation = false;






    private $sourceBytesWrittenTotal = 0;





    public function appendWrittenBytes(int $bytes)
    {
        $this->writtenBytesTotal += (int) $bytes;
    }




    public function isFinished(): bool
    {
        return $this->fileSize <= $this->writtenBytesTotal;
    }




    public function resetIfFinished()
    {
        if ($this->isFinished()) {
            $this->reset();
        }
    }




    public function reset()
    {
        $this->setFileSize(-1);
        $this->setFilePath('');
        $this->setWrittenBytesTotal(0);
        $this->setIndexPositionCreated(false);
        $this->setFileHeaderSizeInBytes(0);
        $this->setStartOffset(0);
        $this->setIsContinuation(false);
        $this->setSourceBytesWrittenTotal(0);
    }

    public function isContinuation(): bool
    {
        return $this->isContinuation;
    }

    public function setIsContinuation(bool $isContinuation)
    {
        $this->isContinuation = $isContinuation;
    }

    public function getSourceBytesWrittenTotal(): int
    {
        return $this->sourceBytesWrittenTotal;
    }

    public function setSourceBytesWrittenTotal(int $sourceBytesWrittenTotal)
    {
        $this->sourceBytesWrittenTotal = $sourceBytesWrittenTotal;
    }




    public function getFilePath(): string
    {
        return (string)$this->filePath;
    }





    public function setFilePath(string $filePath)
    {
        $this->filePath = wp_normalize_path((string)$filePath);
    }




    public function getIndexPath(): string
    {
        return $this->indexPath;
    }





    public function setIndexPath(string $indexPath)
    {
        $this->indexPath = wp_normalize_path((string)$indexPath);
    }




    public function getWrittenBytesTotal(): int
    {
 
        return (int) $this->writtenBytesTotal;
    }





    public function setWrittenBytesTotal(int $writtenBytesTotal)
    {
        $this->writtenBytesTotal = $writtenBytesTotal;
    }




    public function getFileSize(): int
    {
        return (int)$this->fileSize;
    }





    public function setFileSize(int $fileSize)
    {
        $this->fileSize = $fileSize;
    }




    public function getFileHeaderSizeInBytes(): int
    {
        return $this->fileHeaderSizeInBytes;
    }





    public function setFileHeaderSizeInBytes(int $fileHeaderSizeInBytes)
    {
        $this->fileHeaderSizeInBytes = $fileHeaderSizeInBytes;
    }






    public function isIndexPositionCreated(string $category = '', int $categoryIndex = 0): bool
    {
        if (!isset($this->indexPositionCreated[$category])) {
            return false;
        }

        return (bool)$this->indexPositionCreated[$category][$categoryIndex];
    }

    public function isFileHeaderWritten(): bool
    {
        return $this->fileHeaderSizeInBytes > 0;
    }







    public function setIndexPositionCreated(bool $indexPositionCreated, string $category = '', int $categoryIndex = 0)
    {
        if (!isset($this->indexPositionCreated[$category])) {
            $this->indexPositionCreated[$category] = [];
        }

        $this->indexPositionCreated[$category][$categoryIndex] = (bool)$indexPositionCreated;
    }





    public function setStartOffset(int $startOffset)
    {
        $this->startOffset = $startOffset;
    }




    public function getStartOffset(): int
    {
        return $this->startOffset;
    }




    public function getBackupMetadata(): BackupMetadata
    {
        if (!$this->backupMetadata) {
            $this->backupMetadata = new BackupMetadata();
        }

        return $this->backupMetadata;
    }





    public function setBackupMetadata(BackupMetadata $backupMetadata)
    {
        $this->backupMetadata = $backupMetadata;
    }
}
