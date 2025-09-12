<?php

namespace WPStaging\Framework\Job\Task\Tasks;

use DirectoryIterator;
use WPStaging\Backup\Service\Archiver;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Task\AbstractTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;

class CleanupTmpBackupsTask extends AbstractTask
{
    /** @var Directory */
    private $directory;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param Directory $directory
     * @param SeekableQueueInterface $taskQueue
     */
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, Directory $directory, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->directory = $directory;
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
        $tmpBackupsDir = $this->directory->getBackupDirectory();

        $tmpBackupsDir = untrailingslashit($tmpBackupsDir);

        // Early bail: Path to Clean does not exist
        if (!file_exists($tmpBackupsDir)) {
            return $this->generateResponse();
        }

        $iterator = new DirectoryIterator($tmpBackupsDir);

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile()) {
                continue;
            }

            if (!$this->isTmpBackup($fileInfo->getFilename())) {
                continue;
            }

            unlink($fileInfo->getPathname());
        }

        $this->logger->info('Temporary backups cleanup completed.');

        return $this->generateResponse();
    }

    /**
     * @param string $filename
     * @return bool
     */
    private function isTmpBackup(string $filename): bool
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return $extension === Archiver::TMP_BACKUP_EXTENSION;
    }
}
