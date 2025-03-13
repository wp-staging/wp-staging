<?php

namespace WPStaging\Backup\Dto\Task\Restore;

use WPStaging\Backup\Dto\File\ExtractorDto;
use WPStaging\Framework\Job\Dto\AbstractTaskDto;

class ExtractFilesTaskDto extends AbstractTaskDto
{
    /** @var int */
    public $currentIndexOffset;

    /** @var int */
    public $totalFilesExtracted;

    /** @var int */
    public $totalFilesSkipped;

    /** @var int */
    public $extractorFileWrittenBytes;

    /** @var int */
    public $currentHeaderBytesRemoved;

    public function toExtractorDto(): ExtractorDto
    {
        $extractorDto = new ExtractorDto();
        $extractorDto->setCurrentIndexOffset($this->currentIndexOffset ?? 0);
        $extractorDto->setTotalFilesExtracted($this->totalFilesExtracted ?? 0);
        $extractorDto->setTotalFilesSkipped($this->totalFilesSkipped ?? 0);
        $extractorDto->setExtractorFileWrittenBytes($this->extractorFileWrittenBytes ?? 0);
        $extractorDto->setHeaderBytesRemoved($this->currentHeaderBytesRemoved ?? 0);

        return $extractorDto;
    }

    /**
     * @param ExtractorDto $extractorDto
     * @return void
     */
    public function fromExtractorDto(ExtractorDto $extractorDto)
    {
        $this->currentIndexOffset        = $extractorDto->getCurrentIndexOffset();
        $this->totalFilesExtracted       = $extractorDto->getTotalFilesExtracted();
        $this->totalFilesSkipped         = $extractorDto->getTotalFilesSkipped();
        $this->extractorFileWrittenBytes = $extractorDto->getExtractorFileWrittenBytes();
        $this->currentHeaderBytesRemoved = $extractorDto->getHeaderBytesRemoved();
    }
}
