<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Onboarding\BackupPluginsDetector;
use WPStaging\Framework\Onboarding\FreeOnboarding;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Facades\Hooks;

class BackupPluginsNotice
{
    /**
     * @var string
     */
    const OPTION_BACKUP_NOTICE_IS_CLOSED = 'wpstg_backup_notice_is_closed';

    /**
     * @var string
     */
    const OPTION_BACKUP_NOTICE_REMINDER = 'wpstg_backup_notice_remind_me';

    /**
     * @var string
     */
    const FILTER_HIDE_BACKUP_NOTICE = 'wpstg.notice.hide_backup_notice';

    /**
     * @var Notices
     */
    private $notices;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var BackupPluginsDetector
     */
    private $detector;

    /**
     * @var FreeOnboarding
     */
    private $onboarding;

    /**
     * @param Auth $auth
     * @param Notices $notices
     * @param BackupPluginsDetector $detector
     * @param FreeOnboarding $onboarding
     */
    public function __construct(Auth $auth, Notices $notices, BackupPluginsDetector $detector, FreeOnboarding $onboarding)
    {
        $this->notices    = $notices;
        $this->auth       = $auth;
        $this->detector   = $detector;
        $this->onboarding = $onboarding;
    }

    /**
     * @return void
     */
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

        // The task selector carries the competitor message inside the backup card instead.
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

    /**
     * @return void
     */
    public function ajaxBackupPluginNoticeClose()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        update_option(self::OPTION_BACKUP_NOTICE_IS_CLOSED, true);
        wp_send_json_success();
    }

    /**
     * @return void
     */
    public function ajaxBackupPluginNoticeRemindMe()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        update_option(self::OPTION_BACKUP_NOTICE_REMINDER, strtotime('+3 days'), false);
        wp_send_json_success();
    }
}
