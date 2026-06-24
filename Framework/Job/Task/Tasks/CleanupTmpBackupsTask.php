<?php

namespace WPStaging\Framework\Job\Task\Tasks;

use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Service\TmpBackupCleaner;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Task\AbstractTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;

/**
 * Cleans temporary backup files created by backup and Remote Sync jobs
 */
class CleanupTmpBackupsTask extends AbstractTask
{
    /** @var BackupsFinder */
    private $backupsFinder;

    /** @var TmpBackupCleaner */
    private $tmpBackupCleaner;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param BackupsFinder $backupsFinder
     * @param TmpBackupCleaner $tmpBackupCleaner
     * @param SeekableQueueInterface $taskQueue
     */
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, BackupsFinder $backupsFinder, TmpBackupCleaner $tmpBackupCleaner, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->backupsFinder    = $backupsFinder;
        $this->tmpBackupCleaner = $tmpBackupCleaner;
    }

    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'cancel_cleanup_backups';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return esc_html__('Cleaning up temporary backups…', 'wp-staging');
    }

    /**
     * @return TaskResponseDto
     */
    public function execute()
    {
        $this->tmpBackupCleaner->clean($this->backupsFinder->getBackupsDirectory());

        $this->logger->info('Temporary backups cleanup completed.');

        return $this->generateResponse();
    }
}
