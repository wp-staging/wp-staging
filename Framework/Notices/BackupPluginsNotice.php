<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Core\WPStaging;
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
     * @param Auth $auth
     * @param Notices $notices
     */
    public function __construct(Auth $auth, Notices $notices)
    {
        $this->notices = $notices;
        $this->auth = $auth;
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

        $isUpdraftInstalled = is_plugin_active('updraftplus/updraftplus.php');
        $isBackupMigrationInstalled = is_plugin_active('backup-backup/backup-backup.php');
        if (!$isUpdraftInstalled && !$isBackupMigrationInstalled) {
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
