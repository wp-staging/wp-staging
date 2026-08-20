<?php

namespace WPStaging\Backup\Dto\File;

class ExtractorDto
{
 
    protected $indexStartOffset;

 
    protected $currentIndexOffset;

 
    protected $totalFilesExtracted;

 
    protected $totalFilesSkipped;

 
    protected $totalChunks;

 
    protected $extractorFileWrittenBytes;

 
    protected $extractorFileReadBytes;

 
    protected $headerBytesRemoved;

 
    protected $extractorFileBaseBytes;

    public function __construct()
    {
        $this->indexStartOffset          = 0;
        $this->currentIndexOffset        = 0;
        $this->totalFilesExtracted       = 0;
        $this->totalFilesSkipped         = 0;
        $this->totalChunks               = 0;
        $this->extractorFileWrittenBytes = 0;
        $this->extractorFileReadBytes    = 0;
        $this->headerBytesRemoved        = 0;
        $this->extractorFileBaseBytes    = 0;
    }

    public function getIndexStartOffset(): int
    {
        return $this->indexStartOffset;
    }





    public function setIndexStartOffset(int $indexStartOffset)
    {
        $this->indexStartOffset = $indexStartOffset;
    }

    public function getCurrentIndexOffset(): int
    {
        return $this->currentIndexOffset;
    }





    public function setCurrentIndexOffset(int $currentOffset)
    {
        $this->currentIndexOffset = $currentOffset;
    }

    public function getTotalFilesExtracted(): int
    {
        return $this->totalFilesExtracted;
    }





    public function setTotalFilesExtracted(int $filesExtracted)
    {
        $this->totalFilesExtracted = $filesExtracted;
    }

    public function getTotalFilesSkipped(): int
    {
        return $this->totalFilesSkipped;
    }





    public function setTotalFilesSkipped(int $filesSkipped)
    {
        $this->totalFilesSkipped = $filesSkipped;
    }

    public function getTotalChunks(): int
    {
        return $this->totalChunks;
    }





    public function setTotalChunks(int $totalChunks)
    {
        $this->totalChunks = $totalChunks;
    }

    public function getExtractorFileWrittenBytes(): int
    {
        return $this->extractorFileWrittenBytes;
    }





    public function setExtractorFileWrittenBytes(int $extractorFileWrittenBytes)
    {
        $this->extractorFileWrittenBytes = $extractorFileWrittenBytes;
    }

    public function getExtractorFileReadBytes(): int
    {
        return $this->extractorFileReadBytes;
    }





    public function setExtractorFileReadBytes(int $extractorFileReadBytes)
    {
        $this->extractorFileReadBytes = $extractorFileReadBytes;
    }

    public function getHeaderBytesRemoved(): int
    {
        return $this->headerBytesRemoved;
    }





    public function setHeaderBytesRemoved(int $headerBytesRemoved)
    {
        $this->headerBytesRemoved = $headerBytesRemoved;
    }

    public function getExtractorFileBaseBytes(): int
    {
        return $this->extractorFileBaseBytes;
    }





    public function setExtractorFileBaseBytes(int $extractorFileBaseBytes)
    {
        $this->extractorFileBaseBytes = $extractorFileBaseBytes;
    }




    public function incrementTotalFilesExtracted()
    {
        $this->totalFilesExtracted++;
    }




    public function incrementTotalFilesSkipped()
    {
        $this->totalFilesSkipped++;
    }
}
