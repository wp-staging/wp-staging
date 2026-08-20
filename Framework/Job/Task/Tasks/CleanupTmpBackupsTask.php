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




class CleanupTmpBackupsTask extends AbstractTask
{
 
    private $backupsFinder;

 
    private $tmpBackupCleaner;









    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, BackupsFinder $backupsFinder, TmpBackupCleaner $tmpBackupCleaner, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->backupsFinder    = $backupsFinder;
        $this->tmpBackupCleaner = $tmpBackupCleaner;
    }




    public static function getTaskName()
    {
        return 'cancel_cleanup_backups';
    }




    public static function getTaskTitle()
    {
        return esc_html__('Cleaning up temporary backups…', 'wp-staging');
    }




    public function execute()
    {
        $this->tmpBackupCleaner->clean($this->backupsFinder->getBackupsDirectory());

        $this->logger->info('Temporary backups cleanup completed.');

        return $this->generateResponse();
    }
}
