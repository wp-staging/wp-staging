<?php






namespace WPStaging\Staging\Dto\Task\Response;

use WPStaging\Framework\Job\Dto\TaskResponseDto;




class FileCopierResponseDto extends TaskResponseDto
{
 
    private $filesTotal = 0;

 
    private $filesCopied = 0;

 
    private $filesystemSize = 0;

 
    private $copiedBytes = 0;

    public function setFilesTotal(int $filesTotal)
    {
        $this->filesTotal = $filesTotal;
    }

    public function setFilesCopied(int $filesCopied)
    {
        $this->filesCopied = $filesCopied;
    }

    public function setFilesystemSize(int $filesystemSize)
    {
        $this->filesystemSize = $filesystemSize;
    }

    public function setCopiedBytes(int $copiedBytes)
    {
        $this->copiedBytes = $copiedBytes;
    }
}
