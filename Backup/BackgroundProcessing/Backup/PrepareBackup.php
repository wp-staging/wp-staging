<?php







namespace WPStaging\Backup\BackgroundProcessing\Backup;

use UnexpectedValueException;
use WP_Error;
use WPStaging\Backup\Ajax\Backup\PrepareBackup as AjaxPrepareBackup;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Job\JobBackupProvider;
use WPStaging\Backup\Job\Jobs\JobBackup;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\Job\PrepareJob;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Utils\Times;

use function WPStaging\functions\debug_log;






class PrepareBackup extends PrepareJob
{











    public function __construct(AjaxPrepareBackup $ajaxPrepareBackup, Queue $queue, ProcessLock $processLock, Times $times)
    {
        parent::__construct($ajaxPrepareBackup, $queue, $processLock, $times);
    }





    public function getDefaultDataConfiguration(): array
    {
        return [
            'isExportingPlugins'             => true,
            'isExportingMuPlugins'           => true,
            'isExportingThemes'              => true,
            'isExportingUploads'             => true,
            'isExportingOtherWpContentFiles' => true,
            'isExportingOtherWpRootFiles'    => false, 
            'isExportingDatabase'            => true,
            'isAutomatedBackup'              => true,
 
            'repeatBackupOnSchedule'         => false,
            'sitesToBackup'                  => [],
            'storages'                       => ['localStorage'],
            'isInit'                         => true,
            'isSmartExclusion'               => false,
            'isExcludingSpamComments'        => false,
            'isExcludingPostRevision'        => false,
            'isExcludingDeactivatedPlugins'  => false,
            'isExcludingUnusedThemes'        => false,
            'isExcludingLogs'                => false,
            'isExcludingCaches'              => false,
            'backupType'                     => is_multisite() ? BackupMetadata::BACKUP_TYPE_MULTISITE : BackupMetadata::BACKUP_TYPE_SINGLE,
            'subsiteBlogId'                  => null,
            'backupExcludedDirectories'      => '',
            "isValidateBackupFiles"          => false,
        ];
    }

    protected function maybeInitJob(array $args)
    {
        if ($args['isInit']) {
            debug_log('[Background Job] Initiating Backup Job', 'info', false);
            $prepareBackup = WPStaging::make(AjaxPrepareBackup::class);
            $prepareBackup->setQueueId(empty($args['jobId']) ? '' : $args['jobId']);
            $preparedBackup = $prepareBackup->prepare($args);
            if ($preparedBackup instanceof WP_Error) {
                $this->job = $this->getJobToRecordFailedPreparationAgainst($args);
                throw new UnexpectedValueException($preparedBackup->get_error_message());
            }

            $this->job = $prepareBackup->getJob();
        } else {
            $this->job =  WPStaging::make(JobBackupProvider::class)->getJob();
        }
    }







    private function getJobToRecordFailedPreparationAgainst(array $args): JobBackup
    {
 
        $job = WPStaging::make(JobBackupProvider::class)->getJob();
 
        $jobDataDto = $job->getJobDataDto();
        $jobDataDto->setScheduleId(empty($args['scheduleId']) ? null : (string)$args['scheduleId']);

        if (empty($args['isSyncRequest'])) {
            $job->getTransientCache()->startJob((string)$args['jobId'], esc_html__('Backup in Progress', 'wp-staging'), JobTransientCache::JOB_TYPE_BACKUP, (string)$args['jobId']);
        }

        return $job;
    }

    protected function getIsBackupJob(): bool
    {
        return true;
    }

    protected function getJobDefaultName(): string
    {
        return 'Backup';
    }
}
