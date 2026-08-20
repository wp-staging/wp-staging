<?php

namespace WPStaging\Backup\Dto\Traits;

use WPStaging\Backup\Storage\Providers;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\JobDataDto;




trait RemoteUploadTrait
{
 
    private $isAutomatedBackup = false;

 
    private $totalBackupSize = 0;

 
    private $filesToUpload = [];

 
    private $uploadedFiles = [];

 
    private $storages;






    private $remoteStorageMeta;






    private $isOnlyUpload = false;

 
    private $isMultipartBackup = false;

 
    private $maxMultipartBackupSize = 2147483647; 

 
    private $repeatBackupOnSchedule;




    public function getIsAutomatedBackup(): bool
    {
        return $this->isAutomatedBackup;
    }







    public function setIsAutomatedBackup(bool $isAutomatedBackup)
    {
        $this->isAutomatedBackup = $isAutomatedBackup;
    }




    public function getTotalBackupSize(): float
    {
        return $this->totalBackupSize;
    }





    public function setTotalBackupSize(float $totalBackupSize)
    {
        $this->totalBackupSize = $totalBackupSize;
    }




    public function getFilesToUpload(): array
    {
        return $this->filesToUpload;
    }





    public function setFilesToUpload(array $filesToUpload = [])
    {
        $this->filesToUpload = $filesToUpload;
    }




    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }





    public function setUploadedFiles(array $uploadedFiles = [])
    {
        $this->uploadedFiles = $uploadedFiles;
    }







    public function setUploadedFile(string $uploadedFile, float $fileSize, string $fileHash = '')
    {
        $this->uploadedFiles[$uploadedFile] = [
            'size' => $fileSize,
            'hash' => $fileHash,
        ];
    }




    public function getIsOnlyUpload(): bool
    {
        return $this->isOnlyUpload;
    }





    public function setIsOnlyUpload(bool $isOnlyUpload)
    {
        $this->isOnlyUpload = $isOnlyUpload;
    }




    public function getStorages(): array
    {
        return $this->storages;
    }




    public function setStorages($storages = [])
    {
        if (!is_array($storages) && !empty($storages)) {
            $storages = json_decode($storages, true);
        }

        $this->storages = $storages;
    }




    public function getRemoteStorageMeta()
    {
        return $this->remoteStorageMeta;
    }





    public function setRemoteStorageMeta($remoteStorageMeta = [])
    {
        $this->remoteStorageMeta = $remoteStorageMeta;
    }

 
    public function isLocalBackup(): bool
    {
        return in_array('localStorage', $this->getStorages());
    }

 
    public function isUploadToGoogleDrive(): bool
    {
        return in_array(Providers::IDENTIFIER_GOOGLE_DRIVE, $this->getStorages()) || in_array('googleDrive', $this->getStorages());
    }

 
    public function isUploadToAmazonS3(): bool
    {
        return in_array(Providers::IDENTIFIER_AMAZON_S3, $this->getStorages()) || in_array('amazonS3', $this->getStorages());
    }

 
    public function isUploadToSftp(): bool
    {
        return in_array(Providers::IDENTIFIER_SFTP, $this->getStorages());
    }

 
    public function isUploadToWasabi(): bool
    {
        return in_array(Providers::IDENTIFIER_WASABI_S3, $this->getStorages());
    }

 
    public function isUploadToDigitalOceanSpaces(): bool
    {
        return in_array(Providers::IDENTIFIER_DIGITALOCEAN_SPACES, $this->getStorages());
    }

 
    public function isUploadToGenericS3(): bool
    {
        return in_array(Providers::IDENTIFIER_GENERIC_S3, $this->getStorages());
    }

 
    public function isUploadToDropbox(): bool
    {
        return in_array(Providers::IDENTIFIER_DROPBOX, $this->getStorages());
    }

 
    public function isUploadToOneDrive(): bool
    {
        return in_array(Providers::IDENTIFIER_ONE_DRIVE, $this->getStorages());
    }

 
    public function isUploadToPCloud(): bool
    {
        return in_array(Providers::IDENTIFIER_PCLOUD, $this->getStorages());
    }




    public function setIsMultipartBackup($isMultipartBackup)
    {
        $this->isMultipartBackup = $isMultipartBackup;
    }




    public function getIsMultipartBackup(): bool
    {
 
        if (!WPStaging::isPro()) {
            return false;
        }

        return Hooks::applyFilters(JobDataDto::FILTER_IS_MULTIPART_BACKUP, $this->isMultipartBackup);
    }




    public function getMaxMultipartBackupSize()
    {
        return Hooks::applyFilters(JobDataDto::FILTER_MAX_MULTIPART_BACKUP_SIZE, $this->maxMultipartBackupSize);
    }




    public function setMaxMultipartBackupSize($maxMultipartBackupSize)
    {
        $this->maxMultipartBackupSize = $maxMultipartBackupSize;
    }




    public function getRepeatBackupOnSchedule()
    {
        return $this->repeatBackupOnSchedule;
    }




    public function setRepeatBackupOnSchedule($repeatBackupOnSchedule)
    {
        $this->repeatBackupOnSchedule = $repeatBackupOnSchedule;
    }
}
