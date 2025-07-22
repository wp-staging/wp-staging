<?php

namespace WPStaging\Staging\Dto\Service;

class BigFileDto
{
    /** @var string */
    private $filePath = '';

    /** @var string */
    private $destinationPath = '';

    /** @var string */
    private $indexPath = '';

    /** @var int */
    private $writtenBytesTotal = 0;

    /** @var int */
    private $fileSize = -1;

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
        // File can be empty with 0 size, so we need to set it to -1 to indicate that the file size is unknown
        $this->setFileSize(-1);
        $this->setFilePath('');
        $this->setDestinationPath('');
        $this->setIndexPath('');
        $this->setWrittenBytesTotal(0);
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
        $this->filePath = wp_normalize_path($filePath);
    }

    /**
     * @return string
     */
    public function getDestinationPath(): string
    {
        return $this->destinationPath;
    }

    /**
     * @param string $destinationPath
     * @return void
     */
    public function setDestinationPath(string $destinationPath)
    {
        $this->destinationPath = wp_normalize_path($destinationPath);
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
        $this->indexPath = wp_normalize_path($indexPath);
    }

    /**
     * @return int
     */
    public function getWrittenBytesTotal(): int
    {
        /** @noinspection UnnecessaryCastingInspection */
        return (int)$this->writtenBytesTotal;
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
}
