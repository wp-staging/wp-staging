<?php

namespace WPStaging\Backup\Transfer;

use WPStaging\Backup\BackupDeleter;
use WPStaging\Backup\Security\Ajax\BackupDirectorySecurityController;
use WPStaging\Backup\Security\BackupDirectoryProtectionService;
use WPStaging\Backup\Security\BackupDirectorySecurityCheck;
use WPStaging\Backup\Transfer\Ajax\TransferSessionController;
use WPStaging\Core\Cron\Cron;
use WPStaging\Framework\DI\ServiceProvider;




class TransferServiceProvider extends ServiceProvider
{
 
    const CLEANUP_CRON_SCHEDULE = 'hourly';

 
    protected function registerClasses()
    {
        $this->container->singleton(TransferSessionTable::class);
    }

 
    protected function addHooks()
    {
        $this->enqueueAjaxListeners();
        $this->enqueueCleanupTriggers();

        add_filter(BackupDeleter::FILTER_BEFORE_BACKUP_DELETED, $this->container->callback(TransferSessionService::class, 'deleteTransferArtifactsBeforeBackupDeletion'), 10, 2);

        add_action('admin_init', $this->container->callback(BackupDirectoryProtectionService::class, 'maybeProtect'), 20, 0);
        add_action(Cron::ACTION_WEEKLY_EVENT, $this->container->callback(BackupDirectoryProtectionService::class, 'protect'), 30, 0);
        add_action(Cron::ACTION_DAILY_EVENT, $this->container->callback(BackupDirectorySecurityCheck::class, 'maybeRun'), 30, 0);
    }

 
    public function scheduleCleanupCron()
    {
        if (wp_next_scheduled(TransferCleanupService::CRON_HOOK)) {
            return;
        }

        wp_schedule_event(time() + MINUTE_IN_SECONDS, self::CLEANUP_CRON_SCHEDULE, TransferCleanupService::CRON_HOOK);
    }

 
    protected function enqueueAjaxListeners()
    {
        add_action('wp_ajax_wpstg--backups--transfer--create', $this->container->callback(TransferSessionController::class, 'ajaxCreate')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--transfer--revoke', $this->container->callback(TransferSessionController::class, 'ajaxRevoke')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--transfer--status', $this->container->callback(TransferSessionController::class, 'ajaxStatus')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--transfer--download-started', $this->container->callback(TransferSessionController::class, 'ajaxDownloadStarted')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

        add_action('wp_ajax_wpstg--backups--security-check', $this->container->callback(BackupDirectorySecurityController::class, 'ajaxRunCheck')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--security-notice-dismiss', $this->container->callback(BackupDirectorySecurityController::class, 'ajaxDismissNotice')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }






    protected function enqueueCleanupTriggers()
    {
        add_action(TransferCleanupService::CRON_HOOK, $this->container->callback(TransferCleanupService::class, 'cleanup'), 10, 0);
        add_action('admin_init', $this->container->callback(TransferCleanupService::class, 'maybeCleanup'), 30, 0);
        add_action('admin_init', [$this, 'scheduleCleanupCron'], 10, 0);
    }
}
