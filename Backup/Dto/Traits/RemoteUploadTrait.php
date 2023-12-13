<?php

namespace WPStaging\Backup\Dto\Traits;

/**
 * Used for Remote Upload
 */
trait RemoteUploadTrait
{
    /** @var bool */
    private $isAutomatedBackup = false;

    /** @var int The size of all backup multipart files or single full backup file */
    private $totalBackupSize = 0;

    /** @var array */
    private $filesToUpload = [];

    /** @var array */
    private $uploadedFiles = [];

    /** @var array Selected storages for backup. */
    private $storages;

    /**
     * @var array The meta data used by Remote Storages to help uploading.
     * Stores ResumeURI for Google Drive
     * Stores UploadId and UploadedParts Meta for Amazon S3
     */
    private $remoteStorageMeta;

    /**
     * True for stand alone upload job action.
     * Always false when creating backups no matter if the backup is uploaded after that or not.
     * @var bool
     */
    private $isOnlyUpload = false;

    /**
     * @return bool
     */
    public function getIsAutomatedBackup(): bool
    {
        return $this->isAutomatedBackup;
    }

    /**
     * Hydrated dynamically.
     *
     * @param bool $isAutomatedBackup
     * @return void
     */
    public function setIsAutomatedBackup(bool $isAutomatedBackup)
    {
        $this->isAutomatedBackup = $isAutomatedBackup;
    }

    /**
     * @return int
     */
    public function getTotalBackupSize(): int
    {
        return $this->totalBackupSize;
    }

    /**
     * @param int $totalBackupSize
     * @return void
     */
    public function setTotalBackupSize(int $totalBackupSize)
    {
        $this->totalBackupSize = $totalBackupSize;
    }

    /**
     * @return array
     */
    public function getFilesToUpload(): array
    {
        return $this->filesToUpload;
    }

    /**
     * @param array $filesToUpload
     * @return void
     */
    public function setFilesToUpload(array $filesToUpload = [])
    {
        $this->filesToUpload = $filesToUpload;
    }

    /**
     * @return array
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * @param array $uploadedFiles
     * @return void
     */
    public function setUploadedFiles(array $uploadedFiles = [])
    {
        $this->uploadedFiles = $uploadedFiles;
    }

    /**
     * @param string $uploadedFile
     * @param int    $fileSize
     * @param string $fileHash
     * @return void
     */
    public function setUploadedFile(string $uploadedFile, int $fileSize, string $fileHash = '')
    {
        $this->uploadedFiles[$uploadedFile] = [
            'size' => $fileSize,
            'hash' => $fileHash,
        ];
    }

    /**
     * @return bool
     */
    public function getIsOnlyUpload(): bool
    {
        return $this->isOnlyUpload;
    }

    /**
     * @param bool isOnlyUpload
     * @return void
     */
    public function setIsOnlyUpload(bool $isOnlyUpload)
    {
        $this->isOnlyUpload = $isOnlyUpload;
    }

    /**
     * @return array
     */
    public function getStorages(): array
    {
        return $this->storages;
    }

    /**
     * @param array|string $storages
     */
    public function setStorages($storages = [])
    {
        if (!is_array($storages)) {
            $storages = json_decode($storages, true);
        }

        $this->storages = $storages;
    }

    /**
     * @return array
     */
    public function getRemoteStorageMeta()
    {
        return $this->remoteStorageMeta;
    }

    /**
     * @param array $remoteStorageMeta
     * @return void
     */
    public function setRemoteStorageMeta($remoteStorageMeta = [])
    {
        $this->remoteStorageMeta = $remoteStorageMeta;
    }

    /** @return bool */
    public function isLocalBackup(): bool
    {
        return in_array('localStorage', $this->getStorages());
    }

    /** @return bool */
    public function isUploadToGoogleDrive(): bool
    {
        return in_array('googleDrive', $this->getStorages());
    }

    /** @return bool */
    public function isUploadToAmazonS3(): bool
    {
        return in_array('amazonS3', $this->getStorages());
    }

    /** @return bool */
    public function isUploadToSftp(): bool
    {
        return in_array('sftp', $this->getStorages());
    }

    /** @return bool */
    public function isUploadToWasabi(): bool
    {
        return in_array('wasabi-s3', $this->getStorages());
    }

    /** @return bool */
    public function isUploadToDigitalOceanSpaces(): bool
    {
        return in_array('digitalocean-spaces', $this->getStorages());
    }

    /** @return bool */
    public function isUploadToGenericS3(): bool
    {
        return in_array('generic-s3', $this->getStorages());
    }

    /** @return bool */
    public function isUploadToDropbox(): bool
    {
        return in_array('dropbox', $this->getStorages());
    }
}
