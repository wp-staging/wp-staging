<?php

namespace WPStaging\Backup\Dto\Interfaces;

/**
 * Used for Remote Upload
 */
interface RemoteUploadDtoInterface
{
    public function getIsAutomatedBackup(): bool;

    public function setIsAutomatedBackup(bool $isAutomatedBackup);

    public function getTotalBackupSize(): float;

    public function setTotalBackupSize(float $totalBackupSize);

    public function getFilesToUpload(): array;

    public function setFilesToUpload(array $filesToUpload = []);

    public function getUploadedFiles(): array;

    public function setUploadedFiles(array $uploadedFiles = []);

    public function setUploadedFile(string $uploadedFile, float $fileSize);

    public function getIsOnlyUpload(): bool;

    public function setIsOnlyUpload(bool $isOnlyUpload);

    /**
     * @return array
     */
    public function getRemoteStorageMeta();

    /**
     * @param array|null $remoteStorageMeta
     * @return void
     */
    public function setRemoteStorageMeta($remoteStorageMeta = []);

    public function getStorages(): array;

    /**
     * @param array|string $storages
     * @return void
     */
    public function setStorages($storages = []);

    /**
     * @return int
     */
    public function getStartTime();

    /**
     * @param int $endTime
     * @return void
     */
    public function setStartTime($startTime);

    /**
     * @return int
     */
    public function getEndTime();

    /**
     * @param int $endTime
     * @return void
     */
    public function setEndTime($endTime);

    /**
     * @param bool $isMultipartBackup
     */
    public function setIsMultipartBackup($isMultipartBackup);

    /**
     * @return bool
     */
    public function getIsMultipartBackup();

    /**
     * @return int
     */
    public function getMaxMultipartBackupSize();

    public function setMaxMultipartBackupSize($maxMultipartBackupSize);

    public function getRepeatBackupOnSchedule();
}
