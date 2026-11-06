<?php

namespace WPStaging\Staging\Tasks\StagingSite;

use Exception;
use Throwable;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Traits\WithUnfinishedCloneWpConfigGuard;
use WPStaging\Staging\FolderOwnership;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;
use WPStaging\Staging\Interfaces\StagingSiteDtoInterface;
use WPStaging\Staging\Tasks\StagingTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class CleanupStagingFilesTask extends StagingTask
{
    use WithUnfinishedCloneWpConfigGuard;

 
    private $filesystem;

 
    private $folderOwnership;









    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, Filesystem $filesystem, SeekableQueueInterface $taskQueue, FolderOwnership $folderOwnership)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->filesystem      = $filesystem;
        $this->folderOwnership = $folderOwnership;
    }




    public static function getTaskName()
    {
        return 'staging_cleanup_files';
    }




    public static function getTaskTitle()
    {
        return 'Cleaning Up Staging Site Files';
    }




    public function execute()
    {
        $stagingSiteDir = '';
        try {
            $stagingSiteDir = $this->prepareCleanup();
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
            return $this->generateResponse(false);
        }

        $stagingSiteDir = untrailingslashit($stagingSiteDir);

        if ($this->filesystem->normalizePath(ABSPATH, true) === $this->filesystem->normalizePath($stagingSiteDir, true)) {
            $this->logger->warning(sprintf(
                '%s: Path "%s" is the same as the WordPress root directory. This is not allowed.',
                static::getTaskTitle(),
                $stagingSiteDir
            ));

            return $this->generateResponse();
        }

        if (!$this->folderOwnership->isStagingSiteFolder($stagingSiteDir)) {
            $this->logger->warning(sprintf(
                '%s: WP STAGING cannot confirm that path "%s" holds a staging site, so the folder is kept.',
                static::getTaskTitle(),
                $stagingSiteDir
            ));

            return $this->generateResponse();
        }

        $relativePathForLogging = str_replace($this->filesystem->normalizePath(ABSPATH, true), '', $this->filesystem->normalizePath($stagingSiteDir, true));

 
        if (!file_exists($stagingSiteDir)) {
            return $this->generateResponse();
        }

        try {
            $this->filesystem
                ->setRecursive(true)
                ->setShouldStop(function () {
                    return $this->isThreshold();
                });
            $deleted = $this->isResetJob()
                ? $this->deleteStagingSiteFilesWithWpConfigLast($this->filesystem, $stagingSiteDir, true)
                : $this->filesystem->delete($stagingSiteDir);
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




    public function prepareCleanup(): string
    {
        if (!$this->jobDataDto instanceof StagingSiteDtoInterface) {
            throw new Exception('Clone ID not found in job data.');
        }

 
        $jobDataDto  = $this->jobDataDto;

 
        if ($this->stepsDto->getTotal() === 1) {
            return $jobDataDto->getStagingSite()->getPath();
        }

        $stagingSiteDto = $this->getStagingSiteDto($jobDataDto->getCloneId());
        $jobDataDto->setStagingSite($stagingSiteDto);

        $this->stepsDto->setTotal(1);

        return $stagingSiteDto->getPath();
    }




    private function isResetJob(): bool
    {
        return $this->jobDataDto instanceof StagingOperationDtoInterface && $this->jobDataDto->getIsResetJob();
    }
}
