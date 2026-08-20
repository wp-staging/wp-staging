<?php

namespace WPStaging\Backup\Dto\Task\Restore;

use WPStaging\Backup\Dto\File\ExtractorDto;
use WPStaging\Framework\Job\Dto\AbstractTaskDto;

class ExtractFilesTaskDto extends AbstractTaskDto
{
 
    public $currentIndexOffset;

 
    public $totalFilesExtracted;

 
    public $totalFilesSkipped;

 
    public $extractorFileWrittenBytes;

 
    public $extractorFileReadBytes;

 
    public $currentHeaderBytesRemoved;

 
    public $extractorFileBaseBytes;

    public function toExtractorDto(): ExtractorDto
    {
        $extractorDto = new ExtractorDto();
        $extractorDto->setCurrentIndexOffset($this->currentIndexOffset ?? 0);
        $extractorDto->setTotalFilesExtracted($this->totalFilesExtracted ?? 0);
        $extractorDto->setTotalFilesSkipped($this->totalFilesSkipped ?? 0);
        $extractorDto->setExtractorFileWrittenBytes($this->extractorFileWrittenBytes ?? 0);
        $extractorDto->setExtractorFileReadBytes($this->extractorFileReadBytes ?? 0);
        $extractorDto->setHeaderBytesRemoved($this->currentHeaderBytesRemoved ?? 0);
        $extractorDto->setExtractorFileBaseBytes($this->extractorFileBaseBytes ?? 0);

        return $extractorDto;
    }





    public function fromExtractorDto(ExtractorDto $extractorDto)
    {
        $this->currentIndexOffset        = $extractorDto->getCurrentIndexOffset();
        $this->totalFilesExtracted       = $extractorDto->getTotalFilesExtracted();
        $this->totalFilesSkipped         = $extractorDto->getTotalFilesSkipped();
        $this->extractorFileWrittenBytes = $extractorDto->getExtractorFileWrittenBytes();
        $this->extractorFileReadBytes    = $extractorDto->getExtractorFileReadBytes();
        $this->currentHeaderBytesRemoved = $extractorDto->getHeaderBytesRemoved();
        $this->extractorFileBaseBytes    = $extractorDto->getExtractorFileBaseBytes();
    }
}
