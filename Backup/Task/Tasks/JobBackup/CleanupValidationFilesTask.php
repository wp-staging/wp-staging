<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use Throwable;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Backup\Task\BackupTask;

class CleanupValidationFilesTask extends BackupTask
{
    /** @var Filesystem */
    private $filesystem;

    /** @var Directory */
    private $directory;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param Filesystem $filesystem
     * @param Directory $directory
     * @param SeekableQueueInterface $taskQueue
     */
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, Filesystem $filesystem, Directory $directory, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->filesystem = $filesystem;
        $this->directory  = $directory;
    }

    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'backup_cleanup_validation_files';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Cleaning Up Validation Files';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute()
    {
        $validationDir = $this->directory->getTmpDirectory();

        $validationDir = untrailingslashit($validationDir);

        $relativePathForLogging = str_replace($this->filesystem->normalizePath(ABSPATH, true), '', $this->filesystem->normalizePath($validationDir, true));

        // Early bail: Path to Clean does not exist
        if (!file_exists($validationDir)) {
            return $this->generateResponse();
        }

        try {
            $deleted = $this->filesystem
                ->setRecursive(true)
                ->setShouldStop(function () {
                    return $this->isThreshold();
                })
                ->delete($validationDir);
        } catch (Throwable $e) {
            $this->logger->warning(sprintf(
                '%s: Could not cleanup path "%s". May be a permission issue?',
                static::getTaskTitle(),
                $relativePathForLogging
            ));

            return $this->generateResponse();
        }

        if ($deleted) {
            // Successfully deleted
            $this->logger->info(sprintf(
                '%s: Path "%s" successfully cleaned up.',
                static::getTaskTitle(),
                $relativePathForLogging
            ));

            return $this->generateResponse();
        } else {
            /**
             * Not successfully deleted.
             * This can happen if the folder to delete is too large
             * to be deleted in a single request. We continue
             * deleting it in the next request...
             */
            $response = $this->generateResponse(false);
            $response->setIsRunning(true);

            $this->logger->info(sprintf(
                '%s: Re‑queuing folder %s for deletion. The previous attempt exceeded PHP’s execution‑time limit. If this message repeats, PHP can’t remove the directory; delete it manually to free the disk space.',
                static::getTaskTitle(),
                $relativePathForLogging
            ));

            // Early bail: Response modified for repeating
            return $response;
        }
    }

    /**
     * @return void
     */
    public function prepareCleanupValidationTask()
    {
        // Early bail: Already prepared
        if ($this->stepsDto->getTotal() === 1) {
            return;
        }

        $this->stepsDto->setTotal(1);
    }
}
