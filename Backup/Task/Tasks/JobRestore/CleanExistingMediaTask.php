<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;

class CleanExistingMediaTask extends RestoreTask
{
    protected $filesystem;
    protected $directory;

    protected $processedNow;

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Filesystem $filesystem, Directory $directory)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->filesystem = $filesystem;
        $this->directory = $directory;
    }

    public static function getTaskName()
    {
        return 'backup_restore_clean_media';
    }

    public static function getTaskTitle()
    {
        return 'Cleaning Existing Media';
    }

    public function prepareCleaningMedia()
    {
        if ($this->stepsDto->getTotal() === 0) {
            $this->taskQueue->seek(0);

            // Just an arbitrary number, when there are no more items to clean we call stepsDto->finish()
            $this->stepsDto->setTotal(100);
        }
    }

    public function execute()
    {
        if (apply_filters('wpstg.backup.restore.keepExistingMedia', false)) {
            $this->stepsDto->finish();
            $this->logger->info(sprintf(esc_html__('%s (skipped)', 'wp-staging'), static::getTaskTitle()));
            return $this->generateResponse(false);
        }

        $this->prepareCleaningMedia();

        $excludedPaths = [
            rtrim($this->directory->getPluginUploadsDirectory(), '/')
        ];

        $this->filesystem->setShouldStop([$this, 'isThreshold'])
            ->setExcludePaths($excludedPaths)
            ->setWpRootPath(WP_CONTENT_DIR)
            ->setRecursive();

        $result = false;
        $this->filesystem->setProcessedCount(0);

        try {
            $result = $this->filesystem->delete($this->directory->getUploadsDirectory(), false);
            $this->processedNow = $this->filesystem->getProcessedCount();
        } catch (\Exception $e) {
            //
        }

        // Finish if all media files are deleted
        if ($result !== false) {
            $this->stepsDto->finish();
        }

        $this->logger->info(sprintf(esc_html__('%s (cleaned %d items)', 'wp-staging'), static::getTaskTitle(), $this->processedNow));

        return $this->generateResponse(false);
    }
}
