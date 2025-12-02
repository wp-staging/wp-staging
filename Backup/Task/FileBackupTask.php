<?php

/**
 * Base class for file backup tasks that archive WordPress files into backups
 *
 * Manages the coordination between file discovery and archiving, handling large files
 * across multiple requests while tracking progress and managing graceful shutdowns.
 */

namespace WPStaging\Backup\Task;

use WPStaging\Backup\Service\FileBackupService;
use WPStaging\Backup\Service\FileBackupServiceProvider;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

abstract class FileBackupTask extends BackupTask
{
    /**
     * Whether if the file backup task gracefully shuts down
     */
    const TRANSIENT_GRACEFUL_SHUTDOWN = 'wpstg_file_backup_task';

    /** @var FileBackupService */
    protected $fileBackupService;

    public function __construct(FileBackupServiceProvider $fileBackupServiceProvider, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->fileBackupService = $fileBackupServiceProvider->getService(); // @phpstan-ignore-line
    }

    public static function getTaskName(): string
    {
        return 'backup_file_task';
    }

    public static function getTaskTitle(): string
    {
        return 'Adding Files to Backup';
    }

    public function execute(): TaskResponseDto
    {
        $this->prepareFileBackupTask();
        set_transient(self::TRANSIENT_GRACEFUL_SHUTDOWN, '1', 60);
        $this->fileBackupService->setupArchiver($this->getFileIdentifier(), $this->isOtherWpRootFilesTask());
        $this->fileBackupService->execute();

        delete_transient(self::TRANSIENT_GRACEFUL_SHUTDOWN);
        return $this->generateResponse(false);
    }

    /** @return string */
    abstract protected function getFileIdentifier(): string;

    /**
     * @return void
     */
    private function prepareFileBackupTask()
    {
        $this->fileBackupService->inject($this, $this->taskQueue, $this->logger, $this->jobDataDto, $this->stepsDto);
        if ($this->stepsDto->getTotal() > 0) {
            $this->checkIfLastRequestGracefulShutdown();
            return;
        }

        $this->stepsDto->setTotal($this->jobDataDto->getDiscoveredFilesByCategory($this->getFileIdentifier()));
    }

    /**
     * @return bool
     */
    protected function isOtherWpRootFilesTask(): bool
    {
        return false;
    }

    protected function checkIfLastRequestGracefulShutdown()
    {
        $transient = get_transient(self::TRANSIENT_GRACEFUL_SHUTDOWN);
        // empty that mean it was graceful shutdown
        if (empty($transient)) {
            return;
        }

        $this->logger->debug('Resuming file backup task after a non-graceful shutdown.');
        $this->fileBackupService->setIsGracefulShutdown(false);
    }
}
