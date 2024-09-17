<?php

namespace WPStaging\Backup\Task\Tasks;

use Throwable;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\FilesystemExceptions;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Task\AbstractTask;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Filesystem\Filesystem;

class CleanupTmpFilesTask extends AbstractTask
{
    /** @var Filesystem */
    private $filesystem;

    /** @var Directory */
    private $directory;

    /** @var PathIdentifier */
    private $pathIdentifier;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param Filesystem $filesystem
     * @param Directory $directory
     * @param SeekableQueueInterface $taskQueue
     * @param PathIdentifier $pathIdentifier
     */
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, Filesystem $filesystem, Directory $directory, SeekableQueueInterface $taskQueue, PathIdentifier $pathIdentifier)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->filesystem     = $filesystem;
        $this->directory      = $directory;
        $this->pathIdentifier = $pathIdentifier;
    }

    /**
     * @example 'backup_site_restore_themes'
     * @return string
     */
    public static function getTaskName(): string
    {
        return 'backup_restore_cleanup_files';
    }

    /**
     * @example 'Restoring Themes From Backup'
     * @return string
     */
    public static function getTaskTitle(): string
    {
        return 'Cleaning Up Restore Files';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute(): TaskResponseDto
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

            try {
                $this->cleanPluginWpContentDir();
            } catch (Throwable $ex) {
            }

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
                '%s: Re-enqueuing path %s for deletion, as it couldn\'t be deleted in a single request without
                    hitting execution limits. If you see this message in a loop, PHP might not be able to delete
                    this directory, so you might want to try to delete it manually.',
                static::getTaskTitle(),
                $relativePathForLogging
            ));

            // Early bail: Response modified for repeating
            return $response;
        }
    }

    /**
     * @return void
     * @throws FilesystemExceptions
     */
    protected function cleanPluginWpContentDir()
    {
        $pluginWpContentDir = $this->directory->getPluginWpContentDirectory();
        if (!file_exists($pluginWpContentDir)) {
            return;
        }

        $tmpDirectory = trailingslashit($pluginWpContentDir) . 'tmp';
        if (file_exists($tmpDirectory) && $this->filesystem->isEmptyDir($tmpDirectory)) {
            $this->filesystem->delete($tmpDirectory);
        }

        if ($this->filesystem->isEmptyDir($pluginWpContentDir)) {
            $this->filesystem->delete($pluginWpContentDir);
        }
    }

    /**
     * @return void
     */
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

        /** @var JobRestoreDataDto */
        $jobDataDto = $this->jobDataDto;

        // Clear the .sql file used during the restore, if this backup includes a database.
        $databaseFile = $jobDataDto->getBackupMetadata()->getDatabaseFile();

        if ($databaseFile) {
            $databaseFile = $this->pathIdentifier->transformIdentifiableToPath($jobDataDto->getBackupMetadata()->getDatabaseFile());

            if (file_exists($databaseFile)) {
                unlink($databaseFile);
            }
        }

        $this->stepsDto->setTotal(1);
    }
}
