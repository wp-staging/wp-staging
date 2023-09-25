<?php

namespace WPStaging\Backup\Task\Tasks;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Task\AbstractTask;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Filesystem\Filesystem;

class CleanupTmpFilesTask extends AbstractTask
{
    private $filesystem;
    private $directory;
    private $pathIdentifier;

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, Filesystem $filesystem, Directory $directory, SeekableQueueInterface $taskQueue, PathIdentifier $pathIdentifier)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->filesystem = $filesystem;
        $this->directory = $directory;
        $this->pathIdentifier = $pathIdentifier;
    }

    public static function getTaskName()
    {
        return 'backup_restore_cleanup_files';
    }

    public static function getTaskTitle()
    {
        return 'Cleaning Up Restore Files';
    }

    /**
     * @return \WPStaging\Backup\Dto\TaskResponseDto
     */
    public function execute()
    {
        $this->prepareCleanupRestoreTask();

        $tmpRestoreDir = $this->directory->getTmpDirectory();

        $tmpRestoreDir = untrailingslashit($tmpRestoreDir);

        $relativePathForLogging = str_replace($this->filesystem->normalizePath(ABSPATH, true), '', $this->filesystem->normalizePath($tmpRestoreDir, true));

        // Early bail: Path to Clean does not exist
        if (!file_exists($tmpRestoreDir)) {
            return $this->generateResponse();
        }

        try {
            $deleted = $this->filesystem
                ->setRecursive(true)
                ->setShouldStop(function () {
                    return $this->isThreshold();
                })
                ->delete($tmpRestoreDir);
        } catch (\Exception $e) {
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
            /*
             * Not successfully deleted.
             * This can happen if the folder to delete is too large
             * to be deleted in a single request. We continue
             * deleting it in the next request...
             */
            $response = $this->generateResponse(false);
            $response->setIsRunning(true);

            $this->logger->info(sprintf(
                '%s: Re-enqueing path %s for deletion, as it couldn\'t be deleted in a single request without
                    hitting execution limits. If you see this message in a loop, PHP might not be able to delete
                    this directory, so you might want to try to delete it manually.',
                static::getTaskTitle(),
                $relativePathForLogging
            ));

            // Early bail: Response modified for repeating
            return $response;
        }
    }

    public function prepareCleanupRestoreTask()
    {
        // We only cleanup database file for RestoreTask
        if (!$this instanceof RestoreTask) {
            return;
        }

        // Early bail: Already prepared
        if ($this->stepsDto->getTotal() === 1) {
            return;
        }

        // Clear the .sql file used during the restore, if this backup includes a database.
        $databaseFile = $this->jobDataDto->getBackupMetadata()->getDatabaseFile();

        if ($databaseFile) {
            $databaseFile = $this->pathIdentifier->transformIdentifiableToPath($this->jobDataDto->getBackupMetadata()->getDatabaseFile());

            if (file_exists($databaseFile)) {
                unlink($databaseFile);
            }
        }

        $this->stepsDto->setTotal(1);
    }
}
