<?php

namespace WPStaging\Backup\Entity;

use WPStaging\Backup\Interfaces\IndexLineInterface;
use WPStaging\Framework\Filesystem\PathIdentifier;

/**
 * Class FileBeingExtracted
 *
 * This is a OOP representation of a file being extracted.
 * @todo Add strict types in separate PR
 *
 * @see     \WPStaging\Backup\Service\Extractor
 *
 * @package WPStaging\Backup\Entity\Service
 */
class FileBeingExtracted
{
    /**
     * A string that uniquely identifies the file being extracted, e.g. wpstg_c_debug.log, wpstg_p_yoast.json, etc.
     * @var string
     */

    private $identifiablePath;

    /** @var string */
    private $relativePath;

    /** @var int */
    private $start;

    /** @var int */
    private $totalBytes;

    /** @var int */
    private $writtenBytes = 0;

    /** @var int */
    private $readBytes = 0;

    /** @var string */
    protected $extractFolder;

    /** @var PathIdentifier */
    protected $pathIdentifier;

    /** @var int 1 if yes, 0 if no. */
    protected $isCompressed;

    /** @var int */
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

    /**
     * Absolute destination path of the current file to extract.
     * @todo rename this method to getExtractFilePath() or similar.
     * @return string
     */
    public function getBackupPath()
    {
        return $this->extractFolder . $this->relativePath;
    }

    /**
     * Find the maximum remaining number of bytes that will be read from the file.
     * This is limited to 512 KB to avoid reading too much data at once to prevent memory issues.
     *
     * @return int
     */
    public function findReadTo()
    {
        $maxLengthToRead = 512 * KB_IN_BYTES;

        $remainingBytesToRead = $this->totalBytes - $this->readBytes;

        return max(0, min($remainingBytesToRead, $maxLengthToRead));
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->identifiablePath;
    }

    /**
     * @return string
     */
    public function getRelativePath()
    {
        return $this->relativePath;
    }

    /**
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return int
     */
    public function getTotalBytes()
    {
        return $this->totalBytes;
    }

    /**
     * @return int
     */
    public function getWrittenBytes()
    {
        return $this->writtenBytes;
    }

    /**
     * @param int $writtenBytes
     */
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

    /**
     * @param int $readBytes
     * @return void
     */
    public function setReadBytes(int $readBytes)
    {
        $this->readBytes = $readBytes;
    }

    /**
     * @param int $readBytes
     * @return void
     */
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

    /**
     * @return bool|int
     */
    public function getIsCompressed()
    {
        return $this->isCompressed;
    }

    public function getCurrentOffset(): int
    {
        return $this->start + $this->readBytes;
    }

    /**
     * @param int $headerBytesRemoved
     * @return void
     */
    public function addHeaderBytesRemoved(int $headerBytesRemoved)
    {
        $this->headerBytesRemoved += $headerBytesRemoved;
    }

    public function getHeaderBytesRemoved(): int
    {
        return $this->headerBytesRemoved;
    }

    /**
     * @param int $headerBytesRemoved
     * @return void
     */
    public function setHeaderBytesRemoved(int $headerBytesRemoved)
    {
        $this->headerBytesRemoved = $headerBytesRemoved;
    }

    public function areHeaderBytesRemoved(): bool
    {
        return $this->headerBytesRemoved > 0;
    }
}
