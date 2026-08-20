<?php

namespace WPStaging\Staging\Dto\Service;

class BigFileDto
{
 
    private $filePath = '';

 
    private $destinationPath = '';

 
    private $indexPath = '';

 
    private $writtenBytesTotal = 0;

 
    private $fileSize = -1;





    public function appendWrittenBytes(int $bytes)
    {
        $this->writtenBytesTotal += (int) $bytes;
    }




    public function isFinished(): bool
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
 
        $this->setFileSize(-1);
        $this->setFilePath('');
        $this->setDestinationPath('');
        $this->setIndexPath('');
        $this->setWrittenBytesTotal(0);
    }




    public function getFilePath(): string
    {
        return (string)$this->filePath;
    }





    public function setFilePath(string $filePath)
    {
        $this->filePath = wp_normalize_path($filePath);
    }




    public function getDestinationPath(): string
    {
        return $this->destinationPath;
    }





    public function setDestinationPath(string $destinationPath)
    {
        $this->destinationPath = wp_normalize_path($destinationPath);
    }




    public function getIndexPath(): string
    {
        return $this->indexPath;
    }





    public function setIndexPath(string $indexPath)
    {
        $this->indexPath = wp_normalize_path($indexPath);
    }




    public function getWrittenBytesTotal(): int
    {
 
        return (int)$this->writtenBytesTotal;
    }





    public function setWrittenBytesTotal(int $writtenBytesTotal)
    {
        $this->writtenBytesTotal = $writtenBytesTotal;
    }




    public function getFileSize(): int
    {
        return (int)$this->fileSize;
    }





    public function setFileSize(int $fileSize)
    {
        $this->fileSize = $fileSize;
    }
}
