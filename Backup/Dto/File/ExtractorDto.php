<?php

namespace WPStaging\Backup\Dto\File;

class ExtractorDto
{
    /** @var int */
    protected $indexStartOffset;

    /** @var int */
    protected $currentIndexOffset;

    /** @var int */
    protected $totalFilesExtracted;

    /** @var int */
    protected $totalFilesSkipped;

    /** @var int */
    protected $totalChunks;

    /** @var int */
    protected $extractorFileWrittenBytes;

    /** @var int */
    protected $headerBytesRemoved;

    public function __construct()
    {
        $this->indexStartOffset          = 0;
        $this->currentIndexOffset        = 0;
        $this->totalFilesExtracted       = 0;
        $this->totalFilesSkipped         = 0;
        $this->totalChunks               = 0;
        $this->extractorFileWrittenBytes = 0;
        $this->headerBytesRemoved        = 0;
    }

    public function getIndexStartOffset(): int
    {
        return $this->indexStartOffset;
    }

    /**
     * @param int $indexStartOffset
     * @return void
     */
    public function setIndexStartOffset(int $indexStartOffset)
    {
        $this->indexStartOffset = $indexStartOffset;
    }

    public function getCurrentIndexOffset(): int
    {
        return $this->currentIndexOffset;
    }

    /**
     * @param int $currentOffset
     * @return void
     */
    public function setCurrentIndexOffset(int $currentOffset)
    {
        $this->currentIndexOffset = $currentOffset;
    }

    public function getTotalFilesExtracted(): int
    {
        return $this->totalFilesExtracted;
    }

    /**
     * @param int $filesExtracted
     * @return void
     */
    public function setTotalFilesExtracted(int $filesExtracted)
    {
        $this->totalFilesExtracted = $filesExtracted;
    }

    public function getTotalFilesSkipped(): int
    {
        return $this->totalFilesSkipped;
    }

    /**
     * @param int $filesSkipped
     * @return void
     */
    public function setTotalFilesSkipped(int $filesSkipped)
    {
        $this->totalFilesSkipped = $filesSkipped;
    }

    public function getTotalChunks(): int
    {
        return $this->totalChunks;
    }

    /**
     * @param int $totalChunks
     * @return void
     */
    public function setTotalChunks(int $totalChunks)
    {
        $this->totalChunks = $totalChunks;
    }

    public function getExtractorFileWrittenBytes(): int
    {
        return $this->extractorFileWrittenBytes;
    }

    /**
     * @param int $extractorFileWrittenBytes
     * @return void
     */
    public function setExtractorFileWrittenBytes(int $extractorFileWrittenBytes)
    {
        $this->extractorFileWrittenBytes = $extractorFileWrittenBytes;
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

    /**
     * @return void
     */
    public function incrementTotalFilesExtracted()
    {
        $this->totalFilesExtracted++;
    }

    /**
     * @return void
     */
    public function incrementTotalFilesSkipped()
    {
        $this->totalFilesSkipped++;
    }
}
