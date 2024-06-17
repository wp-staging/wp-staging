<?php

namespace WPStaging\Backup\Dto\Task\Restore;

use WPStaging\Backup\Dto\AbstractTaskDto;
use WPStaging\Backup\Dto\File\ExtractorDto;

class ExtractFilesTaskDto extends AbstractTaskDto
{
    /** @var int */
    public $currentIndexOffset;

    /** @var int */
    public $totalFilesExtracted;

    /** @var int */
    public $extractorFileWrittenBytes;

    public function toExtractorDto(): ExtractorDto
    {
        $extractorDto = new ExtractorDto();
        $extractorDto->setCurrentIndexOffset($this->currentIndexOffset ?? 0);
        $extractorDto->setTotalFilesExtracted($this->totalFilesExtracted ?? 0);
        $extractorDto->setExtractorFileWrittenBytes($this->extractorFileWrittenBytes ?? 0);

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
        $this->extractorFileWrittenBytes = $extractorDto->getExtractorFileWrittenBytes();
    }
}
