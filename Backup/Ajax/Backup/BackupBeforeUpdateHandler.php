<?php

namespace WPStaging\Backup\Ajax\Backup;

use WPStaging\Backup\Service\BeforeUpdateBackupRequest;
use WPStaging\Backup\Service\BeforeUpdateBackupsService;
use WPStaging\Backup\Service\UpdateProtectionHealth;
use WPStaging\Backup\Service\UpdateProtectionSettings;
use WPStaging\Core\DTO\Settings;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Logger\SseEventCache;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\Security\Capabilities;

use function WPStaging\functions\debug_log;




class BackupBeforeUpdateHandler
{
 
    private $accessToken;

 
    private $capabilities;

 
    private $beforeUpdateBackups;

 
    private $backupRequest;

 
    private $sseEventCache;

 
    private $updateProtectionSettings;

 
    private $health;










    public function __construct(
        AccessToken $accessToken,
        Capabilities $capabilities,
        BeforeUpdateBackupsService $beforeUpdateBackups,
        BeforeUpdateBackupRequest $backupRequest,
        SseEventCache $sseEventCache,
        UpdateProtectionSettings $updateProtectionSettings,
        UpdateProtectionHealth $health
    ) {
        $this->accessToken              = $accessToken;
        $this->capabilities             = $capabilities;
        $this->beforeUpdateBackups      = $beforeUpdateBackups;
        $this->backupRequest            = $backupRequest;
        $this->sseEventCache            = $sseEventCache;
        $this->updateProtectionSettings = $updateProtectionSettings;
        $this->health                   = $health;
    }







    private function requireAuthorizedRequest()
    {
        if (!$this->accessToken->requestHasValidToken()) {
            wp_send_json_error(['message' => esc_html__('Unauthorized', 'wp-staging')], 401);
        }

        if (!current_user_can($this->capabilities->manageWPSTG())) {
            wp_send_json_error(['message' => esc_html__('Forbidden', 'wp-staging')], 403);
        }
    }




    public function save()
    {
        $this->requireAuthorizedRequest();

        $allowed = ['ask', 'always', 'never'];
        $mode    = isset($_POST['mode']) ? Sanitize::sanitizeString($_POST['mode']) : 'ask';
        if (!in_array($mode, $allowed, true)) {
            $mode = 'ask';
        }

        $optionName = Settings::OPTION_BACKUP_BEFORE_UPDATE_MODE;
        if (!update_option($optionName, $mode) && get_option($optionName) !== $mode) {
            debug_log('Failed to save backup-before-update mode: update_option returned false', 'error');
            wp_send_json_error(['message' => esc_html__('Failed to save preference.', 'wp-staging')], 500);
            return;
        }

        wp_send_json_success();
    }







    public function markIntroSeen()
    {
        $this->requireAuthorizedRequest();

        $surface = isset($_POST['surface']) ? Sanitize::sanitizeString($_POST['surface']) : '';
        if (!in_array($surface, UpdateProtectionSettings::INTRO_SURFACES, true)) {
            wp_send_json_error(['message' => esc_html__('Unknown surface.', 'wp-staging')], 400);
        }

        $this->updateProtectionSettings->markIntroSeen($surface);

        wp_send_json_success();
    }








    public function setFeatureEnabled()
    {
        $this->requireAuthorizedRequest();

        $isEnabled = isset($_POST['enabled']) && Sanitize::sanitizeString($_POST['enabled']) === '1';
        $this->updateProtectionSettings->setEnabled($isEnabled);

        wp_send_json_success(['enabled' => $isEnabled]);
    }








    public function getReusableBackup()
    {
        $this->requireAuthorizedRequest();

        $updateType = isset($_POST['updateType']) ? Sanitize::sanitizeString($_POST['updateType']) : 'plugin';

        $reusable          = $this->beforeUpdateBackups->findReusableBackup($this->getBackupData($updateType, ''));
        $willTakeNewBackup = empty($reusable);

        $this->beforeUpdateBackups->prune($willTakeNewBackup ? 1 : 0);

        wp_send_json_success($reusable);
    }










    public function startBackup()
    {
        $this->requireAuthorizedRequest();

        $updateType = isset($_POST['updateType']) ? Sanitize::sanitizeString($_POST['updateType']) : 'plugin';
        $slug       = isset($_POST['slug']) ? Sanitize::sanitizeString($_POST['slug']) : '';
        $pluginFile = isset($_POST['pluginFile']) ? Sanitize::sanitizeString($_POST['pluginFile']) : '';

        if (isset($_POST['join']) && Sanitize::sanitizeString($_POST['join']) === '1') {
            $this->backupRequest->queuePlugin($pluginFile);

            wp_send_json_success(['status' => $this->backupRequest->getStatus()]);
        }

        $outcome = $this->backupRequest->startForUpdate($this->getBackupData($updateType, $slug), $pluginFile);

        if ($outcome === BeforeUpdateBackupRequest::OUTCOME_ALREADY_RUNNING) {
            wp_send_json_error(['message' => esc_html__('A backup is already running.', 'wp-staging'), 'code' => 'already_running']);
        }

        if ($outcome === BeforeUpdateBackupRequest::OUTCOME_FAILED) {
            wp_send_json_error([
                'message'        => esc_html__('The backup could not be started.', 'wp-staging'),
                'code'           => 'start_failed',
                'failureReason'  => $this->health->getReason(),
                'failureDetails' => $this->health->getMessage(),
            ]);
        }

        wp_send_json_success(['status' => $this->backupRequest->getStatus()]);
    }







