<?php

namespace WPStaging\Backup\Task;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Traits\EndOfLinePlaceholderTrait;
use WPStaging\Framework\Traits\RestoreFileExclusionTrait;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Framework\Job\Interfaces\FileTaskInterface;
use WPStaging\Framework\Job\Task\FileHandler\FileProcessor;
use WPStaging\Framework\SiteInfo;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Facades\Hooks;












abstract class FileRestoreTask extends RestoreTask implements FileTaskInterface
{
    use EndOfLinePlaceholderTrait;
    use RestoreFileExclusionTrait;




    const FILTER_EXCLUDE_FILES_DURING_RESTORE = 'wpstg.backup.restore.exclude_paths';





    const FILTER_EXCLUDE_ENQUEUE_DELETE = 'wpstg.backup.restore.exclude_enqueue_delete';




    protected $filesystem;




    protected $directory;




    private $restoreFileProcessor;




    protected $processedNow;




    protected $pathIdentifier;




    protected $isSiteHostedOnWordPressCom = false;




    private $movedAsideDestinations = [];

    public function __construct(
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue,
        Filesystem $filesystem,
        Directory $directory,
        FileProcessor $restoreFileProcessor,
        PathIdentifier $pathIdentifier,
        SiteInfo $siteInfo
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->filesystem                 = $filesystem;
        $this->directory                  = $directory;
        $this->restoreFileProcessor       = $restoreFileProcessor;
        $this->pathIdentifier             = $pathIdentifier;
        $this->isSiteHostedOnWordPressCom = $siteInfo->isHostedOnWordPressCom();
    }




    public function prepareFileRestore()
    {
        if ($this->stepsDto->getTotal() === 0) {
            $this->buildQueue();
            $this->taskQueue->seek(0);

 
            $this->stepsDto->setTotal(100);
        }
    }




    public function execute(): TaskResponseDto
    {
        if ($this->isSkipped()) {
            $this->stepsDto->finish();
            $this->logger->warning(sprintf(esc_html__('%s skipped by filter!', 'wp-staging'), static::getTaskTitle()));
            return $this->generateResponse(false);
        }

        try {
            $this->checkMissingParts();
        } catch (MissingFileException $ex) {
            $this->stepsDto->finish();
            $this->logger->warning(sprintf(esc_html__('%s skipped due to missing part!', 'wp-staging'), static::getTaskTitle()));
            return $this->generateResponse(false);
        }

        $this->prepareFileRestore();

        try {
            while (!$this->isThreshold()) {
                $this->processNextItemInQueue();
                $this->processedNow++;
            }
        } catch (FinishedQueueException $e) {
            $this->stepsDto->finish();
        }

        $this->logger->info(sprintf(esc_html__('%s (processed %d items)', 'wp-staging'), static::getTaskTitle(), (int)$this->processedNow));

        return $this->generateResponse(false);
    }

    protected function getOriginalSuffix(): string
    {
        return '_wpstg_tmp';
    }









    abstract protected function buildQueue();






    abstract protected function getParts(): array;






    abstract protected function isSkipped(): bool;







    protected function checkMissingParts()
    {
        if (!$this->jobDataDto->getBackupMetadata()->getIsMultipartBackup()) {
            return;
        }

        $parts = $this->getParts();

        $backupDir = $this->directory->getBackupDirectory();

        foreach ($parts as $part) {
            $filepath = $backupDir . $part;
            if (!file_exists($filepath)) {
                throw new MissingFileException();
            }
        }
    }





    protected function processNextItemInQueue()
    {
        $nextInQueueRaw = $this->taskQueue->dequeue();

        if (is_null($nextInQueueRaw)) {
            throw new FinishedQueueException();
        }

 
        if ($nextInQueueRaw === '') {
            return;
        }

        $nextInQueue = json_decode($nextInQueueRaw, true);

 
        if (!is_array($nextInQueue)) {
            $this->logger->warning(sprintf(
                __('%s: An internal error occurred that prevented this item from being restored. Skipping it... (Error Code: INVALID_QUEUE_ITEM)', 'wp-staging'),
                static::getTaskTitle()
            ));
            $this->logger->debug($nextInQueueRaw);

            return;
        }

 
        array_map(function ($requiredKey) use ($nextInQueue, $nextInQueueRaw) {
            if (!array_key_exists($requiredKey, $nextInQueue)) {
                $this->logger->warning(sprintf(
                    __('%s: An internal error occurred that prevented this item from being restored. Skipping it... (Error Code: INVALID_QUEUE_ITEM)', 'wp-staging'),
                    static::getTaskTitle()
                ));
                $this->logger->debug($nextInQueueRaw);

                return;
            }
        }, ['action', 'source', 'destination']);

        $source = $nextInQueue['source'];

 
 
        $destination = $nextInQueue['destination'];
        $destination = $this->replacePlaceholdersWithEOLs($destination);
        $destination = wp_normalize_path($destination);

 
        $this->restoreFileProcessor->handle($nextInQueue['action'], $source, $destination, $this, $this->logger);
    }






    public function enqueueMove(string $source, string $destination)
    {
        $destination = wp_normalize_path($destination);

        if ($this->hasOriginalSuffix($destination)) {
            $this->movedAsideDestinations[] = untrailingslashit($destination);
        }

        $this->enqueue([
            'action'      => 'move',
            'source'      => wp_normalize_path($source),
            'destination' => $destination,
        ]);
    }





    public function enqueueDelete(string $path)
    {
        if ($this->isExcludeEnqueueDelete($path)) {
            return;
        }

        $path = wp_normalize_path($path);

        $this->announceQueuedDeletion($path);

        $this->enqueue([
            'action'      => 'delete',
            'source'      => '',
            'destination' => $path,
        ]);
    }








    protected function announceQueuedDeletion(string $path)
    {
        if (in_array(untrailingslashit($path), $this->movedAsideDestinations, true)) {
            return;
        }

        $this->logger->info(sprintf(
            esc_html__('%s: Deleting %s', 'wp-staging'),
            static::getTaskTitle(),
            $this->filesystem->getPathRelativeToAbspath($path)
        ));
    }





    private function hasOriginalSuffix(string $path): bool
    {
        $suffix = $this->getOriginalSuffix();

        return substr(untrailingslashit($path), -strlen($suffix)) === $suffix;
    }






    public function retryLastActionInNextRequest()
    {
        $this->taskQueue->retry($dequeue = false);
    }




    protected function isRestoreOnSubsite(): bool
    {
        if (!is_multisite()) {
            return false;
        }

        return $this->jobDataDto->getBackupMetadata()->getBackupType() !== BackupMetadata::BACKUP_TYPE_MULTISITE;
    }





    private function enqueue(array $action)
    {
        $this->taskQueue->enqueue(json_encode($action));
    }

    private function isExcludeEnqueueDelete($filePath)
    {
        $normalizedFilePath = rtrim(wp_normalize_path($filePath), '/');
        $excludedFiles      = Hooks::applyFilters(self::FILTER_EXCLUDE_ENQUEUE_DELETE, []);

        if (empty($excludedFiles)) {
            return false;
        }

        foreach ($excludedFiles as $excludedFile) {
            $normalizedExcludedFile = rtrim(wp_normalize_path($excludedFile), '/');
            if (strpos($normalizedFilePath, $normalizedExcludedFile) === 0) {
                return true;
            }
        }

        return false;
    }
}
