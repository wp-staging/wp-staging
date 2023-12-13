<?php

namespace WPStaging\Backup\Dto\Interfaces;

/**
 * Used for Remote Upload
 */
interface RemoteUploadDtoInterface
{
    public function getIsAutomatedBackup(): bool;

    public function setIsAutomatedBackup(bool $isAutomatedBackup);

    public function getTotalBackupSize(): int;

    public function setTotalBackupSize(int $totalBackupSize);

    public function getFilesToUpload(): array;

    public function setFilesToUpload(array $filesToUpload = []);

    public function getUploadedFiles(): array;

    public function setUploadedFiles(array $uploadedFiles = []);

    public function setUploadedFile(string $uploadedFile, int $fileSize);

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
}
