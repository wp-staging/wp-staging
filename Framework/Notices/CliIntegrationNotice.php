<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Traits\NoticesTrait;

/**
 * Displays a dismissible banner promoting the WP Staging CLI tool
 *
 * The banner appears on both Staging and Backup tabs for Pro version users.
 * When dismissed, it stays hidden for 24 hours using WordPress transients.
 */
class CliIntegrationNotice
{
    use NoticesTrait;

    /**
     * @var bool Set to true to enable the CLI integration banner
     * @todo Enable this in next version when the feature is ready
     */
    const IS_ENABLED = true;

    /**
     * @var string Transient key for 24-hour dismissal
     */
    const TRANSIENT_CLI_NOTICE_DISMISSED = 'wpstg_cli_notice_dismissed';

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @param Auth $auth
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Display the CLI integration banner if conditions are met
     *
     * @return void
     */
    public function maybeShowCliNotice()
    {
        // Feature is temporarily disabled
        if (!self::IS_ENABLED) {
            return;
        }

        // Only show on WP Staging admin pages
        if (!$this->isWPStagingAdminPage()) {
            return;
        }

        // Only show in Pro version
        if (WPStaging::isBasic()) {
            return;
        }

        // Don't show if user cannot manage options
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if notice was dismissed in last 24 hours
        if (get_transient(self::TRANSIENT_CLI_NOTICE_DISMISSED)) {
            return;
        }

        $notice = WPSTG_VIEWS_DIR . 'notices/cli-integration-notice.php';

        if (!file_exists($notice)) {
            return;
        }

        include $notice;
    }

    /**
     * AJAX handler to dismiss the CLI notice for 24 hours
     *
     * @return void
     */
    public function ajaxCliNoticeClose()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        set_transient(self::TRANSIENT_CLI_NOTICE_DISMISSED, true, DAY_IN_SECONDS);
        wp_send_json_success();
    }

    /**
     * AJAX handler to get updated CLI modal backup list HTML
     *
     * @return void
     */
    public function ajaxGetCliBackupList()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        $backups   = [];
        $urlAssets = trailingslashit(WPSTG_PLUGIN_URL) . 'assets/';

        if (class_exists('\WPStaging\Backup\Ajax\FileList\ListableBackupsCollection')) {
            try {
                /** @var \WPStaging\Backup\Ajax\FileList\ListableBackupsCollection $listableBackupsCollection */
                $listableBackupsCollection = \WPStaging\Core\WPStaging::make(\WPStaging\Backup\Ajax\FileList\ListableBackupsCollection::class);
                $backups                   = $listableBackupsCollection->getListableBackups();
            } catch (\Exception $e) {
                $backups = [];
            }
        }

        // Check if there are valid (non-corrupt, non-legacy) backups
        $hasBackups = false;
        foreach ($backups as $backup) {
            if (!$backup->isCorrupt && !$backup->isLegacy) {
                $hasBackups = true;
                break;
            }
        }

        ob_start();
        include WPSTG_VIEWS_DIR . 'cli/cli-backup-list.php';
        $html = ob_get_clean();

        wp_send_json_success([
            'html'       => $html,
            'hasBackups' => $hasBackups,
        ]);
    }
}
