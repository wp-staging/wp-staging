<?php

//TODO PHP7.x; declare(strict_types=1);
//TODO PHP7.x; type-hints and return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Entity;

use WPStaging\Framework\Entity\AbstractEntity;

class FilesystemSettings extends AbstractEntity
{
    const DEFAULT_FILE_COPY_LIMIT = 50;
    const DEFAULT_MAX_FILE_SIZE = 8; // MB
    const DEAULT_FILE_COPY_BATCH_SIZE = 2;

    /** @var int */
    private $fileCopyLimit;

    // In MB
    /** @var int */
    private $maximumFileSize;

    /** @var int */
    private $fileCopyBatchSize;

    /** @var bool */
    private $checkDirectorySize;

    /**
     * @return int
     */
    public function getFileCopyLimit()
    {
        return $this->fileCopyLimit?: self::DEFAULT_FILE_COPY_LIMIT;
    }

    /**
     * @param int $fileCopyLimit
     */
    public function setFileCopyLimit($fileCopyLimit)
    {
        $this->fileCopyLimit = $fileCopyLimit;
    }

    /**
     * @return int
     */
    public function getMaximumFileSize()
    {
        return $this->maximumFileSize?: self::DEFAULT_MAX_FILE_SIZE;
    }

    /**
     * @param int $maximumFileSize
     */
    public function setMaximumFileSize($maximumFileSize)
    {
        $this->maximumFileSize = $maximumFileSize;
    }

    /**
     * @return int
     */
    public function getFileCopyBatchSize()
    {
        return $this->fileCopyBatchSize?: self::DEAULT_FILE_COPY_BATCH_SIZE;
    }

    /**
     * @param int $fileCopyBatchSize
     */
    public function setFileCopyBatchSize($fileCopyBatchSize)
    {
        $this->fileCopyBatchSize = $fileCopyBatchSize;
    }

    /**
     * @return bool
     */
    public function isCheckDirectorySize()
    {
        return $this->checkDirectorySize;
    }

    /**
     * @param bool $checkDirectorySize
     */
    public function setCheckDirectorySize($checkDirectorySize)
    {
        $this->checkDirectorySize = $checkDirectorySize;
    }
}