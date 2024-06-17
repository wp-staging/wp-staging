<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Backup\Dto\Service;

use WPStaging\Backup\Entity\BackupMetadata;

/**
 * @todo Add strict type in a separate PR
 */
class CompressorDto
{
    /** @var string */
    private $filePath;

    /** @var string */
    private $indexPath;

    /** @var int */
    private $fileHeaderBytes = 0;

    /** @var int */
    private $writtenBytesTotal = 0;

    /** @var int */
    private $fileSize;

    /** @var array */
    private $indexPositionCreated = [];

    /** @var BackupMetadata */
    private $backupMetadata;

    public function appendWrittenBytes($bytes)
    {
        $this->writtenBytesTotal += (int) $bytes;
    }

    /**
     * @return bool
     */
    public function isFinished()
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
        $this->setFileSize(null);
        $this->setFilePath(null);
        $this->setWrittenBytesTotal(0);
        $this->setIndexPositionCreated(false);
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     */
    public function setFilePath($filePath)
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
    public function getWrittenBytesTotal()
    {
        /** @noinspection UnnecessaryCastingInspection */
        return (int) $this->writtenBytesTotal;
    }

    /**
     * @param int $writtenBytesTotal
     */
    public function setWrittenBytesTotal($writtenBytesTotal)
    {
        $this->writtenBytesTotal = $writtenBytesTotal;
    }

    /**
     * @return int
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * @param int $fileSize
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;
    }

    /**
     * @return int
     */
    public function getFileHeaderBytes(): int
    {
        return $this->fileHeaderBytes;
    }

    /**
     * @param int $fileHeaderBytes
     * @return void
     */
    public function setFileHeaderBytes(int $fileHeaderBytes)
    {
        $this->fileHeaderBytes = $fileHeaderBytes;
    }

    /**
     * @param string $category
     * @param int $categoryIndex
     * @return bool
     */
    public function isIndexPositionCreated($category = '', $categoryIndex = 0)
    {
        if (!isset($this->indexPositionCreated[$category])) {
            return false;
        }

        return (bool)$this->indexPositionCreated[$category][$categoryIndex];
    }

    /**
     * @param bool $indexPositionCreated
     * @param string $category
     * @param int $categoryIndex
     */
    public function setIndexPositionCreated($indexPositionCreated, $category = '', $categoryIndex = 0)
    {
        if (!isset($this->indexPositionCreated[$category])) {
            $this->indexPositionCreated[$category] = [];
        }

        $this->indexPositionCreated[$category][$categoryIndex] = (bool)$indexPositionCreated;
    }

    /**
     * @return BackupMetadata
     */
    public function getBackupMetadata()
    {
        if (!$this->backupMetadata) {
            $this->backupMetadata = new BackupMetadata();
        }

        return $this->backupMetadata;
    }

    /**
     * @param BackupMetadata $backupMetadata
     */
    public function setBackupMetadata(BackupMetadata $backupMetadata)
    {
        $this->backupMetadata = $backupMetadata;
    }
}
