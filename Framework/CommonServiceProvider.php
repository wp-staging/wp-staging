<?php

namespace WPStaging\Framework;

use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Core\Cron\Cron;
use WPStaging\Core\Cron\CronIntegrity;
use WPStaging\Framework\Analytics\AnalyticsCleanup;
use WPStaging\Framework\BackgroundProcessing\Job\PrepareJob;
use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Framework\Filesystem\DebugLogReader;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Filesystem\LogCleanup;
use WPStaging\Framework\Mails\MailSender;
use WPStaging\Framework\Notices\BackupPluginsNotice;
use WPStaging\Framework\Notices\CliIntegrationNotice;
use WPStaging\Framework\Notices\WpVersionCompatNotice;
use WPStaging\Framework\Onboarding\FreeOnboarding;
use WPStaging\Framework\Onboarding\OnboardingAjax;
use WPStaging\Framework\Onboarding\OnboardingJourney;
use WPStaging\Framework\Onboarding\QueuedBackup;
use WPStaging\Framework\Performance\MemoryExhaust;
use WPStaging\Framework\Security\Otp\Otp;
use WPStaging\Staging\Jobs\StagingSiteCreate;
use WPStaging\Framework\Settings\DarkMode;
use WPStaging\Framework\Traits\EventLoggerTrait;
use WPStaging\Framework\Utils\DBPermissions;
use WPStaging\Staging\Ajax\StagingSiteDataChecker;








class CommonServiceProvider extends ServiceProvider
{
    use EventLoggerTrait;

