<?php

namespace WPStaging\Backup\Task;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\MissingFileException;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Task\RestoreFileHandlers\RestoreFileProcessor;
use WPStaging\Framework\SiteInfo;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

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
abstract class FileRestoreTask extends RestoreTask
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Directory
     */
    protected $directory;

    /**
     * @var RestoreFileProcessor
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
        RestoreFileProcessor $restoreFileProcessor,
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

            // Just an arbitrary number, when there are no more items in the Queue we call stepsDto->finish()
            $this->stepsDto->setTotal(100);
        }
    }

    /**
     * @return \WPStaging\Backup\Dto\TaskResponseDto
     */
    public function execute()
    {
        try {
            $this->checkMissingParts();
        } catch (MissingFileException $ex) {
            $this->stepsDto->finish();
            $this->logger->warning(sprintf(esc_html__('%s Skipped!', 'wp-staging'), static::getTaskTitle()));
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

    protected function getOriginalSuffix()
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
    abstract protected function getParts();

    /**
     * Skip the task if part is missing for this task
     *
     * @throws MissingFileException
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
        $destination = wp_normalize_path($destination);

        // Executes the action
        $this->restoreFileProcessor->handle($nextInQueue['action'], $source, $destination, $this, $this->logger);
    }

    /**
     * @param string $source Source path to move.
     * @param string $destination Where to move source to.
     */
    public function enqueueMove($source, $destination)
    {
        $this->enqueue([
            'action' => 'move',
            'source' => wp_normalize_path($source),
            'destination' => wp_normalize_path($destination),
        ]);
    }

    /**
     * @param string $path The path to delete. Can be a folder, which will be deleted recursively.
     */
    public function enqueueDelete($path)
    {
        $this->enqueue([
            'action' => 'delete',
            'source' => '',
            'destination' => wp_normalize_path($path),
        ]);
    }

    /**
     * Use to retry last action in next request,
     * if it wasn't completed in current request.
     */
    public function retryLastActionInNextRequest()
    {
        $this->taskQueue->retry($dequeue = false);
    }

    /**
     * @param array $action An array of actions to perform.
     */
    private function enqueue($action)
    {
        $this->taskQueue->enqueue(json_encode($action));
    }
}
