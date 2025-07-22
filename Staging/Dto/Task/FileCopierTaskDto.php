<?php

namespace WPStaging\Staging\Dto\Task;

use WPStaging\Framework\Job\Dto\AbstractTaskDto;
use WPStaging\Staging\Dto\Service\BigFileDto;

class FileCopierTaskDto extends AbstractTaskDto
{
    /** @var string */
    public $filePath = '';

    /** @var string */
    public $destinationPath = '';

    /** @var string */
    public $indexPath = '';

    /** @var int */
    public $writtenBytesTotal = 0;

    /** @var int */
    public $fileSize = 0;

    public function getBigFileDto(): BigFileDto
    {
        $bigFileDto = new BigFileDto();
        $bigFileDto->setFilePath($this->filePath);
        $bigFileDto->setDestinationPath($this->destinationPath);
        $bigFileDto->setIndexPath($this->indexPath);
        $bigFileDto->setWrittenBytesTotal($this->writtenBytesTotal);
        $bigFileDto->setFileSize($this->fileSize);

        return $bigFileDto;
    }

    /**
     * Set properties from a BigFileDto object or reset if null.
     *
     * @param BigFileDto|null $bigFileDto The DTO to set from, or null to reset.
     * @return void
     */
    public function setBigFileDto($bigFileDto)
    {
        if ($bigFileDto === null) {
            $this->reset();
            return;
        }

        $this->filePath          = $bigFileDto->getFilePath();
        $this->destinationPath   = $bigFileDto->getDestinationPath();
        $this->indexPath         = $bigFileDto->getIndexPath();
        $this->writtenBytesTotal = $bigFileDto->getWrittenBytesTotal();
        $this->fileSize          = $bigFileDto->getFileSize();
    }

    public function reset()
    {
        $this->filePath          = '';
        $this->destinationPath   = '';
        $this->indexPath         = '';
        $this->writtenBytesTotal = 0;
        $this->fileSize          = 0;
    }
}
