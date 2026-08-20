<?php

namespace WPStaging\Backup\Dto\Interfaces;




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




    public function getRemoteStorageMeta();





    public function setRemoteStorageMeta($remoteStorageMeta = []);

    public function getStorages(): array;





    public function setStorages($storages = []);




    public function getStartTime();





    public function setStartTime($startTime);




    public function getEndTime();





    public function setEndTime($endTime);




    public function setIsMultipartBackup($isMultipartBackup);




    public function getIsMultipartBackup();




    public function getMaxMultipartBackupSize();

    public function setMaxMultipartBackupSize($maxMultipartBackupSize);

    public function getRepeatBackupOnSchedule();
}
