<?php

namespace WPStaging\Staging\Tasks\StagingSiteCreate;

use RuntimeException;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Analytics\Actions\AnalyticsStagingCreate;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Staging\Dto\Job\StagingSiteCreateDataDto;
use WPStaging\Staging\Tasks\StagingTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class CreateRequirementsCheckTask extends StagingTask
{
    /** @var Directory */
    protected $directory;

    /** @var Database */
    protected $database;

    /** @var Filesystem */
    protected $filesystem;

    /** @var DiskWriteCheck */
    protected $diskWriteCheck;

    /** @var AnalyticsStagingCreate */
    protected $analyticsStagingCreate;

    /** @var SystemInfo */
    protected $systemInfo;

    /** @var StagingSiteCreateDataDto $jobDataDto */
    protected $jobDataDto;

    public function __construct(
        Directory $directory,
        Database $database,
        Filesystem $filesystem,
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue,
        DiskWriteCheck $diskWriteCheck,
        AnalyticsStagingCreate $analyticsStagingCreate,
        SystemInfo $systemInfo
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->directory              = $directory;
        $this->filesystem             = $filesystem;
        $this->diskWriteCheck         = $diskWriteCheck;
        $this->analyticsStagingCreate = $analyticsStagingCreate;
        $this->systemInfo             = $systemInfo;
        $this->database               = $database;
    }

    public static function getTaskName()
    {
        return 'staging_site_create_requirements_check';
    }

    public static function getTaskTitle()
    {
        return 'Requirements Check';
    }

    public function execute()
    {
        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setTotal(1);
        }

        try {
            $this->logger->info('#################### Start Staging Site Create Job ####################');
            $this->logger->writeLogHeader();
            $this->logger->writeInstalledPluginsAndThemes();
            $this->cannotCreateStagingSiteOnMultisite();
            $this->cannotCreateIfCantWriteToDisk();
            $this->cannotCreateStagingDirectory();
            $this->cannotCreateIfPrefixContainsInvalidCharacter();
            $this->cannotCreateIfUsingExternalDatabase();
            $this->cannotCreateIfStagingPrefixSameAsProductionSite();
        } catch (RuntimeException $e) {
            $this->analyticsStagingCreate->enqueueFinishEvent($this->jobDataDto->getId(), $this->jobDataDto);
            $this->logger->critical($e->getMessage());

            return $this->generateResponse(false);
        }

        $this->logger->info('Staging Site creation requirements passed...');

        return $this->generateResponse();
    }

    protected function cannotCreateStagingSiteOnMultisite()
    {
        if (is_multisite()) {
            throw new RuntimeException(__('Basic version doesn\'t support staging feature on multisite.', 'wp-staging'));
        }
    }

    protected function cannotCreateIfCantWriteToDisk()
    {
        try {
            $this->diskWriteCheck->testDiskIsWriteable();
        } catch (DiskNotWritableException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    protected function cannotCreateStagingDirectory()
    {
        $stagingSitePath = $this->jobDataDto->getStagingSitePath();
        if (!is_dir($stagingSitePath) && !$this->filesystem->mkdir($stagingSitePath)) {
            throw new RuntimeException(esc_html__('Cannot create staging site. Staging site directory does not exist and cannot be created!', 'wp-staging'));
        }

        if (!$this->filesystem->isEmptyDir($stagingSitePath)) {
            throw new RuntimeException(esc_html__('Cannot create staging site. Staging site directory is not empty!', 'wp-staging'));
        }
    }

    protected function cannotCreateIfPrefixContainsInvalidCharacter()
    {
        $stagingSitePrefix = $this->jobDataDto->getDatabasePrefix();
        if (preg_match('|[^a-z0-9_]|i', $stagingSitePrefix)) {
            throw new RuntimeException(esc_html__("Staging site prefix contains invalid character(s). Use different prefix with valid characters.", 'wp-staging'));
        }
    }

    protected function cannotCreateIfUsingExternalDatabase()
    {
        $isUsingExternalDatabase = false;
        if ($isUsingExternalDatabase) {
            throw new RuntimeException(esc_html__('Staging site creation with external database is not supported in the basic version.', 'wp-staging'));
        }
    }

    protected function cannotCreateIfStagingPrefixSameAsProductionSite()
    {
        $isSamePrefix = $this->database->getBasePrefix() === $this->jobDataDto->getDatabasePrefix();
        if ($isSamePrefix) {
            throw new RuntimeException(esc_html__('Staging site prefix is same as production site prefix. Use different prefix for staging site.', 'wp-staging'));
        }
    }
}
