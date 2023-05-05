<?php

namespace WPStaging\Framework\Settings;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Framework\Security\Auth;

class Settings
{
    /**
     * @var array
     * Sanitize the options to escape from XSS
     */
    private $optionsToSanitize = [
        'queryLimit' => 'sanitizeInt',
        'querySRLimit' => 'sanitizeInt',
        'fileLimit' => 'sanitizeInt',
        'maxFileSize' => 'sanitizeInt',
        'batchSize' => 'sanitizeInt',
        'delayRequest' => 'sanitizeInt',
        'cpuLoad' => 'sanitizeString',
        'unInstallOnDelete' => 'sanitizeBool',
        'optimizer' => 'sanitizeBool',
        'disableAdminLogin' => 'sanitizeBool',
        'keepPermalinks' => 'sanitizeBool',
        'checkDirectorySize' => 'sanitizeBool',
        'debugMode' => 'sanitizeBool',
        'schedulesErrorReport' => 'sanitizeBool',
        'schedulesReportEmail' => 'sanitizeEmail',
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

    public function __construct(SiteInfo $siteInfo, Sanitize $sanitize, Queue $queue, Auth $auth)
    {
        $this->siteInfo = $siteInfo;
        $this->sanitize = $sanitize;
        $this->queue    = $queue;
        $this->auth     = $auth;
    }

    public function registerSettings()
    {
        register_setting("wpstg_settings", "wpstg_settings", [$this, "sanitizeOptions"]);
    }

    /**
     * Sanitize options data and delete the cache
     * @param array $data
     * @return array
     */
    public function sanitizeOptions($data = [])
    {
        // is_array() is required otherwise new clone will fail.
        $showErrorToggleStagingSiteCloning = false;
        if ($this->siteInfo->isStagingSite() && is_array($data)) {
            $isStagingCloneable = isset($data['isStagingSiteCloneable']) ? $data['isStagingSiteCloneable'] : 'false';
            unset($data['isStagingSiteCloneable']);
            $showErrorToggleStagingSiteCloning = !$this->toggleStagingSiteCloning($isStagingCloneable === 'true');
        }

        if (WPStaging::isPro() && is_array($data)) {
            $sendBackupSchedulesErrorReport = isset($data['schedulesErrorReport']) ? $data['schedulesErrorReport'] : false;
            $reportEmail                    = isset($data['schedulesReportEmail']) ? $data['schedulesReportEmail'] : '';
            unset($data['schedulesErrorReport']);
            unset($data['wpstg-send-schedules-report-email']);
            $this->setBackupScheduleOptions($sendBackupSchedulesErrorReport, $reportEmail);
        }

        $sanitized = $this->sanitizeData($data);

        if ($showErrorToggleStagingSiteCloning) {
            add_settings_error("wpstg-notices", '', __("Settings updated. But unable to activate/deactivate the site cloneable status!", "wp-staging"), "warning");
        } else {
            add_settings_error("wpstg-notices", '', __("Settings updated.", "wp-staging"), "updated");
        }

        return apply_filters("wpstg-settings", $sanitized, $data);
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
    }

    /**
     * @param array $data
     * @return array
     */
    protected function sanitizeData($data = [])
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
    protected function toggleStagingSiteCloning($isCloneable)
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
     * @param bool $sendBackupSchedulesErrorReport
     * @param string $reportEmail
     * @return bool
     */
    protected function setBackupScheduleOptions($sendBackupSchedulesErrorReport, $reportEmail)
    {
        if (!WPStaging::isPro()) {
            return false;
        }

        if (!class_exists('WPStaging\Backup\BackupScheduler')) {
            return false;
        }

        $error = !update_option(BackupScheduler::BACKUP_SCHEDULE_ERROR_REPORT_OPTION, $sendBackupSchedulesErrorReport);
        if ($error) {
            return false;
        }

        return update_option(BackupScheduler::BACKUP_SCHEDULE_REPORT_EMAIL_OPTION, $reportEmail);
    }
}
