<?php

/**
 * Prepares a Backup (Backup) to be executed using Background Processing.
 *
 * @package WPStaging\Backup\BackgroundProcessing\Backup
 */

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

/**
 * Class PrepareBackup
 *
 * @package WPStaging\Backup\BackgroundProcessing\Backup
 */
class PrepareBackup extends PrepareJob
{
    /**
     * PrepareBackup constructor.
     *
     * @param AjaxPrepareBackup $ajaxPrepareBackup A reference to the object currently handling
     *                                             AJAX Backup preparation requests.
     * @param Queue             $queue             A reference to the instance of the Queue manager the class
     *                                             should use for processing.
     * @param ProcessLock       $processLock       A reference to the Process Lock manager the class should use
     *                                             to prevent concurrent processing of the job requests.
     * @param Times             $times             A reference to the Times utility class.
     */
    public function __construct(AjaxPrepareBackup $ajaxPrepareBackup, Queue $queue, ProcessLock $processLock, Times $times)
    {
        parent::__construct($ajaxPrepareBackup, $queue, $processLock, $times);
    }

    /**
     * Returns the default data configuration that will be used to prepare a Backup using
     * default settings.
     */
    public function getDefaultDataConfiguration(): array
    {
        return [
            'isExportingPlugins'             => true,
            'isExportingMuPlugins'           => true,
            'isExportingThemes'              => true,
            'isExportingUploads'             => true,
            'isExportingOtherWpContentFiles' => true,
            'isExportingOtherWpRootFiles'    => false, //do not backup wp root files by default.
            'isExportingDatabase'            => true,
            'isAutomatedBackup'              => true,
            // Prevent this scheduled backup from generating another schedule.
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
            debug_log('[Schedule] Configuring JOB DATA DTO', 'info', false);
            $prepareBackup = WPStaging::make(AjaxPrepareBackup::class);
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
        return 'Backup Creation';
    }
}
