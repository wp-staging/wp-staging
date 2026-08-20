<?php

 

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
use WPStaging\Backup\BackupScheduler;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Core\WPStaging;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\TransientCache;

class FinishBackupTask extends BackupTask
{
    use EventLoggerTrait;

 
    const OPTION_LAST_BACKUP = 'wpstg_last_backup_info';





    const ACTION_BACKUP_CREATED = 'wpstg.backup.created';

 
    protected $analyticsBackupCreate;

 
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




    public function execute()
    {
        $backupFilePath = $this->jobDataDto->getBackupFilePath();

        $this->logCompressionEntry();

        $this->analyticsBackupCreate->enqueueFinishEvent($this->jobDataDto->getId(), $this->jobDataDto);

        if (!$this->jobDataDto->getIsSyncRequest()) {
            $this->logger->info("✓ Backup successfully created");
        }

 
        if (!$this->jobDataDto->getIsCreateBackupInBackground()) {
            $this->maybeLogBackupProcess();
            $this->saveCloudStorageOptions();
        }

        $this->maybeTriggerBackupCreationInBackground();

        $this->stepsDto->finish();

        $this->jobDataDto->setEndTime(time());

        update_option(static::OPTION_LAST_BACKUP, [
            'endTime'          => time(), 
            'duration'         => $this->jobDataDto->getDuration(),
            'JobBackupDataDto' => $this->jobDataDto,
        ], false);

 
        if ($this->jobDataDto->isScheduledBackup()) {
            delete_option(BackupScheduler::OPTION_LAST_BACKUP_FAILURE);
        }

 
        $this->transientCache->delete(TransientCache::KEY_INVALID_BACKUP_FILE_INDEX);

        $this->performFinishBackupAction();

        do_action(self::ACTION_BACKUP_CREATED, $this->jobDataDto);

        return $this->overrideGenerateResponse($this->makeListableBackup($backupFilePath));
    }




    protected function performFinishBackupAction()
    {
        $this->getJobTransientCache()->completeJob();
    }






    private function overrideGenerateResponse($backup = null)
    {
        add_filter(self::FILTER_TASK_RESPONSE, function ($response) use ($backup) {

            $md5 = $backup ? $backup->md5BaseName : null;
            if ($this->jobDataDto->getIsMultipartBackup()) {
                $md5 = $this->getPartsMd5();
            }

            if ($response instanceof FinalizeBackupResponseDto) {
                $response->setBackupMd5($md5);
                $response->setBackupSize($backup ? $backup->size : null);
                $response->setIsLocalBackup($this->jobDataDto->isLocalBackup());
                $response->setIsMultipartBackup($this->jobDataDto->getIsMultipartBackup());
                $response->setIsGlitchInBackup($this->jobDataDto->getIsGlitchInBackup());
                $response->setGlitchReason($this->jobDataDto->getGlitchReason());
                $response->setIsBeforePush(!empty($this->jobDataDto->getPushPrepareData()));
            }

            return $response;
        });

        return $this->generateResponse();
    }




    protected function logCompressionEntry()
    {
 
    }






    protected function saveCloudStorageOptions()
    {
 
    }

    protected function getResponseDto(): FinalizeBackupResponseDto
    {
        return new FinalizeBackupResponseDto();
    }










    protected function makeListableBackup($backupFilePath): ListableBackup
    {
        clearstatcache();
        $backupFilePath      = (string)$backupFilePath;
        $backup              = new ListableBackup();
        $backup->md5BaseName = md5(basename($backupFilePath));
        $backup->size        = filesize($backupFilePath);

        return $backup;
    }




    protected function getPartsMd5(): array
    {
        $md5 = [];
        foreach ($this->jobDataDto->getMultipartFilesInfo() as $multipartInfo) {
            $md5[] = md5($multipartInfo['destination']);
        }

        return $md5;
    }





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
            $this->logger->info('Backup creation triggered in background with job ID: ' . $jobId . '.');
        }
    }







    private function maybeLogBackupProcess()
    {
        if ($this->jobDataDto->getRepeatBackupOnSchedule() || !empty($this->jobDataDto->getScheduleId())) {
            return;
        }

        $this->logBackupProcessCompleted($this->jobDataDto);
    }




    protected function getBackupCreationPrepareData(): array
    {
        $jobBackupDataDto = $this->jobDataDto;

        return [
            'name'                           => $jobBackupDataDto->getName(),
            'isBeforeUpdateBackup'           => $jobBackupDataDto->getIsBeforeUpdateBackup(),
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
            'isWpCliRequest'                 => true, 
            'repeatBackupOnSchedule'         => false,
            'isCreateBackupInBackground'     => false,
            'isAutomatedBackup'              => false,
        ];
    }
}
