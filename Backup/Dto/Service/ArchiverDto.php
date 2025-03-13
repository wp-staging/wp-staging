<?php

namespace WPStaging\Backup\Dto\Service;

use WPStaging\Backup\Entity\BackupMetadata;

class ArchiverDto
{
    /** @var string */
    private $filePath;

    /** @var string */
    private $indexPath;

    /** @var int */
    private $fileHeaderSizeInBytes = 0;

    /** @var int */
    private $writtenBytesTotal = 0;

    /** @var int */
    private $fileSize;

    /** @var array */
    private $indexPositionCreated = [];

    /** @var BackupMetadata */
    private $backupMetadata;

    /**
     * @param int $bytes
     * @return void
     */
    public function appendWrittenBytes(int $bytes)
    {
        $this->writtenBytesTotal += (int) $bytes;
    }

    /**
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->fileSize <= $this->writtenBytesTotal;
    }

    /**
     * @return void
     */
    public function resetIfFinished()
    {
        if ($this->isFinished()) {
            $this->reset();
        }
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->setFileSize(-1);
        $this->setFilePath('');
        $this->setWrittenBytesTotal(0);
        $this->setIndexPositionCreated(false);
        $this->setFileHeaderSizeInBytes(0);
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return (string)$this->filePath;
    }

    /**
     * @param string $filePath
     * @return void
     */
    public function setFilePath(string $filePath)
    {
        $this->filePath = wp_normalize_path((string)$filePath);
    }

    /**
     * @return string
     */
    public function getIndexPath(): string
    {
        return $this->indexPath;
    }

    /**
     * @param string $indexPath
     * @return void
     */
    public function setIndexPath(string $indexPath)
    {
        $this->indexPath = wp_normalize_path((string)$indexPath);
    }

    /**
     * @return int
     */
    public function getWrittenBytesTotal(): int
    {
        /** @noinspection UnnecessaryCastingInspection */
        return (int) $this->writtenBytesTotal;
    }

    /**
     * @param int $writtenBytesTotal
     * @return void
     */
    public function setWrittenBytesTotal(int $writtenBytesTotal)
    {
        $this->writtenBytesTotal = $writtenBytesTotal;
    }

    /**
     * @return int
     */
    public function getFileSize(): int
    {
        return (int)$this->fileSize;
    }

    /**
     * @param int $fileSize
     * @return void
     */
    public function setFileSize(int $fileSize)
    {
        $this->fileSize = $fileSize;
    }

    /**
     * @return int
     */
    public function getFileHeaderSizeInBytes(): int
    {
        return $this->fileHeaderSizeInBytes;
    }

    /**
     * @param int $fileHeaderSizeInBytes
     * @return void
     */
    public function setFileHeaderSizeInBytes(int $fileHeaderSizeInBytes)
    {
        $this->fileHeaderSizeInBytes = $fileHeaderSizeInBytes;
    }

    /**
     * @param string $category
     * @param int $categoryIndex
     * @return bool
     */
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

    /**
     * @param bool $indexPositionCreated
     * @param string $category
     * @param int $categoryIndex
     * @return void
     */
    public function setIndexPositionCreated(bool $indexPositionCreated, string $category = '', int $categoryIndex = 0)
    {
        if (!isset($this->indexPositionCreated[$category])) {
            $this->indexPositionCreated[$category] = [];
        }

        $this->indexPositionCreated[$category][$categoryIndex] = (bool)$indexPositionCreated;
    }

    /**
     * @return BackupMetadata
     */
    public function getBackupMetadata(): BackupMetadata
    {
        if (!$this->backupMetadata) {
            $this->backupMetadata = new BackupMetadata();
        }

        return $this->backupMetadata;
    }

    /**
     * @param BackupMetadata $backupMetadata
     * @return void
     */
    public function setBackupMetadata(BackupMetadata $backupMetadata)
    {
        $this->backupMetadata = $backupMetadata;
    }
}
