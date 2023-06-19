<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use Exception;
use RuntimeException;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\BackupValidator;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupMetadataEditor;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

use function WPStaging\functions\debug_log;

class ValidateBackupTask extends BackupTask
{
    /** @var PathIdentifier */
    protected $pathIdentifier;

    /** @var BackupMetadataEditor */
    protected $backupMetadataEditor;

    /** @var BackupValidator */
    protected $backupValidator;

    /** @var array */
    protected $backupParts = [];

    /** @var bool */
    protected $isMultipartBackup = false;

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, PathIdentifier $pathIdentifier, BackupMetadataEditor $backupMetadataEditor, BackupValidator $backupValidator)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->pathIdentifier       = $pathIdentifier;
        $this->backupMetadataEditor = $backupMetadataEditor;
        $this->backupValidator = $backupValidator;
    }

    public static function getTaskName()
    {
        return 'backup_validate';
    }

    public static function getTaskTitle()
    {
        return 'Validating Backup';
    }

    public function execute()
    {
        $backupFilePath          = $this->jobDataDto->getBackupFilePath();
        $this->isMultipartBackup = $this->jobDataDto->getIsMultipartBackup();

        // Store the "Size" of the Backup in the metadata, which is something we can only do after the backup is final.
        try {
            if ($this->isMultipartBackup) {
                $this->signMultipartBackup();
            } else {
                $this->signBackup($backupFilePath);
            }
        } catch (Exception $e) {
            $backupType = $this->isMultipartBackup ? 'multipart' : '';
            $this->logger->critical(sprintf(esc_html__('The %s backup file could not be signed for consistency. Error: ' . $e->getMessage(), 'wp-staging'), $backupType));

            return $this->generateResponse();
        }

        // Validate the Single Backup File
        if (!$this->isMultipartBackup && $backupFilePath) {
            try {
                $this->validateBackup($backupFilePath);
            } catch (Exception $e) {
                $this->logger->critical(esc_html__('The backup seems to be invalid: ' . $e->getMessage(), 'wp-staging'));

                return $this->generateResponse();
            }
        }

        if ($this->isMultipartBackup) {
            try {
                $this->validateMultipartBackup();
                $this->logger->info('The multipart backup parts are validated successfully.');
            } catch (Exception $e) {
                $this->logger->critical(esc_html__('The multipart backup seems to be invalid: ' . $e->getMessage(), 'wp-staging'));

                return $this->generateResponse();
            }
        }

        $this->stepsDto->finish();

        return $this->generateResponse(false);
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
     */
    protected function signBackup($backupFilePath, $backupSize = 0, $partSize = 0)
    {
        clearstatcache();
        if (!is_file($backupFilePath)) {
            throw new \RuntimeException('The backup file is invalid: ' . $backupFilePath . '.');
        }

        $file           = new FileObject($backupFilePath, FileObject::MODE_APPEND_AND_READ);
        $backupMetadata = new BackupMetadata();
        $backupMetadata->hydrate($file->readBackupMetadata());

        if ($backupSize === 0) {
            $backupSize = $file->getSize();

            $backupSize = $this->reCalcBackupSize($backupSize);
        }

        $this->jobDataDto->setTotalBackupSize($backupSize);
        $backupMetadata->setBackupSize($backupSize);
        if ($this->isMultipartBackup) {
            $multipartMetadata = $backupMetadata->getMultipartMetadata();
            $multipartMetadata->setPartSize($partSize);
            $backupMetadata->setMultipartMetadata($multipartMetadata);
        }

        $this->backupMetadataEditor->setBackupMetadata($file, $backupMetadata);
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
    private function reCalcBackupSize($backupSize = 0)
    {
        return $backupSize - 2 + strlen($backupSize);
    }

    protected function signMultipartBackup()
    {
        $backupsDirectory = '';
        if ($this->jobDataDto->isLocalBackup()) {
            $backupsDirectory = WPStaging::make(BackupsFinder::class)->getBackupsDirectory();
        } else {
            $backupsDirectory = WPStaging::make(Directory::class)->getCacheDirectory();
        }

        $backupSize        = 0;
        $this->backupParts = [];

        foreach ($this->jobDataDto->getMultipartFilesInfo() as $multipartFileInfo) {
            $backupPart          = $backupsDirectory . $multipartFileInfo['destination'];
            $partSize            = filesize($backupPart);
            $partSize            = $partSize - 2 + strlen($partSize);
            $backupSize          += $partSize;
            $this->backupParts[] = [
                'path' => $backupPart,
                'size' => $partSize
            ];
        }

        $incrementSizePerPart = strlen($backupSize) - 2;
        $backupSize           = $backupSize + (count($this->backupParts) * $incrementSizePerPart);

        foreach ($this->backupParts as $part) {
            $this->signBackup($part['path'], $backupSize, $part['size'] + $incrementSizePerPart);
        }
    }

    /**
     *
     * Check if the backup is valid
     *
     * @param string $backupFilePath
     * @param int $backupSize
     * @param int $partSize
     * @return void
     * @throws \WPStaging\Backup\Exceptions\DiskNotWritableException
     * @throws RuntimeException
     */
    protected function validateBackup($backupFilePath, $backupSize = 0, $partSize = 0)
    {
        clearstatcache();
        if (!is_file($backupFilePath)) {
            throw new RuntimeException('The backup file does not exist: ' . $backupFilePath);
        }

        $file = new FileObject($backupFilePath);

        $backupMetadata = new BackupMetadata();
        $backupMetadata->hydrate($file->readBackupMetadata());

        if ($backupMetadata->getName() !== $this->jobDataDto->getName()) {
            throw new RuntimeException('Unexpected Name in Metadata.');
        }

        if ($backupSize === 0) {
            $backupSize = $file->getSize();
        }

        if ($backupMetadata->getBackupSize() !== $backupSize) {
            throw new RuntimeException('Unexpected Backup Size in Metadata.');
        }

        $this->validateFileIndex($file, $backupMetadata, $throwException = false);

        if (!$this->isMultipartBackup) {
            $this->logger->info('The backup was validated successfully.');
            $this->logger->info("################## FINISH ##################");
            return;
        }

        $multipartMetadata = $backupMetadata->getMultipartMetadata();

        if ($multipartMetadata->getPartSize() !== $partSize) {
            throw new RuntimeException('Unexpected multipart size in metadata: Multipart Size: ' . $multipartMetadata->getPartSize() . ' Expected Size: ' . $partSize);
        }
    }

    /**
     * @throws \WPStaging\Backup\Exceptions\DiskNotWritableException
     * @throws RuntimeException
     */
    protected function validateMultipartBackup()
    {
        $backupSize = 0;
        clearstatcache();
        foreach ($this->backupParts as $index => $part) {
            $partSize                          = filesize($part['path']);
            $backupSize                        += $partSize;
            $this->backupParts[$index]['size'] = $partSize;
        }

        foreach ($this->backupParts as $part) {
            $this->validateBackup($part['path'], $backupSize, $part['size']);
        }
    }

    /**
     * @param FileObject $file
     * @param BackupMetadata $backupMetadata
     * @param bool throwException
     * @throws RuntimeException
     */
    protected function validateFileIndex(FileObject $file, BackupMetadata $backupMetadata, $throwException = true)
    {
        if ($this->backupValidator->validateFileIndex($file, $backupMetadata)) {
            return;
        }

        debug_log("ValidateBackupTask:" . $this->backupValidator->getErrorMessage());
        if ($throwException) {
            throw new RuntimeException($this->backupValidator->getErrorMessage());
        }
    }
}
