<?php

namespace WPStaging\Backup\Service;

use RuntimeException;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Framework\Filesystem\FileObject;

class BackupSigner
{
 
    const MAX_SIZE_WRITE_ATTEMPTS = 16;

 
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
            throw new RuntimeException('The backup file is invalid: ' . $backupFilePath . '.');
        }

        $file           = new FileObject($backupFilePath, FileObject::MODE_APPEND_AND_READ);
        $backupMetadata = new BackupMetadata();
        $backupMetadata = $backupMetadata->hydrateByFile($file);

        if ($backupSize !== 0) {
            $this->writeSizesToMetadata($file, $backupMetadata, $backupSize, $partSize);
            $this->jobDataDto->setTotalBackupSize($backupSize);

            return;
        }

        $ownSize = $this->writeUntilTheSizeHolds(function ($size) use ($file, $backupMetadata) {
            return $this->writeSizesToMetadata($file, $backupMetadata, $size, $size);
        }, $file->getSize());

        $this->jobDataDto->setTotalBackupSize($ownSize);
    }




    private function writeSizesToMetadata(FileObject $file, BackupMetadata $backupMetadata, int $backupSize, int $partSize): int
    {
        $backupMetadata->setBackupSize($backupSize);
        $this->signMultipartMetadata($backupMetadata, $partSize);
        $this->backupMetadataEditor->setBackupMetadata($file, $backupMetadata);
        $file->fflush();

        clearstatcache(true, $file->getPathname());

        return (int)filesize($file->getPathname());
    }










    protected function writeUntilTheSizeHolds(callable $writeThenMeasure, int $startingSize): int
    {
        $size = $startingSize;

        for ($attempt = 0; $attempt < static::MAX_SIZE_WRITE_ATTEMPTS; $attempt++) {
            $measured = (int)$writeThenMeasure($size);

            if ($measured === $size) {
                return $size;
            }

            $size = $measured;
        }

        throw new RuntimeException('Cannot determine the size to record in the backup metadata.');
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
}
