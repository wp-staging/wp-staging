<?php

namespace WPStaging\Framework\Job\Task\Tasks;

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
 
    private $filesystem;

 
    private $directory;

 
    private $pathIdentifier;










    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, Filesystem $filesystem, Directory $directory, SeekableQueueInterface $taskQueue, PathIdentifier $pathIdentifier)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->filesystem     = $filesystem;
        $this->directory      = $directory;
        $this->pathIdentifier = $pathIdentifier;
    }




    public static function getTaskName(): string
    {
        return 'cancel_cleanup_files';
    }




    public static function getTaskTitle(): string
    {
        return esc_html__('Cleaning up temporary files…', 'wp-staging');
    }




    public function execute(): TaskResponseDto
    {
        $this->prepareCleanupRestoreTask();

        $tmpRestoreDir = $this->directory->getTmpDirectory();

        $tmpRestoreDir = untrailingslashit($tmpRestoreDir);

        $relativePathForLogging = str_replace($this->filesystem->normalizePath(ABSPATH, true), '', $this->filesystem->normalizePath($tmpRestoreDir, true));

 
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






            $response = $this->generateResponse(false);
            $response->setIsRunning(true);

            $this->logger->info(sprintf(
                '%s: Re-enqueuing path %s for deletion, as it couldn\'t be deleted in a single request without
                    hitting execution limits. If you see this message in a loop, PHP might not be able to delete
                    this directory, so you might want to try to delete it manually.',
                static::getTaskTitle(),
                $relativePathForLogging
            ));

 
            return $response;
        }
    }





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




    public function prepareCleanupRestoreTask()
    {
 
        if (!$this instanceof RestoreTask) {
            return;
        }

 
        if ($this->stepsDto->getTotal() === 1) {
            return;
        }

 
        $jobDataDto = $this->jobDataDto;

 
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
