<?php






namespace WPStaging\Backup\Dto\Task\Backup\Response;

use WPStaging\Framework\Job\Dto\TaskResponseDto;

class FinalizeBackupResponseDto extends TaskResponseDto
{




    private $backupMd5;

 
    private $backupSize = 0;

 
    private $isLocalBackup = true;

 
    private $isMultipartBackup = false;

 
    private $isGlitchInBackup = false;

 
    private $glitchReason = '';

 
    private $isBeforePush = false;




    public function setBackupMd5($backupMd5)
    {
        $this->backupMd5 = $backupMd5;
    }






    public function getBackupMd5()
    {
        return $this->backupMd5;
    }




    public function setBackupSize($backupSize)
    {
        $this->backupSize = (int)$backupSize;
    }

    public function getBackupSize(): int
    {
        return $this->backupSize;
    }




    public function setIsLocalBackup($isLocalBackup)
    {
        $this->isLocalBackup = $isLocalBackup;
    }




    public function getIsLocalBackup()
    {
        return $this->isLocalBackup;
    }




    public function setIsMultipartBackup($isMultipartBackup)
    {
        $this->isMultipartBackup = $isMultipartBackup;
    }




    public function getIsMultipartBackup()
    {
        return $this->isMultipartBackup;
    }





    public function setIsGlitchInBackup(bool $isGlitchInBackup)
    {
        $this->isGlitchInBackup = $isGlitchInBackup;
    }

    public function getIsGlitchInBackup(): bool
    {
        return $this->isGlitchInBackup;
    }





    public function setGlitchReason(string $glitchReason)
    {
        $this->glitchReason = $glitchReason;
    }

    public function getGlitchReason(): string
    {
        return $this->glitchReason;
    }





    public function setIsBeforePush(bool $isBeforePush)
    {
        $this->isBeforePush = $isBeforePush;
    }

    public function getIsBeforePush(): bool
    {
        return $this->isBeforePush;
    }
}
