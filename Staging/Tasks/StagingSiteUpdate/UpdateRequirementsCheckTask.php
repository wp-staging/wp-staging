<?php

namespace WPStaging\Staging\Tasks\StagingSiteUpdate;

use RuntimeException;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingUpdate;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Staging\Dto\Job\StagingSiteJobsDataDto;
use WPStaging\Staging\Sites;
use WPStaging\Staging\Tasks\StagingTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class UpdateRequirementsCheckTask extends StagingTask
{
    /** @var Directory */
    protected $directory;

    /** @var Database */
    protected $database;

    /** @var Filesystem */
    protected $filesystem;

    /** @var DiskWriteCheck */
    protected $diskWriteCheck;

    /** @var AnalyticsStagingUpdate */
    protected $analyticsStagingUpdate;

    /** @var SystemInfo */
    protected $systemInfo;

    /** @var StagingSiteJobsDataDto $jobDataDto */
    protected $jobDataDto;

    /** @var Sites */
    protected $sites;

    public function __construct(
        Directory $directory,
        Database $database,
        Filesystem $filesystem,
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue,
        DiskWriteCheck $diskWriteCheck,
        AnalyticsStagingUpdate $analyticsStagingUpdate,
        SystemInfo $systemInfo,
        Sites $sites
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->directory              = $directory;
        $this->filesystem             = $filesystem;
        $this->diskWriteCheck         = $diskWriteCheck;
        $this->analyticsStagingUpdate = $analyticsStagingUpdate;
        $this->systemInfo             = $systemInfo;
        $this->database               = $database;
        $this->sites                  = $sites;
    }

    /**
     * @return string
     */
    public static function getTaskName()
    {
        return 'staging_site_update_requirements_check';
    }

    /**
     * @return string
     */
    public static function getTaskTitle()
    {
        return 'Requirements Check';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute()
    {
        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setTotal(1);
        }

        try {
            $this->logStartHeader();
            $this->logger->writeLogHeader();
            $this->logger->writeInstalledPluginsAndThemes();
            $this->writeStagingSettingsLogs();
            $this->cannotUpdateStagingSiteOnMultisite();
            $this->cannotUpdateIfCantWriteToDisk();
            $this->cannotUpdateIfStagingSiteNoExists();
            $this->cannotUpdateIfUsingExternalDatabase();
            $this->cannotUpdateIfStagingPrefixSameAsProductionSite();
        } catch (RuntimeException $e) {
            $this->analyticsStagingUpdate->enqueueFinishEvent($this->jobDataDto->getId(), $this->jobDataDto);
            $this->logger->critical($e->getMessage());

            return $this->generateResponse(false);
        }

        $this->saveStagingSite();
        $this->logRequirementsCheckPassed();

        return $this->generateResponse();
    }

    /**
     * @return void
     */
    protected function logStartHeader()
    {
        $this->logger->info('#################### Start Staging Site Update Job ####################');
    }

    /**
     * @return void
     */
    protected function logRequirementsCheckPassed()
    {
        $this->logger->info('Staging Site update requirements passed...');
    }

    /**
     * @return void
     */
    protected function saveStagingSite()
    {
        $stagingSites = $this->sites->tryGettingStagingSites();
        $stagingSites[$this->jobDataDto->getCloneId()] = $this->jobDataDto->getStagingSite()->toArray();
        $this->sites->updateStagingSites($stagingSites);
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function cannotUpdateStagingSiteOnMultisite()
    {
        if (is_multisite()) {
            throw new RuntimeException(__('Basic version doesn\'t support staging feature on multisite.', 'wp-staging'));
        }
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function cannotUpdateIfCantWriteToDisk()
    {
        try {
            $this->diskWriteCheck->testDiskIsWriteable();
        } catch (DiskNotWritableException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function cannotUpdateIfStagingSiteNoExists()
    {
        $stagingSitePath = $this->jobDataDto->getStagingSitePath();
        if (!is_dir($stagingSitePath)) {
            throw new RuntimeException(esc_html__('Cannot update staging site. Staging site directory does not exist!', 'wp-staging'));
        }
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function cannotUpdateIfUsingExternalDatabase()
    {
        if ($this->jobDataDto->getIsExternalDatabase()) {
            throw new RuntimeException(esc_html__('Staging site update with external database is not supported in the basic version.', 'wp-staging'));
        }
    }

    protected function cannotUpdateIfStagingPrefixSameAsProductionSite()
    {
        $isSamePrefix = $this->database->getBasePrefix() === $this->jobDataDto->getDatabasePrefix();
        if ($isSamePrefix) {
            throw new RuntimeException(esc_html__('Staging site prefix is same as production site prefix. Use different prefix for staging site.', 'wp-staging'));
        }
    }

    /**
     * @return void
     */
    protected function writeStagingSettingsLogs()
    {
        $this->logger->info('Staging Settings:');
        $this->logger->info('Staging Site Path: ' . $this->jobDataDto->getStagingSitePath());
        $this->logger->info('Staging Site URL: ' . $this->jobDataDto->getStagingSiteUrl());
        $this->logger->info('Database Prefix: ' . $this->jobDataDto->getDatabasePrefix());
        $this->logger->info('Clone ID: ' . $this->jobDataDto->getCloneId());
        $this->logger->info('Clone Name: ' . $this->jobDataDto->getName());
        $this->logger->info('Is External Database: ' . ($this->jobDataDto->getIsExternalDatabase() ? 'Yes' : 'No'));
    }
}
