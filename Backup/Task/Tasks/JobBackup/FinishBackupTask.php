<?php

// TODO PHP7.1; constant visibility

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use RuntimeException;
use WPStaging\Backup\BackgroundProcessing\Backup\PrepareBackup;
use WPStaging\Framework\Analytics\Actions\AnalyticsBackupCreate;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Traits\EventLoggerTrait;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Backup\Dto\Task\Backup\Response\FinalizeBackupResponseDto;
use WPStaging\Backup\Entity\ListableBackup;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Core\WPStaging;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\TransientCache;

use function WPStaging\functions\debug_log;

class FinishBackupTask extends BackupTask
{
    use EventLoggerTrait;

    /** @var string */
    const OPTION_LAST_BACKUP = 'wpstg_last_backup_info';

    /** @var AnalyticsBackupCreate */
    protected $analyticsBackupCreate;

    /** @var TransientCache */
    protected $transientCache;

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, AnalyticsBackupCreate $analyticsBackupCreate, TransientCache $transientCache)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->analyticsBackupCreate = $analyticsBackupCreate;
        $this->transientCache        = $transientCache;
    }

    public static function getTaskName(): string
    {
        return 'backup_finish';
    }

    public static function getTaskTitle(): string
    {
        return 'Finalizing Backup';
    }

    /**
     * @return FinalizeBackupResponseDto|TaskResponseDto
     */
    public function execute()
    {
        $backupFilePath = $this->jobDataDto->getBackupFilePath();

        $this->analyticsBackupCreate->enqueueFinishEvent($this->jobDataDto->getId(), $this->jobDataDto);

        $this->logger->info("################## FINISH ##################");

        // This condition prevents duplicate log entries for a single backup process.
        // For example, in background (BG) backups, this task run twice, so we log it only once after the process completes.
        if (!$this->jobDataDto->getIsCreateBackupInBackground()) {
            $this->logBackupProcessCompleted($this->getBackupCreationPrepareData());
            $this->saveCloudStorageOptions();
        }

        $this->maybeTriggerBackupCreationInBackground();

        $this->stepsDto->finish();

        $this->jobDataDto->setEndTime(time());

        update_option(static::OPTION_LAST_BACKUP, [
            'endTime'          => time(), // Unix timestamp is timezone independent
            'duration'         => $this->jobDataDto->getDuration(),
            'JobBackupDataDto' => $this->jobDataDto,
        ], false);

        // Delete the transient cache for the backup file index to make sure it is checked again now
        $this->transientCache->delete(TransientCache::KEY_INVALID_BACKUP_FILE_INDEX);

        return $this->overrideGenerateResponse($this->makeListableBackup($backupFilePath));
    }

    /**
     * @param null|ListableBackup $backup
     *
     * @return FinalizeBackupResponseDto|TaskResponseDto
     */
    private function overrideGenerateResponse($backup = null)
    {
        add_filter('wpstg.task.response', function ($response) use ($backup) {

            $md5 = $backup ? $backup->md5BaseName : null;
            if ($this->jobDataDto->getIsMultipartBackup()) {
                $md5 = $this->getPartsMd5();
            }

            if ($response instanceof FinalizeBackupResponseDto) {
                $response->setBackupMd5($md5);
                $response->setBackupSize($backup ? size_format($backup->size) : null);
                $response->setIsLocalBackup($this->jobDataDto->isLocalBackup());
                $response->setIsMultipartBackup($this->jobDataDto->getIsMultipartBackup());
            } else {
                debug_log('Fail to finalize response for backup process! Response content: ' . print_r($response, true));
            }

            return $response;
        });

        return $this->generateResponse();
    }

    /**
     * Retains backups, if at least one remote storage is set.
     *
     * @return void
     */
    protected function saveCloudStorageOptions()
    {
        // Used in PRO version
    }

    protected function getResponseDto(): FinalizeBackupResponseDto
    {
        return new FinalizeBackupResponseDto();
    }

    /**
     * This is used to display the "Download Modal" after the backup completes.
     *
     * @param string|null $backupFilePath
     *
     * @return ListableBackup
     * @see string src/Backend/public/js/wpstg-admin.js, search for "wpstg--backups--backup"
     *
     */
    protected function makeListableBackup($backupFilePath): ListableBackup
    {
        clearstatcache();
        $backupFilePath      = (string)$backupFilePath;
        $backup              = new ListableBackup();
        $backup->md5BaseName = md5(basename($backupFilePath));
        $backup->size        = filesize($backupFilePath);

        return $backup;
    }

    /**
     * @return string[]
     */
    protected function getPartsMd5(): array
    {
        $md5 = [];
        foreach ($this->jobDataDto->getMultipartFilesInfo() as $multipartInfo) {
            $md5[] = md5($multipartInfo['destination']);
        }

        return $md5;
    }

    /**
     * @return void
     * @throws RuntimeException
     */
    protected function maybeTriggerBackupCreationInBackground()
    {
        if (!$this->jobDataDto->getIsCreateBackupInBackground()) {
            return;
        }

        $data  = $this->getBackupCreationPrepareData();
        $jobId = WPStaging::make(PrepareBackup::class)->prepare($data);

        if ($jobId instanceof \WP_Error) {
            throw new RuntimeException('Failed to trigger Backup creation in background: ' . $jobId->get_error_message());
        } else {
            $this->logger->info('Backup creation triggered in background with job ID: ' . $jobId);
        }
    }

    /**
     * @return array
     */
    protected function getBackupCreationPrepareData(): array
    {
        $jobBackupDataDto = $this->jobDataDto;

        return [
            'name'                           => $jobBackupDataDto->getName(),
            'isExportingPlugins'             => $jobBackupDataDto->getIsExportingPlugins(),
            'isExportingMuPlugins'           => $jobBackupDataDto->getIsExportingMuPlugins(),
            'isExportingThemes'              => $jobBackupDataDto->getIsExportingThemes(),
            'isExportingUploads'             => $jobBackupDataDto->getIsExportingUploads(),
            'isExportingOtherWpContentFiles' => $jobBackupDataDto->getIsExportingOtherWpContentFiles(),
            'isExportingDatabase'            => $jobBackupDataDto->getIsExportingDatabase(),
            'sitesToBackup'                  => $jobBackupDataDto->getSitesToBackup(),
            'storages'                       => $jobBackupDataDto->getStorages(),
            'isSmartExclusion'               => $jobBackupDataDto->getIsSmartExclusion(),
            'isExcludingSpamComments'        => $jobBackupDataDto->getIsExcludingSpamComments(),
            'isExcludingPostRevision'        => $jobBackupDataDto->getIsExcludingPostRevision(),
            'isExcludingDeactivatedPlugins'  => $jobBackupDataDto->getIsExcludingDeactivatedPlugins(),
            'isExcludingUnusedThemes'        => $jobBackupDataDto->getIsExcludingUnusedThemes(),
            'isExcludingLogs'                => $jobBackupDataDto->getIsExcludingLogs(),
            'isExcludingCaches'              => $jobBackupDataDto->getIsExcludingCaches(),
            'isExportingOtherWpRootFiles'    => $jobBackupDataDto->getIsExportingOtherWpRootFiles(),
            'isWpCliRequest'                 => true, // should be true otherwise multisite backup will not work
            'repeatBackupOnSchedule'         => false,
            'isCreateBackupInBackground'     => false,
            'isAutomatedBackup'              => false,
        ];
    }
}
