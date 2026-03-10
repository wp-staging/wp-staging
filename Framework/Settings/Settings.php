<?php

namespace WPStaging\Framework\Settings;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\FeatureDetection;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\Network\HttpBasicAuth;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Security\DataEncryption;
use WPStaging\Notifications\Notifications;

/**
 * This class provides functionality for managing application settings.
 */
class Settings
{
    use HttpBasicAuth;

    /** @var string */
    const ACTION_WPSTG_PRO_SETTINGS = 'wpstg.views.pro.settings';

    /**
     * @var array
     * Sanitize the options to escape from XSS
     */
    private $optionsToSanitize = [
        'queryLimit'        => 'sanitizeInt',
        'querySRLimit'      => 'sanitizeInt',
        'fileLimit'         => 'sanitizeInt',
        'maxFileSize'       => 'sanitizeInt',
        'batchSize'         => 'sanitizeInt',
        'delayRequest'      => 'sanitizeInt',
        'cpuLoad'           => 'sanitizeString',
        'unInstallOnDelete' => 'sanitizeBool',
        'optimizer'         => 'sanitizeBool',
        'disableAdminLogin' => 'sanitizeBool',
        'keepPermalinks'    => 'sanitizeBool',
        'debugMode'         => 'sanitizeBool',
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

    /** @var DataEncryption */
    private $dataEncryption;

    /**
     * @param SiteInfo $siteInfo
     * @param Sanitize $sanitize
     * @param Queue $queue
     * @param Auth $auth
     * @param DataEncryption $dataEncryption
     */
    public function __construct(SiteInfo $siteInfo, Sanitize $sanitize, Queue $queue, Auth $auth, DataEncryption $dataEncryption)
    {
        $this->siteInfo       = $siteInfo;
        $this->sanitize       = $sanitize;
        $this->queue          = $queue;
        $this->auth           = $auth;
        $this->dataEncryption = $dataEncryption;
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
            $optionBackupScheduleWarningReport = isset($data['schedulesWarningReport']) ? 'true' : '';
            $optionBackupScheduleGeneralReport = isset($data['schedulesGeneralReport']) ? 'true' : '';
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

            $this->setErrorReportOptions(
                $optionBackupScheduleErrorReport,
                $optionBackupScheduleWarningReport,
                $optionBackupScheduleGeneralReport,
                $optionBackupScheduleReportEmail,
                $optionBackupScheduleSlackErrorReport,
                $optionBackupScheduleReportSlackWebhook,
                $optionSendEmailAsHTML
            );

            $this->saveHttpAuthCredentials($data);
            unset($data['httpAuthUsername'], $data['httpAuthPassword']);
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
                'message' => esc_html__('Error 403: Unauthorized Request', 'wp-staging'),
            ]);
        }

        $result = $this->queue->purgeQueueTable();

        if ($result === false) {
            wp_send_json([
                'success' => false,
                'message' => esc_html__('Unable to purge queue table', 'wp-staging'),
            ]);
        }

        if ($result === 0) {
            wp_send_json([
                'success' => true,
                'message' => sprintf(esc_html__('Table %s is already empty.', 'wp-staging'), esc_html($this->queue->getTableName())),
            ]);
        }

        wp_send_json([
            'success' => true,
            'message' => sprintf(esc_html__('Purged queue table! Removed %s action(s)', 'wp-staging'), esc_html((string)$result)),
        ]);

        return null;
    }

    /**
     * Lightweight AJAX action that serves as the loopback target for HTTP Basic Auth testing.
     * Returns a simple success response so the caller can verify the request went through.
     *
     * No auth check: this endpoint is intentionally public (wp_ajax_nopriv_) so the
     * server-side loopback in ajaxTestHttpAuth() can reach it without a WP session.
     *
     * @return void
     */
    public function ajaxHttpAuthPing()
    {
        wp_send_json_success(['ping' => true]);
    }

    /**
     * Tests that the saved HTTP Basic Auth credentials allow loopback requests to admin-ajax.php.
     * Performs a wp_remote_post() to the ping action using the stored credentials.
     *
     * @return void
     */
    public function ajaxTestHttpAuth()
    {
        if ($this->auth->isAuthenticatedRequest() === false) {
            wp_send_json_error(['message' => esc_html__('Error 403: Unauthorized Request', 'wp-staging')]);
            return;
        }

        $headers = $this->getHttpAuthHeaders();
        if (empty($headers)) {
            wp_send_json_error(['message' => esc_html__('No HTTP Basic Auth credentials are saved yet. Save your settings first, then test the connection.', 'wp-staging')]);
            return;
        }

        $url = admin_url('admin-ajax.php');

        $response = wp_remote_post($url, [
            'timeout'   => 15,
            'sslverify' => apply_filters(FeatureDetection::FILTER_HTTPS_LOCAL_SSL_VERIFY, false),
            'headers'   => $headers,
            'body'      => [
                'action' => 'wpstg_http_auth_ping',
            ],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => sprintf(
                    esc_html__('Connection failed: %s', 'wp-staging'),
                    esc_html($response->get_error_message())
                ),
            ]);
            return;
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body       = json_decode(wp_remote_retrieve_body($response), true);

        if ($statusCode === 401) {
            wp_send_json_error(['message' => esc_html__('Authentication failed (401). The username or password is incorrect.', 'wp-staging')]);
            return;
        }

        if ($statusCode === 403) {
            wp_send_json_error(['message' => esc_html__('Access denied (403). The request was blocked, possibly by a firewall or security plugin.', 'wp-staging')]);
            return;
        }

        if ($statusCode !== 200 || empty($body['success'])) {
            wp_send_json_error([
                'message' => sprintf(
                    esc_html__('Unexpected response (HTTP %s). The loopback request did not succeed.', 'wp-staging'),
                    esc_html((string)$statusCode)
                ),
            ]);
            return;
        }

        wp_send_json_success(['message' => esc_html__('Connection successful! Background tasks will be able to reach wp-admin.', 'wp-staging')]);
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
     * @param string $optionBackupScheduleWarningReport 'true' if active
     * @param string $optionBackupScheduleGeneralReport 'true' if active
     * @param string $optionBackupScheduleReportEmail
     * @param string $optionBackupScheduleSlackErrorReport 'true' if active
     * @param string $optionBackupScheduleReportSlackWebhook
     * @param string $optionSendEmailAsHTML 'true' if active
     * @return void
     */
    protected function setErrorReportOptions(
        string $optionBackupScheduleErrorReport,
        string $optionBackupScheduleWarningReport,
        string $optionBackupScheduleGeneralReport,
        string $optionBackupScheduleReportEmail,
        string $optionBackupScheduleSlackErrorReport,
        string $optionBackupScheduleReportSlackWebhook,
        string $optionSendEmailAsHTML
    ) {
        if (!class_exists('WPStaging\Backup\BackupScheduler')) {
            return;
        }

        update_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_ERROR_REPORT, $optionBackupScheduleErrorReport, false);
        update_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_WARNING_REPORT, $optionBackupScheduleWarningReport, false);
        update_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_GENERAL_REPORT, $optionBackupScheduleGeneralReport, false);
        update_option(Notifications::OPTION_BACKUP_SCHEDULE_REPORT_EMAIL, $optionBackupScheduleReportEmail);
        update_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_SLACK_ERROR_REPORT, $optionBackupScheduleSlackErrorReport, false);
        update_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_REPORT_SLACK_WEBHOOK, $optionBackupScheduleReportSlackWebhook, false);
        update_option(Notifications::OPTION_SEND_EMAIL_AS_HTML, $optionSendEmailAsHTML);
    }

    /**
     * Save HTTP Basic Auth credentials for loopback requests.
     * If username is empty, both username and password are cleared.
     * If password is blank and username is provided, existing password is preserved.
     *
     * @param array $data
     * @return void
     */
    protected function saveHttpAuthCredentials(array $data)
    {
        $username = isset($data['httpAuthUsername'])
            ? $this->sanitize->sanitizeString($data['httpAuthUsername'], false)
            : '';

        if (empty($username)) {
            update_option(Queue::OPTION_HTTP_AUTH_CREDENTIALS, ['username' => '', 'password' => ''], false);
            return;
        }

        $submittedPassword = isset($data['httpAuthPassword'])
            ? $this->sanitize->sanitizePassword($data['httpAuthPassword'])
            : '';

        if (!empty($submittedPassword)) {
            $password = $this->dataEncryption->encrypt($submittedPassword);
        } else {
            $existing = get_option(Queue::OPTION_HTTP_AUTH_CREDENTIALS, []);
            $password = !empty($existing['password']) ? $existing['password'] : '';
        }

        update_option(Queue::OPTION_HTTP_AUTH_CREDENTIALS, [
            'username' => $username,
            'password' => $password,
        ], false);
    }
}
