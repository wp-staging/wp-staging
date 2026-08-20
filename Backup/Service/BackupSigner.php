<?php

namespace WPStaging\Backup\Service;

use RuntimeException;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Framework\Filesystem\FileObject;

class BackupSigner
{
 
    protected $backupMetadataEditor;

 
    protected $jobDataDto;




    public function __construct(BackupMetadataEditor $backupMetadataEditor)
    {
        $this->backupMetadataEditor = $backupMetadataEditor;
    }





    public function setup(JobBackupDataDto $jobDataDto)
    {
        $this->jobDataDto = $jobDataDto;
    }





    public function signBackup(string $backupFilePath)
    {
        $this->signBackupFile($backupFilePath);
    }





    public function validateSignedBackup(string $backupFilePath)
    {
        $this->validateBackupFile($backupFilePath);
    }












    protected function signBackupFile(string $backupFilePath, int $backupSize = 0, int $partSize = 0)
    {
        clearstatcache();
        if (!is_file($backupFilePath)) {
            throw new \RuntimeException('The backup file is invalid: ' . $backupFilePath . '.');
        }

        $file           = new FileObject($backupFilePath, FileObject::MODE_APPEND_AND_READ);
        $backupMetadata = new BackupMetadata();
        $backupMetadata = $backupMetadata->hydrateByFile($file);

        if ($backupSize === 0) {
            $backupSize = $file->getSize();

            $backupSize = $this->reCalcBackupSize($backupSize);
        }

        $this->jobDataDto->setTotalBackupSize($backupSize);
        $backupMetadata->setBackupSize($backupSize);
        $this->signMultiPartMetadata($backupMetadata, $partSize);
        $this->backupMetadataEditor->setBackupMetadata($file, $backupMetadata);
    }







    protected function validateBackupFile(string $backupFilePath, int $backupSize = 0, int $partSize = 0)
    {
        clearstatcache();
        if (!is_file($backupFilePath)) {
            throw new RuntimeException('The backup file does not exist: ' . $backupFilePath);
        }

        $file = new FileObject($backupFilePath);

        $backupMetadata = new BackupMetadata();
        $backupMetadata = $backupMetadata->hydrateByFile($file);

        if ($backupMetadata->getName() !== $this->jobDataDto->getName()) {
            throw new RuntimeException('Unexpected Name in Metadata.');
        }

        if ($backupSize === 0) {
            $backupSize = $file->getSize();
        }

        if ($backupMetadata->getBackupSize() !== $backupSize) {
            throw new RuntimeException(sprintf('Unexpected Backup Size in Metadata. Size in Metadata %s, Size in File %s', $backupMetadata->getBackupSize(), $backupSize));
        }

        $this->validateMultipartMetadata($backupMetadata, $partSize);
    }






    protected function signMultipartMetadata(BackupMetadata $backupMetadata, int $partSize)
    {
 
    }






    protected function validateMultipartMetadata(BackupMetadata $backupMetadata, int $partSize)
    {
 
    }












    private function reCalcBackupSize(int $backupSize = 0): int
    {
        return $backupSize - 2 + strlen((string)$backupSize);
    }
}
