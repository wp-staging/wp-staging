<?php

namespace WPStaging\Backup\Dto\Traits;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\JobDataDto;

/**
 * Used for Remote Upload
 */
trait RemoteUploadTrait
{
    /** @var bool */
    private $isAutomatedBackup = false;

    /** @var float The size of all backup multipart files or single full backup file */
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

    /** @var bool */
    private $isMultipartBackup = false;

    /** @var int */
    private $maxMultipartBackupSize = 2147483647; // 2GB - 1 Byte

    /** @var bool True if this backup should be repeated on a schedule, false if it should run only once. */
    private $repeatBackupOnSchedule;

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
     * @return float
     */
    public function getTotalBackupSize(): float
    {
        return $this->totalBackupSize;
    }

    /**
     * @param float $totalBackupSize
     * @return void
     */
    public function setTotalBackupSize(float $totalBackupSize)
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
     * @param float    $fileSize
     * @param string $fileHash
     * @return void
     */
    public function setUploadedFile(string $uploadedFile, float $fileSize, string $fileHash = '')
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
        if (!is_array($storages) && !empty($storages)) {
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

    /** @return bool */
    public function isUploadToOneDrive(): bool
    {
        return in_array('one-drive', $this->getStorages());
    }

    /**
     * @param bool $isMultipartBackup
     */
    public function setIsMultipartBackup($isMultipartBackup)
    {
        $this->isMultipartBackup = $isMultipartBackup;
    }

    /**
     * @return bool
     */
    public function getIsMultipartBackup(): bool
    {
        // Do not remove this check, otherwise the backup process will fail with an error if multipart backup is enabled in the free version.
        if (!WPStaging::isPro()) {
            return false;
        }

        return Hooks::applyFilters(JobDataDto::FILTER_IS_MULTIPART_BACKUP, $this->isMultipartBackup);
    }

    /**
     * @return int
     */
    public function getMaxMultipartBackupSize()
    {
        return Hooks::applyFilters(JobDataDto::FILTER_MAX_MULTIPART_BACKUP_SIZE, $this->maxMultipartBackupSize);
    }

    /**
     * @param int $maxMultipartBackupSize
     */
    public function setMaxMultipartBackupSize($maxMultipartBackupSize)
    {
        $this->maxMultipartBackupSize = $maxMultipartBackupSize;
    }

    /**
     * @return bool
     */
    public function getRepeatBackupOnSchedule()
    {
        return $this->repeatBackupOnSchedule;
    }

    /**
     * @param bool $repeatBackupOnSchedule
     */
    public function setRepeatBackupOnSchedule($repeatBackupOnSchedule)
    {
        $this->repeatBackupOnSchedule = $repeatBackupOnSchedule;
    }
}
