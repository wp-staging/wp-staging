<?php

namespace WPStaging\Backup\Service;

use RuntimeException;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Framework\Filesystem\FileObject;

class BackupSigner
{
    /** @var BackupMetadataEditor */
    protected $backupMetadataEditor;

    /** @var JobBackupDataDto */
    protected $jobDataDto;

    /**
     * @param BackupMetadataEditor $backupMetadataEditor
     */
    public function __construct(BackupMetadataEditor $backupMetadataEditor)
    {
        $this->backupMetadataEditor = $backupMetadataEditor;
    }

    /**
     * @param JobBackupDataDto $jobDataDto
     * @return void
     */
    public function setup(JobBackupDataDto $jobDataDto)
    {
        $this->jobDataDto = $jobDataDto;
    }

    /**
     * @param string $backupFilePath
     * @return void
     */
    public function signBackup(string $backupFilePath)
    {
        $this->signBackupFile($backupFilePath);
    }

    /**
     * @param string $backupFilePath
     * @return void
     */
    public function validateSignedBackup(string $backupFilePath)
    {
        $this->validateBackupFile($backupFilePath);
    }

    /**
     * Signing the Backup aims to give it an identifier that can be checked for its consistency.
     *
     * Currently, we use the size of the file. We can use this information later, during Restore or Upload,
     * to check if the Backup file we have is complete and matches the expected one.
     *
     * @param string $backupFilePath
     * @param int $backupSize
     * @param int $partSize
     * @return void
     */
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

    /**
     * @param string $backupFilePath
     * @param integer $backupSize
     * @param integer $partSize
     * @return void
     */
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

    /**
     * @param BackupMetadata $backupMetadata
     * @param integer $partSize
     * @return void
     */
    protected function signMultipartMetadata(BackupMetadata $backupMetadata, int $partSize)
    {
        // no-op
    }

    /**
     * @param BackupMetadata $backupMetadata
     * @param integer $partSize
     * @return void
     */
    protected function validateMultipartMetadata(BackupMetadata $backupMetadata, int $partSize)
    {
        // no-op
    }

    /**
     * Subtract four doublequotes from the backup Size and add the byte length of $backupSize
     *
     * Example:
     *
     * Before: "backupSize": ""
     * After:  "backupSize": 123456
     *
     * @param int $backupSize
     * @return int
     */
    private function reCalcBackupSize(int $backupSize = 0): int
    {
        return $backupSize - 2 + strlen($backupSize);
    }
}
