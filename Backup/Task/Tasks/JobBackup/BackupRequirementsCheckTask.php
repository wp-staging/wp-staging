<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use RuntimeException;
use WPStaging\Backend\Modules\SystemInfo;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Analytics\Actions\AnalyticsBackupCreate;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Exceptions\DiskNotWritableException;
use WPStaging\Backup\Service\Compressor;
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

    /** @var Compressor */
    private $compressor;

    /** @var SystemInfo */
    protected $systemInfo;

    public function __construct(
        Directory $directory,
        LoggerInterface $logger,
        Cache $cache,
        StepsDto $stepsDto,
        SeekableQueueInterface $taskQueue,
        DiskWriteCheck $diskWriteCheck,
        AnalyticsBackupCreate $analyticsBackupCreate,
        BackupScheduler $backupScheduler,
        Compressor $compressor,
        SystemInfo $systemInfo
    ) {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->directory = $directory;
        $this->diskWriteCheck = $diskWriteCheck;
        $this->analyticsBackupCreate = $analyticsBackupCreate;
        $this->backupScheduler = $backupScheduler;
        $this->compressor = $compressor;
        $this->systemInfo = $systemInfo;
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

        $isMultipartBackup = $this->jobDataDto->getIsMultipartBackup() ? 'Yes' : 'No';
        $this->logger->info(sprintf(__('Is Multipart Backup: %s ', 'wp-staging'), esc_html($isMultipartBackup)));

        $this->addBackupSettingsToLogs();

        $this->logger->info(__('Backup requirements passed...', 'wp-staging'));

        $this->backupScheduler->maybeDeleteOldBackups($this->jobDataDto);

        $this->maybeCreateMainIndexFile();

        return $this->generateResponse();
    }

    protected function shouldWarnIfRunning32Bits()
    {
        if (PHP_INT_SIZE === 4) {
            $this->logger->warning(__('You are running a 32-bit version of PHP. 32-bits PHP can\'t handle backups larger than 2GB. You might face a critical error. Consider upgrading to 64-bit.', 'wp-staging'));
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

        $this->compressor->setCategory('', $create = true);
    }

    /**
     * @return void
     */
    protected function addBackupSettingsToLogs()
    {
        $this->logInformation('Backup Contents', $this->getBackupContents());

        $this->logInformation('Advanced Exclude Options', $this->getSmartExclusion());

        $this->logInformation('Schedule Options', $this->getBackupScheduleOptions());

        $this->logInformation('Storages', $this->jobDataDto->getStorages());
    }

    /**
     * @return array
     */
    private function getBackupContents(): array
    {
        return [
            'Media Library'             => $this->jobDataDto->getIsExportingUploads(),
            'Themes'                    => $this->jobDataDto->getIsExportingThemes(),
            'Must-Use Plugins'          => $this->jobDataDto->getIsExportingMuPlugins(),
            'Plugins'                   => $this->jobDataDto->getIsExportingPlugins(),
            'Other Files In wp-content' => $this->jobDataDto->getIsExportingOtherWpContentFiles(),
            'Database'                  => $this->jobDataDto->getIsExportingDatabase(),
        ];
    }

    /**
     * @return array
     */
    private function getSmartExclusion(): array
    {
        $smartExclusion = ['No'];
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
        $scheduleOptions = ['No'];
        if ($this->jobDataDto->getRepeatBackupOnSchedule()) {
            $scheduleOptions = [
                'Recurrence' => $this->jobDataDto->getScheduleRecurrence(),
                'Time'       => $this->jobDataDto->getScheduleTime(),
                'Retention'  => $this->jobDataDto->getScheduleRotation(),
                'Launch Now' => $this->jobDataDto->getIsCreateScheduleBackupNow(),
            ];
        }

        return $scheduleOptions;
    }

    /**
     * @param string message
     * @param array data
     *
     * @return void
     */
    private function logInformation(string $message, array $data)
    {
        $this->logger->info(sprintf(esc_html('%s: %s'), $message, wp_json_encode($data)));
    }
}
