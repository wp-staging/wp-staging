<?php

namespace WPStaging\Backup;

use WPStaging\Framework\DI\FeatureServiceProvider;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Queue\FileSeekableQueue;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Backup\Ajax\Backup\PrepareBackup;
use WPStaging\Backup\Ajax\Restore\LoginUrl;
use WPStaging\Backup\Ajax\Restore\PrepareRestore;
use WPStaging\Backup\Ajax\Restore\ReadBackupMetadata;
use WPStaging\Backup\Ajax\ScheduleList;
use WPStaging\Backup\Ajax\Cancel;
use WPStaging\Backup\Ajax\Backup;
use WPStaging\Backup\Ajax\Delete;
use WPStaging\Backup\Ajax\Edit;
use WPStaging\Backup\Ajax\Listing;
use WPStaging\Backup\Ajax\Restore;
use WPStaging\Backup\Ajax\FileInfo;
use WPStaging\Backup\Ajax\FileList;
use WPStaging\Backup\Ajax\MemoryExhaust;
use WPStaging\Backup\Ajax\Parts;
use WPStaging\Backup\Ajax\Status;
use WPStaging\Backup\Ajax\Upload;
use WPStaging\Backup\Request\Logs;
use WPStaging\Backup\Service\BackupAssets;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Service\Database\Importer\Insert\ExtendedInserterWithoutTransaction;
use WPStaging\Backup\Service\Database\Importer\Insert\QueryInserter;
use WPStaging\Backup\Task\AbstractTask;

class BackupServiceProvider extends FeatureServiceProvider
{
    /**
     * Toggle the experimental backup feature on/off.
     * Used only for developers of WP STAGING while the backups feature is being developed.
     * Do not turn this on unless you know what you're doing, as it might irreversibly delete
     * files, databases, etc.
     */
/*    public static function getFeatureTrigger()
    {
        return 'WPSTG_FEATURE_ENABLE_BACKUP';
    }*/

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

        $this->container->make(BackupDownload::class)->listenDownload();

        $this->hookDatabaseImporterQueryInserter();
    }

    protected function addHooks()
    {
        $this->enqueueAjaxListeners();

        $this->enqueueBackupScripts();

        add_action('wpstg_weekly_event', [$this, 'createBackupsDirectory'], 25, 0);

        add_action('wp_login', $this->container->callback(AfterRestore::class, 'loginAfterRestore'), 10, 0);
    }

    protected function enqueueAjaxListeners()
    {
        add_action('wp_ajax_wpstg--backups--prepare-backup', $this->container->callback(PrepareBackup::class, 'ajaxPrepare')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--create', $this->container->callback(Backup::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        add_action('wp_ajax_wpstg--backups--prepare-restore', $this->container->callback(PrepareRestore::class, 'ajaxPrepare')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--restore', $this->container->callback(Restore::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        add_action('wp_ajax_wpstg--backups--read-backup-metadata', $this->container->callback(ReadBackupMetadata::class, 'ajaxPrepare')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        add_action('wp_ajax_wpstg--backups--listing', $this->container->callback(Listing::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--delete', $this->container->callback(Delete::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--cancel', $this->container->callback(Cancel::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--edit', $this->container->callback(Edit::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--parts', $this->container->callback(Parts::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--status', $this->container->callback(Status::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--restore--file-list', $this->container->callback(FileList::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--restore--file-info', $this->container->callback(FileInfo::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--restore--file-upload', $this->container->callback(Upload::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--uploads-delete-unfinished', $this->container->callback(Upload::class, 'ajaxDeleteIncompleteUploads')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_raw_wpstg--backups--login-url', $this->container->callback(LoginUrl::class, 'getLoginUrl')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--detect-memory-exhaust', $this->container->callback(MemoryExhaust::class, 'ajaxResponse')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        // Nopriv
        add_action('wp_ajax_nopriv_wpstg--backups--restore', $this->container->callback(Restore::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_nopriv_wpstg--backups--status', $this->container->callback(Status::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_nopriv_raw_wpstg--backups--login-url', $this->container->callback(LoginUrl::class, 'getLoginUrl')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        add_action('wpstg_create_cron_backup', $this->container->callback(BackupScheduler::class, 'createCronBackup'), 10, 1);
        add_action('wp_ajax_wpstg--backups-dismiss-schedule', $this->container->callback(BackupScheduler::class, 'dismissSchedule'), 10, 1); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups-fetch-schedules', $this->container->callback(ScheduleList::class, 'renderScheduleList'), 10, 1); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        add_action("admin_post_wpstg--backups--logs", $this->container->callback(Logs::class, 'download')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        // Event that we can run on daily basis to repair any corrupted backup create cron jobs
        add_action('wpstg_daily_event', $this->container->callback(BackupScheduler::class, 'reCreateCron'), 10, 0);
    }

    protected function hookDatabaseImporterQueryInserter()
    {
        $this->container->bind(QueryInserter::class, ExtendedInserterWithoutTransaction::class);
    }

    protected function enqueueBackupScripts()
    {
        add_action('wpstg_enqueue_backup_scripts', $this->container->callback(BackupAssets::class, 'register'));
    }
}
