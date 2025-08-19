<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Ajax\Backup;
use WPStaging\Backup\Ajax\Delete;
use WPStaging\Backup\Ajax\Edit;
use WPStaging\Backup\Ajax\FileInfo;
use WPStaging\Backup\Ajax\Parts;
use WPStaging\Backup\Ajax\BackupSizeCalculator;
use WPStaging\Backup\Ajax\Restore;
use WPStaging\Backup\Ajax\ScheduleList;
use WPStaging\Backup\Ajax\Upload;
use WPStaging\Backup\Ajax\Backup\PrepareBackup;
use WPStaging\Backup\Ajax\BackupDownloader;
use WPStaging\Backup\Ajax\Restore\PrepareRestore;
use WPStaging\Backup\Ajax\Restore\ReadBackupMetadata;
use WPStaging\Backup\Request\Logs;
use WPStaging\Backup\Service\BackupAssets;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Service\Database\Importer\Insert\ExtendedInserterWithoutTransaction;
use WPStaging\Backup\Service\Database\Importer\Insert\QueryInserter;
use WPStaging\Backup\Ajax\BackupSpeedIndex;
use WPStaging\Core\Cron\Cron;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Adapter\DirectoryInterface;
use WPStaging\Framework\DI\FeatureServiceProvider;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\FilesystemScanner;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Job\Task\AbstractTask;
use WPStaging\Framework\Queue\FileSeekableQueue;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Security\Otp\OtpSender;

class BackupServiceProvider extends FeatureServiceProvider
{
    /** @var string */
    const ACTION_BACKUP_ENQUEUE_SCRIPTS = 'wpstg.backup.enqueue_scripts';

    public function createBackupsDirectory()
    {
        $backupsDirectory = $this->container->make(BackupsFinder::class)->getBackupsDirectory();
        $this->container->make(Filesystem::class)->mkdir($backupsDirectory, true);
    }

    protected function registerClasses()
    {
        $this->container->bind(SeekableQueueInterface::class, function () {
            return $this->container->make(FileSeekableQueue::class);
        });

        $this->container->when(AbstractTask::class)
                        ->needs(SeekableQueueInterface::class)
                        ->give(FileSeekableQueue::class);

        $this->container->when(PathIdentifier::class)
                        ->needs(DirectoryInterface::class)
                        ->give(Directory::class);

        $this->container->when(FilesystemScanner::class)
                        ->needs(SeekableQueueInterface::class)
                        ->give(FileSeekableQueue::class);

        $this->hookDatabaseImporterQueryInserter();
    }

    protected function addHooks()
    {
        $this->enqueueAjaxListeners();

        $this->enqueueBackupScripts();

        add_action(Cron::ACTION_WEEKLY_EVENT, [$this, 'createBackupsDirectory'], 25, 0);

        add_action('wp_login', $this->container->callback(AfterRestore::class, 'loginAfterRestore'), 10, 0);
    }

    protected function enqueueAjaxListeners()
    {
        add_action('wp_ajax_wpstg--backups--prepare-backup', $this->container->callback(PrepareBackup::class, 'ajaxPrepare')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--create', $this->container->callback(Backup::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        add_action('wp_ajax_wpstg--backups--prepare-restore', $this->container->callback(PrepareRestore::class, 'ajaxPrepare')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--restore', $this->container->callback(Restore::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        add_action('wp_ajax_wpstg--backups--read-backup-metadata', $this->container->callback(ReadBackupMetadata::class, 'ajaxPrepare')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--delete', $this->container->callback(Delete::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--edit', $this->container->callback(Edit::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--parts', $this->container->callback(Parts::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--restore--file-info', $this->container->callback(FileInfo::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--prepare-upload', $this->container->callback(Upload::class, 'ajaxPrepareUpload')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--restore--file-upload', $this->container->callback(Upload::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--prepare-url-upload', $this->container->callback(BackupDownloader::class, 'ajaxPrepareUpload')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--url-file-upload', $this->container->callback(BackupDownloader::class, 'ajaxDownloadBackupFromRemoteServer'), 10, 0); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--uploads-delete-unfinished', $this->container->callback(Upload::class, 'ajaxDeleteIncompleteUploads')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_calculate_backup_speed_index', $this->container->callback(BackupSpeedIndex::class, 'ajaxMaybeShowModal')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        // TODO: move this to JobServiceProvider once the Staging PR: https://github.com/wp-staging/wp-staging-pro/pull/3738 is merged
        add_action('wp_ajax_wpstg--send--otp', $this->container->callback(OtpSender::class, 'ajaxSendOtp')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        // Nopriv
        add_action('wp_ajax_nopriv_wpstg--backups--restore', $this->container->callback(Restore::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        add_action(Cron::ACTION_CREATE_CRON_BACKUP, $this->container->callback(BackupScheduler::class, 'createCronBackup'), 10, 1);
        add_action('wp_ajax_wpstg--backups-dismiss-schedule', $this->container->callback(BackupScheduler::class, 'dismissSchedule'), 10, 1); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups-fetch-schedules', $this->container->callback(ScheduleList::class, 'renderScheduleList'), 10, 1); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        add_action("admin_post_wpstg--backups--logs", $this->container->callback(Logs::class, 'download')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        // Event that we can run on daily basis to repair any corrupted backup create cron jobs
        add_action(Cron::ACTION_DAILY_EVENT, $this->container->callback(BackupScheduler::class, 'reCreateCron'), 10, 0);
        add_action('wp_ajax_wpstg--backups--calculate-backup-size', $this->container->callback(BackupSizeCalculator::class, 'ajaxCalculateBackupPartsSize')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }

    protected function hookDatabaseImporterQueryInserter()
    {
        $this->container->bind(QueryInserter::class, ExtendedInserterWithoutTransaction::class);
    }

    protected function enqueueBackupScripts()
    {
        add_action(self::ACTION_BACKUP_ENQUEUE_SCRIPTS, $this->container->callback(BackupAssets::class, 'register')); // phpcs:ignore
    }
}
