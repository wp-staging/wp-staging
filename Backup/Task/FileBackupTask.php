<?php

// TODO PHP7.1; constant visibility

namespace WPStaging\Backup\Task;

use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Backup\Service\FileBackupService;
use WPStaging\Backup\Service\FileBackupServiceProvider;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

abstract class FileBackupTask extends BackupTask
{
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
        $this->fileBackupService->setupArchiver($this->getFileIdentifier(), $this->isOtherWpRootFilesTask());
        $this->fileBackupService->execute();

        return $this->generateResponse(false);
    }

    /** @return string */
    abstract protected function getFileIdentifier(): string;

    /**
     * @return void
     */
    private function prepareFileBackupTask()
    {
        $this->fileBackupService->inject($this->taskQueue, $this->logger, $this->jobDataDto, $this->stepsDto);
        if ($this->stepsDto->getTotal() > 0) {
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
}