    protected function registerClasses()
    {
        $this->container->singleton(DiskWriteCheck::class);
        $this->container->make(DebugLogReader::class)->listenDeleteLogRequest();

        add_action(Cron::ACTION_DAILY_EVENT, [$this, 'cleanupLogs'], 25, 0);
        add_action(Cron::ACTION_DAILY_EVENT, [$this, 'cleanupAnalytics'], 25, 0);
        add_action(Cron::ACTION_DAILY_EVENT, [$this, 'cleanupExpiredOtps'], 25, 0);

 
 
 
 
 
 
 
        $container = $this->container;
        add_action('init', function () use ($container) {
 
 
            if (defined('WP_INSTALLING') && WP_INSTALLING) {
                return;
            }

            if (defined('DOING_CRON') && DOING_CRON) {
                return;
            }

            try {
                $container->make(CronIntegrity::class)->checkAndRepair();
            } catch (\Throwable $e) {
 
            }
        }, 20, 0);
        add_action("wp_ajax_wpstg_is_writable_clone_destination_dir", $this->container->callback(StagingSiteDataChecker::class, "ajaxIsWritableCloneDestinationDir")); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_check_user_permissions", $this->container->callback(DBPermissions::class, 'ajaxCheckDBPermissions')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_check_user_is_authenticated", [$this, "ajaxIsUserAuthenticated"]);// phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_nopriv_wpstg_check_user_is_authenticated", [$this, "ajaxIsUserAuthenticated"]);// phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_backup_plugin_notice_close', $this->container->callback(BackupPluginsNotice::class, 'ajaxBackupPluginNoticeClose')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_backup_plugin_notice_remind_me', $this->container->callback(BackupPluginsNotice::class, 'ajaxBackupPluginNoticeRemindMe')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_cli_notice_close', $this->container->callback(CliIntegrationNotice::class, 'ajaxCliNoticeClose')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_cli_notice_hide_forever', $this->container->callback(CliIntegrationNotice::class, 'ajaxCliNoticeHideForever')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_cli_get_backup_list', $this->container->callback(CliIntegrationNotice::class, 'ajaxGetCliBackupList')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('admin_init', $this->container->callback(DarkMode::class, 'mayBeShowDarkMode'), 10, 1);
        add_action('wp_ajax_wpstg_set_dark_mode', $this->container->callback(DarkMode::class, 'ajaxEnableDefaultColorMode')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_set_default_os_color_mode', $this->container->callback(DarkMode::class, 'ajaxSetDefaultOsMode')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_log_event_failure", [$this, "ajaxLogEventFailure"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_nopriv_wpstg_log_event_failure", [$this, "ajaxLogEventFailure"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--detect-memory-exhaust', $this->container->callback(MemoryExhaust::class, 'ajaxResponse')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_wpstg_log_event_success", [$this, "ajaxLogEventSuccess"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action("wp_ajax_nopriv_wpstg_log_event_success", [$this, "ajaxLogEventSuccess"]); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_send_mail_notification', $this->container->callback(MailSender::class, 'ajaxSendEmailNotification')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_nopriv_wpstg_send_mail_notification', $this->container->callback(MailSender::class, 'ajaxSendEmailNotification')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_dismiss_compat_notice', $this->container->callback(WpVersionCompatNotice::class, 'ajaxDismissCompatNotice')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_onboarding_select_action', $this->container->callback(OnboardingAjax::class, 'ajaxSelectAction')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_filter('admin_body_class', [$this, 'addOnboardingBodyClass']);
        add_action('wp_ajax_wpstg_onboarding_action_started', $this->container->callback(OnboardingAjax::class, 'ajaxActionStarted')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_onboarding_offer_shown', $this->container->callback(OnboardingAjax::class, 'ajaxOfferShown')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_onboarding_restart', $this->container->callback(OnboardingAjax::class, 'ajaxRestart')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_onboarding_next_step', $this->container->callback(OnboardingAjax::class, 'ajaxNextStep')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_onboarding_cancel_action', $this->container->callback(OnboardingAjax::class, 'ajaxCancelAction')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_onboarding_finish', $this->container->callback(OnboardingAjax::class, 'ajaxFinish')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_onboarding_queue_backup', $this->container->callback(OnboardingAjax::class, 'ajaxQueueBackup')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_onboarding_backup_status', $this->container->callback(OnboardingAjax::class, 'ajaxQueuedBackupStatus')); // phpcs:ignore WPStaging.Security.AuthorizationChecked

 
        add_action(StagingSiteCreate::ACTION_STAGING_SITE_CREATED, $this->container->callback(OnboardingJourney::class, 'completeStaging'), 5);
        add_action(StagingSiteCreate::ACTION_STAGING_SITE_CREATED, $this->container->callback(QueuedBackup::class, 'startAfterStagingCreated'));
        add_action(FinishBackupTask::ACTION_BACKUP_CREATED, $this->container->callback(OnboardingJourney::class, 'completeBackup'), 5);
        add_action(FinishBackupTask::ACTION_BACKUP_CREATED, $this->container->callback(QueuedBackup::class, 'markCompleted'));
 
 
        add_action(PrepareJob::ACTION_JOB_FAILURE, $this->container->callback(QueuedBackup::class, 'onBackgroundJobFailure'));

        add_action('wp_ajax_wpstg_cancel_clone', $this->container->callback(QueuedBackup::class, 'discardOnStagingRequest'), 1); // phpcs:ignore WPStaging.Security.AuthorizationChecked -- Authorization checked in discardOnStagingRequest()

 
 
        add_action('wp_ajax_wpstg_cancel_clone', $this->container->callback(OnboardingJourney::class, 'abandonActionOnRequest'), 2); // phpcs:ignore WPStaging.Security.AuthorizationChecked -- Authorization checked in abandonActionOnRequest()
        add_action('wp_ajax_wpstg--job--cancel', $this->container->callback(QueuedBackup::class, 'discardOnStagingRequest'), 1); // phpcs:ignore WPStaging.Security.AuthorizationChecked -- Authorization checked in discardOnStagingRequest()
        add_action('wp_ajax_wpstg--job--cancel', $this->container->callback(OnboardingJourney::class, 'abandonActionOnRequest'), 2); // phpcs:ignore WPStaging.Security.AuthorizationChecked -- Authorization checked in abandonActionOnRequest()
        add_action('wp_ajax_wpstg_staging_job_error', $this->container->callback(QueuedBackup::class, 'discardOnStagingRequest'), 1); // phpcs:ignore WPStaging.Security.AuthorizationChecked -- Authorization checked in discardOnStagingRequest()
    }










    public function addOnboardingBodyClass($classes)
    {
        $onboarding = FreeOnboarding::resolve();

        return $onboarding === null ? $classes : $onboarding->addFocusModeBodyClass($classes);
    }




    public function cleanupLogs()
    {
        $this->container->make(LogCleanup::class)->cleanOldLogs();
    }




    public function cleanupAnalytics()
    {
        $this->container->make(AnalyticsCleanup::class)->cleanupOldAnalytics();
    }




    public function cleanupExpiredOtps()
    {
        $this->container->make(Otp::class)->cleanupExpiredOtps();
    }




    public function ajaxIsUserAuthenticated()
    {
        if (!is_user_logged_in()) {
            wp_send_json(['wpAuthCheck' => false, 'redirectUrl' => wp_login_url()]);
        }

        wp_send_json(['wpAuthCheck' => true]);
    }
}