    public function getBackupProgress()
    {
        $this->requireAuthorizedRequest();

 
 
        $this->backupRequest->failIfStalled();
        $this->backupRequest->runWaitingWork();

        $status = $this->backupRequest->getStatus();

        wp_send_json_success(array_merge([
            'status'         => $status,
            'pluginFiles'    => $this->backupRequest->getPendingPluginFiles(),
            'failureReason'  => $status === BeforeUpdateBackupRequest::STATUS_FAILED ? $this->health->getReason() : '',
            'failureDetails' => $status === BeforeUpdateBackupRequest::STATUS_FAILED ? $this->health->getMessage() : '',
            'isPaused'       => $this->health->isPaused(),
        ], $this->getLastTask()));
    }








    public function resumeProtection()
    {
        $this->requireAuthorizedRequest();

        $this->health->resume();

        wp_send_json_success();
    }











    private function getBackupData(string $updateType, string $slug): array
    {
        $isCore = $updateType === 'core';

        return [
            'name'                           => $this->getBackupName($updateType, $slug),
            'isExportingPlugins'             => $isCore || $updateType === 'plugin',
            'isExportingMuPlugins'           => $isCore,
            'isExportingThemes'              => $isCore || $updateType === 'theme',
            'isExportingUploads'             => $isCore,
            'isExportingOtherWpContentFiles' => $isCore,
            'isExportingOtherWpRootFiles'    => false,
            'isExportingDatabase'            => true,
            'isBeforeUpdateBackup'           => true,
            'isAutomatedBackup'              => false,
            'storages'                       => ['localStorage'],
 
            'isSmartExclusion'               => true,
            'isExcludingCaches'              => true,
            'isExcludingLogs'                => true,
        ];
    }






    private function getBackupName(string $updateType, string $slug): string
    {
        if ($updateType === 'core') {
            return esc_html__('Before WordPress core update', 'wp-staging');
        }

        if ($slug === '') {
            /* translators: %s: what is being updated, for example plugin or theme. */
            return sprintf(esc_html__('Before %s update', 'wp-staging'), $updateType);
        }

        /* translators: %s: name of the plugin or theme being updated. */
        return sprintf(esc_html__('Before updating %s', 'wp-staging'), $slug);
    }










    private function getLastTask(): array
    {
        $nothing = ['title' => '', 'percentage' => 0];

        try {
            $jobId = WPStaging::make(JobTransientCache::class)->getJobId();
            if (empty($jobId)) {
                return $nothing;
            }

            $this->sseEventCache->setJobId($jobId);
            $this->sseEventCache->load();
            $events = $this->sseEventCache->getEvents();
        } catch (\Throwable $e) {
            debug_log('WP STAGING Backup Before Update: could not read backup progress. ' . $e->getMessage(), 'debug', false);

            return $nothing;
        }

        foreach (array_reverse((array)$events) as $event) {
            if (is_array($event) && isset($event['type'], $event['data']['title']) && $event['type'] === SseEventCache::EVENT_TYPE_TASK) {
                return [
                    'title'      => (string)$event['data']['title'],
                    'percentage' => isset($event['data']['percentage']) ? (int)$event['data']['percentage'] : 0,
                ];
            }
        }

        return $nothing;
    }






    public function getPluginUpdateVersionInfo()
    {
        $this->requireAuthorizedRequest();

        $pluginFile = isset($_POST['plugin']) ? Sanitize::sanitizeString($_POST['plugin']) : '';

        if (!function_exists('get_plugin_updates')) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        $updates     = get_plugin_updates();
        $versionInfo = ['name' => '', 'currentVersion' => '', 'newVersion' => ''];
        if (isset($updates[$pluginFile])) {
            $plugin      = $updates[$pluginFile];
            $versionInfo = [
                'name'           => isset($plugin->Name) ? $plugin->Name : '',
                'currentVersion' => isset($plugin->Version) ? $plugin->Version : '',
                'newVersion'     => isset($plugin->update->new_version) ? $plugin->update->new_version : '',
            ];
        }

        wp_send_json_success($versionInfo);
    }
}
