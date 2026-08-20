<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Onboarding\BackupPluginsDetector;
use WPStaging\Framework\Onboarding\FreeOnboarding;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Facades\Hooks;

class BackupPluginsNotice
{



    const OPTION_BACKUP_NOTICE_IS_CLOSED = 'wpstg_backup_notice_is_closed';




    const OPTION_BACKUP_NOTICE_REMINDER = 'wpstg_backup_notice_remind_me';




    const FILTER_HIDE_BACKUP_NOTICE = 'wpstg.notice.hide_backup_notice';




    private $notices;




    private $auth;




    private $detector;




    private $onboarding;







    public function __construct(Auth $auth, Notices $notices, BackupPluginsDetector $detector, FreeOnboarding $onboarding)
    {
        $this->notices    = $notices;
        $this->auth       = $auth;
        $this->detector   = $detector;
        $this->onboarding = $onboarding;
    }




    public function maybeShowBackupNotice()
    {
        if (!$this->notices->isWPStagingAdminPage()) {
            return;
        }

        if (WPStaging::isPro()) {
            return;
        }

        if (Hooks::applyFilters(self::FILTER_HIDE_BACKUP_NOTICE, false)) {
            return;
        }

 
        if ($this->onboarding->isTaskSelector()) {
            return;
        }

        if (!$this->detector->hasCompetingPlugin()) {
            return;
        }

        if (!current_user_can('manage_options') || get_option(self::OPTION_BACKUP_NOTICE_IS_CLOSED)) {
            return;
        }

        $remindMe = get_option(self::OPTION_BACKUP_NOTICE_REMINDER);

        if (!empty($remindMe) && time() < $remindMe) {
            return;
        }

        $notice = WPSTG_VIEWS_DIR . 'notices/backup-plugins-notice.php';

        if (!file_exists($notice)) {
            return;
        }

        include $notice;
    }




    public function ajaxBackupPluginNoticeClose()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        update_option(self::OPTION_BACKUP_NOTICE_IS_CLOSED, true);
        wp_send_json_success();
    }




    public function ajaxBackupPluginNoticeRemindMe()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        update_option(self::OPTION_BACKUP_NOTICE_REMINDER, strtotime('+3 days'), false);
        wp_send_json_success();
    }
}
