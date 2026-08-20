<?php

namespace WPStaging\Staging\Tasks;

use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Interfaces\FilesystemScannerDtoInterface;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Staging\Dto\Task\FileCopierTaskDto;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;
use WPStaging\Staging\Service\FileCopier;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

abstract class FileCopierTask extends StagingTask
{
 
    protected $fileCopier;

 
    protected $jobDataDto; // @phpstan-ignore-line

 
    protected $currentTaskDto;

    public function __construct(FileCopier $fileCopier, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->fileCopier = $fileCopier;
    }

    public static function getTaskName(): string
    {
        return 'staging_file_copier_task';
    }

    public static function getTaskTitle(): string
    {
        return 'Copying Files to Staging Site';
    }

    public function execute(): TaskResponseDto
    {
 
        if ($this->getIsExcluded()) {
            return $this->generateResponse(true);
        }

        $this->prepareFileCopyingTask();
 
        if ($this->stepsDto->getTotal() === 0) {
            return $this->generateResponse(true);
        }

        $this->fileCopier->setup($this->jobDataDto->getStagingSitePath(), $this->getLoggerFriendlyName(), $this->getIsWpContent());
        $this->fileCopier->execute();

        $this->currentTaskDto->setBigFileDto($this->fileCopier->getBigFileDto());
        $this->setCurrentTaskDto($this->currentTaskDto);

        return $this->generateResponse(false);
    }

 
    abstract protected function getFileIdentifier(): string;

    protected function getLoggerFriendlyName(): string
    {
        return $this->getFileIdentifier();
    }

 
    protected function getIsWpContent(): bool
    {
        return false;
    }

 
    protected function getIsExcluded(): bool
    {
        return false;
    }

 
    protected function getCurrentTaskType(): string
    {
        return FileCopierTaskDto::class;
    }




    private function prepareFileCopyingTask()
    {
        $this->fileCopier->inject($this->taskQueue, $this->logger, $this->stepsDto);
        $this->fileCopier->setupBigFileBeingCopied($this->currentTaskDto->getBigFileDto());
        if ($this->stepsDto->getTotal() > 0) {
            return;
        }

        $this->stepsDto->setTotal($this->jobDataDto->getDiscoveredFilesByIdentifier($this->getFileIdentifier()));
    }
}
