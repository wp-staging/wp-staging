<?php

namespace WPStaging\Framework\Settings;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Framework\Security\Auth;
use WPStaging\Notifications\Notifications;

class Settings
{
    /** @var string */
    const ACTION_WPSTG_PRO_SETTINGS = 'wpstg.views.pro.settings';

    /**
     * @var array
     * Sanitize the options to escape from XSS
     */
    private $optionsToSanitize = [
        'queryLimit'         => 'sanitizeInt',
        'querySRLimit'       => 'sanitizeInt',
        'fileLimit'          => 'sanitizeInt',
        'maxFileSize'        => 'sanitizeInt',
        'batchSize'          => 'sanitizeInt',
        'delayRequest'       => 'sanitizeInt',
        'cpuLoad'            => 'sanitizeString',
        'unInstallOnDelete'  => 'sanitizeBool',
        'optimizer'          => 'sanitizeBool',
        'disableAdminLogin'  => 'sanitizeBool',
        'keepPermalinks'     => 'sanitizeBool',
        'checkDirectorySize' => 'sanitizeBool',
        'debugMode'          => 'sanitizeBool',
    ];

    /**
     * @var SiteInfo
     */
    private $siteInfo;

    /**
     * @var Sanitize
     */
    private $sanitize;

    /** @var Queue */
    private $queue;

    /** @var Auth */
    private $auth;

    /**
     * @param SiteInfo $siteInfo
     * @param Sanitize $sanitize
     * @param Queue $queue
     * @param Auth $auth
     */
    public function __construct(SiteInfo $siteInfo, Sanitize $sanitize, Queue $queue, Auth $auth)
    {
        $this->siteInfo = $siteInfo;
        $this->sanitize = $sanitize;
        $this->queue    = $queue;
        $this->auth     = $auth;
    }

    /**
     * @return void
     */
    public function registerSettings()
    {
        register_setting("wpstg_settings", "wpstg_settings", [$this, "sanitizeOptions"]);
    }

    /**
     * Sanitize options data and delete the cache
     * @param array $data
     * @return array
     */
    public function sanitizeOptions(array $data = []): array
    {
        // is_array() is required otherwise new clone will fail.
        $showErrorToggleStagingSiteCloning = false;
        if ($this->siteInfo->isStagingSite() && is_array($data)) {
            $isStagingCloneable = isset($data['isStagingSiteCloneable']) ? $data['isStagingSiteCloneable'] : 'false';
            unset($data['isStagingSiteCloneable']);
            $showErrorToggleStagingSiteCloning = !$this->toggleStagingSiteCloning($isStagingCloneable === 'true');
        }

        if (is_array($data)) {
            $optionBackupScheduleErrorReport = isset($data['schedulesErrorReport']) ? 'true' : '';
            $optionBackupScheduleReportEmail = !empty($data['schedulesReportEmail']) ? $this->sanitize->sanitizeEmail($data['schedulesReportEmail']) : '';

            if (empty($optionBackupScheduleReportEmail)) {
                $optionBackupScheduleErrorReport = '';
            }

            unset($data['schedulesErrorReport'], $data['schedulesReportEmail']);

            $optionBackupScheduleSlackErrorReport   = isset($data['schedulesSlackErrorReport']) ? 'true' : '';
            $optionBackupScheduleReportSlackWebhook = !empty($data['schedulesReportSlackWebhook']) ? $this->sanitize->sanitizeUrl($data['schedulesReportSlackWebhook']) : '';
            $optionSendEmailAsHTML                  = isset($data['emailAsHTML']) ? 'true' : '';

            if (empty($optionBackupScheduleReportSlackWebhook)) {
                $optionBackupScheduleSlackErrorReport = '';
            }

            unset($data['schedulesErrorSlackReport'], $data['schedulesReportSlackWebhook']);

            $this->setErrorReportOptions($optionBackupScheduleErrorReport, $optionBackupScheduleReportEmail, $optionBackupScheduleSlackErrorReport, $optionBackupScheduleReportSlackWebhook, $optionSendEmailAsHTML);
        }

        $sanitized = $this->sanitizeData($data);

        if ($showErrorToggleStagingSiteCloning) {
            add_settings_error("wpstg-notices", '', __("Settings updated. But unable to activate/deactivate the site cloneable status!", "wp-staging"), "warning");
        } else {
            add_settings_error("wpstg-notices", '', __("Settings updated.", "wp-staging"), "updated");
        }

        return $sanitized;
    }

    /**
     * @return null
     */
    public function ajaxPurgeQueueTable()
    {
        if ($this->auth->isAuthenticatedRequest() === false) {
            wp_send_json([
                'success' => false,
                'message' => esc_html__('Error 403: Unauthorized Request', 'wp-staging')
            ]);
        }

        $result = $this->queue->purgeQueueTable();

        if ($result === false) {
            wp_send_json([
                'success' => false,
                'message' => esc_html__('Unable to purge queue table', 'wp-staging')
            ]);
        }

        if ($result === 0) {
            wp_send_json([
                'success' => true,
                'message' => sprintf(esc_html__('Table %s is already empty.', 'wp-staging'), esc_html($this->queue->getTableName()))
            ]);
        }

        wp_send_json([
            'success' => true,
            'message' => sprintf(esc_html__('Purged queue table! Removed %s action(s)', 'wp-staging'), esc_html($result))
        ]);

        return null;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function sanitizeData(array $data = []): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
                continue;
            }

            $val = htmlspecialchars($value);
            if (array_key_exists($key, $this->optionsToSanitize)) {
                $sanitizeMethod = $this->optionsToSanitize[$key];
                $val            = $this->sanitize->$sanitizeMethod($val);
            }

            $sanitized[$key] = wp_filter_nohtml_kses($val);
        }

        return $sanitized;
    }

    /**
     * Toggle staging site cloning
     *
     * @param bool $isCloneable
     *
     * @return bool
     */
    protected function toggleStagingSiteCloning(bool $isCloneable): bool
    {
        if ($isCloneable && $this->siteInfo->enableStagingSiteCloning()) {
            return true;
        }

        if (!$isCloneable && $this->siteInfo->disableStagingSiteCloning()) {
            return true;
        }

        return false;
    }

    /**
     * Set backup schedule error reporting options
     *
     * @param string $optionBackupScheduleErrorReport 'true' if active
     * @param string $optionBackupScheduleReportEmail
     * @param string $optionBackupScheduleSlackErrorReport 'true' if active
     * @param string $optionBackupScheduleReportSlackWebhook
     * @param string $optionSendEmailAsHTML 'true' if active
     * @return void
     */
    protected function setErrorReportOptions(
        string $optionBackupScheduleErrorReport,
        string $optionBackupScheduleReportEmail,
        string $optionBackupScheduleSlackErrorReport,
        string $optionBackupScheduleReportSlackWebhook,
        string $optionSendEmailAsHTML
    ) {
        if (!class_exists('WPStaging\Backup\BackupScheduler')) {
            return;
        }

        update_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_ERROR_REPORT, $optionBackupScheduleErrorReport);
        update_option(Notifications::OPTION_BACKUP_SCHEDULE_REPORT_EMAIL, $optionBackupScheduleReportEmail);
        update_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_SLACK_ERROR_REPORT, $optionBackupScheduleSlackErrorReport);
        update_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_REPORT_SLACK_WEBHOOK, $optionBackupScheduleReportSlackWebhook);
        update_option(Notifications::OPTION_SEND_EMAIL_AS_HTML, $optionSendEmailAsHTML);
    }
}
