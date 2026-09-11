<?php

namespace WPStaging\Backup\Ajax\Backup;

use WPStaging\Backup\Service\BackgroundBackupProgress;
use WPStaging\Backup\Service\BeforeUpdateBackupRequest;
use WPStaging\Backup\Service\BeforeUpdateBackupsService;
use WPStaging\Backup\Service\StagingUpdateBackupClient;
use WPStaging\Backup\Service\UpdateProtectionHealth;
use WPStaging\Backup\Service\UpdateProtectionSettings;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Traits\AuthorizedRequestTrait;




class BackupBeforeUpdateHandler
{
    use AuthorizedRequestTrait;

 
    private $accessToken;

 
    private $capabilities;

 
    private $beforeUpdateBackups;

 
    private $backupRequest;

 
    private $backupProgress;

 
    private $updateProtectionSettings;

 
    private $health;

 
    private $stagingUpdateBackupClient;











    public function __construct(
        AccessToken $accessToken,
        Capabilities $capabilities,
        BeforeUpdateBackupsService $beforeUpdateBackups,
        BeforeUpdateBackupRequest $backupRequest,
        BackgroundBackupProgress $backupProgress,
        UpdateProtectionSettings $updateProtectionSettings,
        UpdateProtectionHealth $health,
        StagingUpdateBackupClient $stagingUpdateBackupClient
    ) {
        $this->accessToken              = $accessToken;
        $this->capabilities             = $capabilities;
        $this->beforeUpdateBackups      = $beforeUpdateBackups;
        $this->backupRequest            = $backupRequest;
        $this->backupProgress           = $backupProgress;
        $this->updateProtectionSettings = $updateProtectionSettings;
        $this->health                   = $health;
        $this->stagingUpdateBackupClient = $stagingUpdateBackupClient;
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










    public function cancelBackup()
    {
        $this->requireAuthorizedRequest();

        wp_send_json_success(['cancelled' => $this->backupRequest->cancel()]);
    }








    public function getReusableBackup()
    {
        $this->requireAuthorizedRequest();

        $updateType = isset($_POST['updateType']) ? Sanitize::sanitizeString($_POST['updateType']) : 'plugin';
        $cloneId    = isset($_POST['cloneId']) ? Sanitize::sanitizeString($_POST['cloneId']) : '';

        if ($updateType === 'staging') {
            $this->sendResult($this->stagingUpdateBackupClient->request('reusable', $cloneId));
            return;
        }

        $this->sendResult($this->getReusableBackupOnThisSite($updateType));
    }





    private function getReusableBackupOnThisSite(string $updateType): array
    {
        $backupData        = $this->getBackupData($updateType, '');
        $reusable          = $this->beforeUpdateBackups->findReusableBackup($backupData);
        $willTakeNewBackup = empty($reusable);

        $this->beforeUpdateBackups->prune($willTakeNewBackup ? 1 : 0);

        return ['success' => true, 'data' => $reusable];
    }










    public function startBackup()
    {
        $this->requireAuthorizedRequest();

        $updateType = isset($_POST['updateType']) ? Sanitize::sanitizeString($_POST['updateType']) : 'plugin';
        $slug       = isset($_POST['slug']) ? Sanitize::sanitizeString($_POST['slug']) : '';
        $pluginFile = isset($_POST['pluginFile']) ? Sanitize::sanitizeString($_POST['pluginFile']) : '';
        $cloneId    = isset($_POST['cloneId']) ? Sanitize::sanitizeString($_POST['cloneId']) : '';

        if ($updateType === 'staging') {
            $this->sendResult($this->stagingUpdateBackupClient->request('start', $cloneId));
            return;
        }

        if (isset($_POST['join']) && Sanitize::sanitizeString($_POST['join']) === '1') {
            $this->backupRequest->queuePlugin($pluginFile);

            wp_send_json_success(['status' => $this->backupRequest->getStatus()]);
        }

        $this->sendResult($this->startBackupOnThisSite($updateType, $slug, $pluginFile));
    }







    private function startBackupOnThisSite(string $updateType, string $slug, string $pluginFile): array
    {
        $outcome = $this->backupRequest->startForUpdate($this->getBackupData($updateType, $slug), $pluginFile);

        if ($outcome === BeforeUpdateBackupRequest::OUTCOME_ALREADY_RUNNING) {
            return ['success' => false, 'data' => ['message' => esc_html__('A backup is already running.', 'wp-staging'), 'code' => 'already_running']];
        }

        if ($outcome === BeforeUpdateBackupRequest::OUTCOME_FAILED) {
            return [
                'success' => false,
                'data'    => [
                    'message'        => esc_html__('The backup could not be started.', 'wp-staging'),
                    'code'           => 'start_failed',
                    'failureReason'  => $this->health->getReason(),
                    'failureDetails' => $this->health->getMessage(),
                ],
            ];
        }

        return ['success' => true, 'data' => ['status' => $this->backupRequest->getStatus()]];
    }







    public function getBackupProgress()
    {
        $this->requireAuthorizedRequest();

        $updateType = isset($_POST['updateType']) ? Sanitize::sanitizeString($_POST['updateType']) : '';
        $cloneId    = isset($_POST['cloneId']) ? Sanitize::sanitizeString($_POST['cloneId']) : '';

        if ($updateType === 'staging') {
            $this->sendResult($this->stagingUpdateBackupClient->request('progress', $cloneId));
            return;
        }

        $this->sendResult($this->getBackupProgressOnThisSite());
    }




    private function getBackupProgressOnThisSite(): array
    {
        $this->backupRequest->failIfStalled();
        $this->backupRequest->runWaitingWork();

        $status = $this->backupRequest->getStatus();

        return [
            'success' => true,
            'data'    => array_merge(
                [
                    'status'         => $status,
                    'pluginFiles'    => $this->backupRequest->getPendingPluginFiles(),
                    'failureReason'  => $status === BeforeUpdateBackupRequest::STATUS_FAILED ? $this->health->getReason() : '',
                    'failureDetails' => $status === BeforeUpdateBackupRequest::STATUS_FAILED ? $this->health->getMessage() : '',
                    'isPaused'       => $this->health->isPaused(),
                ],
                $this->backupProgress->getLastTask('WP STAGING Backup Before Update')
            ),
        ];
    }








    public function resumeProtection()
    {
        $this->requireAuthorizedRequest();

        $this->health->resume();

        wp_send_json_success();
    }











    private function getBackupData(string $updateType, string $slug): array
    {
        $isWholeSite = $updateType === 'core';

        return [
            'name'                           => $this->getBackupName($updateType, $slug),
            'isExportingPlugins'             => $isWholeSite || $updateType === 'plugin',
            'isExportingMuPlugins'           => $isWholeSite,
            'isExportingThemes'              => $isWholeSite || $updateType === 'theme',
            'isExportingUploads'             => $isWholeSite,
            'isExportingOtherWpContentFiles' => $isWholeSite,
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





    private function sendResult(array $result)
    {
        if ($result['success']) {
            wp_send_json_success($result['data']);
            return;
        }

        wp_send_json_error($result['data']);
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
