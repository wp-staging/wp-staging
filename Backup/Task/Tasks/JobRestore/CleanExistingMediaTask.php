<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;

class CleanExistingMediaTask extends RestoreTask
{
    /**
     * Old filter, cannot be renamed to new pattern
     * @var string
     */
    const FILTER_KEEP_EXISTING_MEDIA = 'wpstg.backup.restore.keepExistingMedia';

    /**
     * Paths given should be absolute
     * @var string
     */
    const FILTER_EXCLUDE_MEDIA_DURING_CLEANUP = 'wpstg.backup.restore.exclude_media_during_cleanup';

    /** @var Filesystem */
    protected $filesystem;

    /** @var Directory */
    protected $directory;

    /** @var int */
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
        if ($this->isBackupPartSkipped(PartIdentifier::UPLOAD_PART_IDENTIFIER)) {
            $this->stepsDto->finish();
            $this->logger->warning(sprintf(esc_html__('%s skipped because upload excluded by filter', 'wp-staging'), static::getTaskTitle()));
            return $this->generateResponse(false);
        }

        if (Hooks::applyFilters(self::FILTER_KEEP_EXISTING_MEDIA, false)) {
            $this->stepsDto->finish();
            $this->logger->info(sprintf(esc_html__('%s (skipped)', 'wp-staging'), static::getTaskTitle()));
            return $this->generateResponse(false);
        }

        $this->prepareCleaningMedia();

        $excludedPaths = [
            rtrim($this->directory->getPluginUploadsDirectory(), '/')
        ];

        if ($this->isMainSite() &&  $this->jobDataDto->getBackupMetadata()->getBackupType() !== BackupMetadata::BACKUP_TYPE_MULTISITE) {
            $excludedPaths[] = rtrim($this->directory->getMainSiteUploadsDirectory(), '/') . '/sites';
        }

        $excludedPaths = array_merge($excludedPaths, Hooks::applyFilters(self::FILTER_EXCLUDE_MEDIA_DURING_CLEANUP, []));

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

    /**
     * Should only be true when multisite and blog id of subsite is 1
     *
     * @return bool
     */
    protected function isMainSite(): bool
    {
        if (!is_multisite()) {
            return false;
        }

        return get_current_blog_id() === 1;
    }
}
