<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use Exception;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Backup\Service\BackupSigner;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class SignBackupTask extends BackupTask
{
    /** @var BackupSigner */
    protected $backupSigner;

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, BackupSigner $backupSigner)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->backupSigner = $backupSigner;
    }

    public static function getTaskName(): string
    {
        return 'backup_signer';
    }

    public static function getTaskTitle(): string
    {
        return 'Signing Backup';
    }

    public function execute(): TaskResponseDto
    {
        $this->backupSigner->setup($this->jobDataDto);
        $backupFilePath = $this->jobDataDto->getBackupFilePath();

        // Store the "Size" of the Backup in the metadata, which is something we can only do after the backup is final.
        try {
            $this->backupSigner->signBackup($backupFilePath ?: '');
        } catch (Exception $e) {
            $this->logger->critical(sprintf('The backup file could not be signed for consistency. Error: %s', $e->getMessage()));

            return $this->generateResponse();
        }

        // Validate the Signed Backup
        try {
            $this->backupSigner->validateSignedBackup($backupFilePath ?: '');
        } catch (Exception $e) {
            $this->logger->critical(sprintf('The backup seems to be invalid: %s', $e->getMessage()));

            return $this->generateResponse();
        }

        $this->logger->info('The backup was signed successfully.');

        $this->stepsDto->finish();

        return $this->generateResponse(false);
    }
}
