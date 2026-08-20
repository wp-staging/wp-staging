<?php







namespace WPStaging\Backup\BackgroundProcessing\Backup;

use WPStaging\Backup\Ajax\Backup\PrepareBackup as AjaxPrepareBackup;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Job\JobBackupProvider;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\Job\PrepareJob;
use WPStaging\Framework\BackgroundProcessing\Queue;
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
            $prepareBackup->prepare($args);
            $this->job = $prepareBackup->getJob();
        } else {
            $this->job =  WPStaging::make(JobBackupProvider::class)->getJob();
        }
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
