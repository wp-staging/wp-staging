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

/**
 * Class FileRestoreTask
 *
 * This is an abstract class for the filesystem-based restore actions of restoring a site,
 * such as plugins, themes, mu-plugins and uploads files.
 *
 * It's main philosophy is to control the individual queue of what needs to be processed
 * from each of the concrete restores. It delegates actual processing of the queue to a separate class.
 *
 * @package WPStaging\Backup\Abstracts\Task
 */
abstract class FileRestoreTask extends RestoreTask implements FileTaskInterface
{
    use EndOfLinePlaceholderTrait;
    use RestoreFileExclusionTrait;

    /**
     * @var string
     */
    const FILTER_EXCLUDE_FILES_DURING_RESTORE = 'wpstg.backup.restore.exclude_paths';

    /**
     * Note: internal use
     * @var string
     */
    const FILTER_EXCLUDE_ENQUEUE_DELETE = 'wpstg.backup.restore.exclude_enqueue_delete';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Directory
     */
    protected $directory;

    /**
     * @var FileProcessor
     */
    private $restoreFileProcessor;

    /**
     * @var int
     */
    protected $processedNow;

    /**
     * @var PathIdentifier
     */
    protected $pathIdentifier;

    /**
     * @var bool
     */
    protected $isSiteHostedOnWordPressCom = false;

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

    /**
     * @return void
     */
    public function prepareFileRestore()
    {
        if ($this->stepsDto->getTotal() === 0) {
            $this->buildQueue();
            $this->taskQueue->seek(0);

            // Just an arbitrary number, when there are no more items in the Queue we call stepsDto->finish()
            $this->stepsDto->setTotal(100);
        }
    }

    /**
     * @return TaskResponseDto
     */
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

    /**
     * Concrete classes of the FileRestoreTask must build
     * the queue once, enqueuing everything that needs
     * to be moved or deleted, using $this->enqueueMove
     * or $this->enqueueDelete.
     *
     * @return void
     */
    abstract protected function buildQueue();

    /**
     * Skip the task if part is missing for this task
     *
     * @return array
     */
    abstract protected function getParts(): array;

    /**
     * Skip the task if set by filter
     *
     * @return bool
     */
    abstract protected function isSkipped(): bool;

    /**
     * Skip the task if part is missing for this task
     *
     * @throws MissingFileException
     * @return void
     */
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

    /**
     * Executes the next item in the queue.
     * @return void
     */
    protected function processNextItemInQueue()
    {
        $nextInQueueRaw = $this->taskQueue->dequeue();

        if (is_null($nextInQueueRaw)) {
            throw new FinishedQueueException();
        }

        // Skip blank lines
        if ($nextInQueueRaw === '') {
            return;
        }

        $nextInQueue = json_decode($nextInQueueRaw, true);

        // Make sure we read expected data from the queue
        if (!is_array($nextInQueue)) {
            $this->logger->warning(sprintf(
                __('%s: An internal error occurred that prevented this item from being restored. Skipping it... (Error Code: INVALID_QUEUE_ITEM)', 'wp-staging'),
                static::getTaskTitle()
            ));
            $this->logger->debug($nextInQueueRaw);

            return;
        }

        // Make sure data is in the expected format
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

        // Make sure destination is within WordPress
        // @todo Test backup in Windows and restoring in Linux and vice-versa
        $destination = $nextInQueue['destination'];
        $destination = $this->replacePlaceholdersWithEOLs($destination);
        $destination = wp_normalize_path($destination);

        // Executes the action
        $this->restoreFileProcessor->handle($nextInQueue['action'], $source, $destination, $this, $this->logger);
    }

    /**
     * @param string $source Source path to move.
     * @param string $destination Where to move source to.
     * @return void
     */
    public function enqueueMove(string $source, string $destination)
    {
        $this->enqueue([
            'action'      => 'move',
            'source'      => wp_normalize_path($source),
            'destination' => wp_normalize_path($destination),
        ]);
    }

    /**
     * @param string $path The path to delete. Can be a folder, which will be deleted recursively.
     * @return void
     */
    public function enqueueDelete(string $path)
    {
        if ($this->isExcludeEnqueueDelete($path)) {
            return;
        }

        $this->enqueue([
            'action'      => 'delete',
            'source'      => '',
            'destination' => wp_normalize_path($path),
        ]);
    }

    /**
     * Use to retry last action in next request,
     * if it wasn't completed in current request.
     * @return void
     */
    public function retryLastActionInNextRequest()
    {
        $this->taskQueue->retry($dequeue = false);
    }

    /**
     * @return bool
     */
    protected function isRestoreOnSubsite(): bool
    {
        if (!is_multisite()) {
            return false;
        }

        return $this->jobDataDto->getBackupMetadata()->getBackupType() !== BackupMetadata::BACKUP_TYPE_MULTISITE;
    }

    /**
     * @param array $action An array of actions to perform.
     * @return void
     */
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
