<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use RuntimeException;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Backup\Storage\Providers;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Analytics\Actions\AnalyticsBackupCreate;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Backup\Service\Archiver;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class BackupRequirementsCheckTask extends BackupTask
{
    /** @var Directory */
    protected $directory;

    /** @var DiskWriteCheck */
    protected $diskWriteCheck;

    /** @var AnalyticsBackupCreate */
    protected $analyticsBackupCreate;

    /** @var BackupScheduler */
    protected $backupScheduler;

    /** @var Archiver */
    private $archiver;

    /** @var SystemInfo */
    protected $systemInfo;

    /** @var Providers */
    protected $providers;

    public function __construct(
        Directory $directory,
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue,
        DiskWriteCheck $diskWriteCheck,
        AnalyticsBackupCreate $analyticsBackupCreate,
        BackupScheduler $backupScheduler,
        Archiver $archiver,
        SystemInfo $systemInfo,
        Providers $providers
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->directory             = $directory;
        $this->diskWriteCheck        = $diskWriteCheck;
        $this->analyticsBackupCreate = $analyticsBackupCreate;
        $this->backupScheduler       = $backupScheduler;
        $this->archiver              = $archiver;
        $this->systemInfo            = $systemInfo;
        $this->providers             = $providers;
    }

    public static function getTaskName()
    {
        return 'backup_site_requirements_check';
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
            $this->logger->info('#################### Start Backup Job ####################');
            $this->logger->writeLogHeader();
            $this->logger->writeInstalledPluginsAndThemes();
            $this->cannotBackupMultisite();
            $this->shouldWarnIfRunning32Bits();
            $this->cannotBackupWithNoStorage();
            $this->cannotBackupEmptyBackup();
            $this->cannotRestoreIfCantWriteToDisk();
            $this->checkFilesystemPermissions();
        } catch (RuntimeException $e) {
            // todo: Set the requirement check fail reason
            $this->analyticsBackupCreate->enqueueFinishEvent($this->jobDataDto->getId(), $this->jobDataDto);
            $this->logger->critical($e->getMessage());

            return $this->generateResponse(false);
        }

        $this->addBackupSettingsToLogs();

        $this->logger->info('Backup requirements passed...');

        $this->backupScheduler->maybeDeleteOldBackups($this->jobDataDto);

        $this->maybeCreateMainIndexFile();

        return $this->generateResponse();
    }

    protected function shouldWarnIfRunning32Bits()
    {
        if (PHP_INT_SIZE === 4) {
            $this->logger->warning('You are running a 32-bit version of PHP. 32-bits PHP can\'t handle backups larger than 2GB. You might face a critical error. Consider upgrading to 64-bit.');
        }
    }

    protected function cannotBackupMultisite()
    {
        if (is_multisite()) {
            throw new RuntimeException(__('Basic version doesn\'t support multisite backups.', 'wp-staging'));
        }
    }

    protected function cannotBackupWithNoStorage()
    {
        if (empty($this->jobDataDto->getStorages())) {
            throw new RuntimeException(__('You must select at least one storage.', 'wp-staging'));
        }
    }

    protected function cannotBackupEmptyBackup()
    {
        if (
            !$this->jobDataDto->getIsExportingDatabase()
            && !$this->jobDataDto->getIsExportingPlugins()
            && !$this->jobDataDto->getIsExportingUploads()
            && !$this->jobDataDto->getIsExportingMuPlugins()
            && !$this->jobDataDto->getIsExportingThemes()
            && !$this->jobDataDto->getIsExportingOtherWpContentFiles()
            && !$this->jobDataDto->getIsExportingOtherWpRootFiles()
        ) {
            throw new RuntimeException(__('You must select at least one item to backup.', 'wp-staging'));
        }
    }

    protected function cannotRestoreIfCantWriteToDisk()
    {
        try {
            $this->diskWriteCheck->testDiskIsWriteable();
        } catch (DiskNotWritableException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * @throws RuntimeException When PHP does not have enough permission to a required directory.
     */
    protected function checkFilesystemPermissions()
    {
        clearstatcache();

        if (!is_writable($this->directory->getPluginUploadsDirectory())) {
            throw new RuntimeException(sprintf(__('PHP does not have enough permission to write to the WP STAGING data directory: %s', 'wp-staging'), $this->directory->getPluginUploadsDirectory()));
        }

        if ($this->jobDataDto->getIsExportingPlugins()) {
            if (!is_readable($this->directory->getPluginsDirectory())) {
                throw new RuntimeException(sprintf(__('PHP does not have enough permission to read the plugins directory: %s', 'wp-staging'), $this->directory->getPluginsDirectory()));
            }
        }

        if ($this->jobDataDto->getIsExportingThemes()) {
            foreach ($this->directory->getAllThemesDirectories() as $themesDirectory) {
                if (!is_readable($themesDirectory)) {
                    throw new RuntimeException(sprintf(__('PHP does not have enough permission to read a themes directory: %s', 'wp-staging'), $themesDirectory));
                }
            }
        }

        if ($this->jobDataDto->getIsExportingMuPlugins()) {
            if (!is_readable($this->directory->getMuPluginsDirectory()) && !wp_mkdir_p($this->directory->getMuPluginsDirectory())) {
                throw new RuntimeException(sprintf(__('PHP does not have enough permission to read the mu-plugins directory: %s', 'wp-staging'), $this->directory->getMuPluginsDirectory()));
            }
        }

        if ($this->jobDataDto->getIsExportingUploads()) {
            if (!is_readable($this->directory->getUploadsDirectory())) {
                throw new RuntimeException(sprintf(__('PHP does not have enough permission to read the uploads directory: %s', 'wp-staging'), $this->directory->getUploadsDirectory()));
            }
        }

        if ($this->jobDataDto->getIsExportingOtherWpContentFiles()) {
            if (!is_readable($this->directory->getWpContentDirectory())) {
                throw new RuntimeException(sprintf(__('PHP does not have enough permission to read the wp-content directory: %s', 'wp-staging'), $this->directory->getWpContentDirectory()));
            }
        }

        if ($this->jobDataDto->getIsExportingOtherWpRootFiles()) {
            if (!is_readable($this->directory->getAbsPath())) {
                throw new RuntimeException(sprintf(__('PHP does not have enough permission to read the WordPress root directory: %s', 'wp-staging'), $this->directory->getAbsPath()));
            }
        }
    }

    protected function maybeCreateMainIndexFile()
    {
        // Early Bail: No need to create a index file it is only a schedule
        if ($this->jobDataDto->getRepeatBackupOnSchedule() && !$this->jobDataDto->getIsCreateScheduleBackupNow()) {
            return;
        }

        // Early Bail: if not split backup
        if (!$this->jobDataDto->getIsMultipartBackup()) {
            return;
        }

        $this->archiver->createArchiveFile(Archiver::CREATE_BINARY_HEADER);
    }

    /**
     * @return void
     */
    protected function addBackupSettingsToLogs()
    {
        $this->logger->info('Backup Settings');
        $this->logInformation($this->getBackupContents());
        $this->logInformation($this->getSmartExclusion());
        $this->logger->add('- Backup Run in Background : ' . ($this->jobDataDto->getIsCreateBackupInBackground() ?  'True' : 'False'), Logger::TYPE_INFO_SUB);
        $this->logger->add('- Backup Validate : ' . ($this->jobDataDto->getIsValidateBackupFiles() ? 'True' : 'False'), Logger::TYPE_INFO_SUB);
        $this->logInformation($this->getBackupScheduleOptions());
        $this->logger->add('- Is Multipart Backup: ' . ($this->jobDataDto->getIsMultipartBackup() ? 'Yes' : 'No'), Logger::TYPE_INFO_SUB);
        $this->logger->add('- Storages : ' . implode(', ', $this->jobDataDto->getStorages()), Logger::TYPE_INFO_SUB);
        $this->logger->add(sprintf('- Backup Format: %s', $this->jobDataDto->getIsBackupFormatV1() ? 'v1' : 'v2'), Logger::TYPE_INFO_SUB);
        $this->writeCloudServiceSettingsToLogs();
    }

    /**
     * @return array
     */
    private function getBackupContents(): array
    {
        return [
            'Backup Media Library'             => $this->jobDataDto->getIsExportingUploads(),
            'Backup Themes'                    => $this->jobDataDto->getIsExportingThemes(),
            'Backup Must-Use Plugins'          => $this->jobDataDto->getIsExportingMuPlugins(),
            'Backup Plugins'                   => $this->jobDataDto->getIsExportingPlugins(),
            'Backup Other Files In wp-content' => $this->jobDataDto->getIsExportingOtherWpContentFiles(),
            'Backup Other wp root folders'     => $this->jobDataDto->getIsExportingOtherWpRootFiles(),
            'Backup Database'                  => $this->jobDataDto->getIsExportingDatabase(),
        ];
    }

    /**
     * @return array
     */
    private function getSmartExclusion(): array
    {
        $smartExclusion = [
            'Add Exclusions' => $this->jobDataDto->getIsSmartExclusion() ? 'True' : 'False',
            ];

        if ($this->jobDataDto->getIsSmartExclusion()) {
            $smartExclusion = [
                'Exclude log files'           => $this->jobDataDto->getIsExcludingLogs(),
                'Exclude cache files'         => $this->jobDataDto->getIsExcludingCaches(),
                'Exclude post revisions'      => $this->jobDataDto->getIsExcludingPostRevision(),
                'Exclude spam comments'       => $this->jobDataDto->getIsExcludingSpamComments(),
                'Exclude unused themes'       => $this->jobDataDto->getIsExcludingUnusedThemes(),
                'Exclude deactivated plugins' => $this->jobDataDto->getIsExcludingDeactivatedPlugins(),
            ];
        }

        return $smartExclusion;
    }

    /**
     * @return array
     */
    private function getBackupScheduleOptions(): array
    {
        $scheduleOptions = [
            'Backup One Time' => $this->jobDataDto->getRepeatBackupOnSchedule() === false ? 'True' : 'False',
        ];

        if ($this->jobDataDto->getRepeatBackupOnSchedule()) {
            $scheduleOptions = [
                'Recurrence' => $this->jobDataDto->getScheduleRecurrence(),
                'Time'       => implode(':', $this->jobDataDto->getScheduleTime()),
                'Retention'  => $this->jobDataDto->getScheduleRotation() ? 'True' : 'False',
                'Launch Now' => $this->jobDataDto->getIsCreateScheduleBackupNow(),
            ];
        }

        return $scheduleOptions;
    }

    /**
     * @param array $data
     * @return void
     */
    private function logInformation(array $data)
    {
        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? 'True' : 'False';
            }

            $this->logger->add(sprintf('- %s : %s', esc_html($key), esc_html($value)), Logger::TYPE_INFO_SUB);
        }
    }

    /**
     * @return void
     */
    private function writeCloudServiceSettingsToLogs()
    {
        foreach ($this->jobDataDto->getStorages() as $storage) {
            if ($storage === 'localStorage') {
                continue;
            }

            $authClass    = $this->providers->getStorageProperty($storage, 'authClass', true);
            $providerName = $this->providers->getStorageProperty($storage, 'name', true);

            if (!$authClass || !class_exists($authClass) || empty($providerName)) {
                continue;
            }

            $this->logger->logProviderSettings($providerName, $authClass);
        }
    }
}
