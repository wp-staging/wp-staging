<?php

namespace WPStaging\Backup\Ajax\Backup;

use wpdb;
use WPStaging\Core\WPStaging;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Job\JobBackupProvider;
use WPStaging\Backup\Job\Jobs\JobBackup;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Analytics\ErrorCode;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Job\Ajax\PrepareJob;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\JobTransientCache;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Utils\SlashMode;
use WPStaging\Framework\Utils\Urls;

class PrepareBackup extends PrepareJob
{
 
    private $jobDataDto;

 
    private $jobBackup;

 
    private $urls;

 
    private $wpdb;








    public function __construct(Filesystem $filesystem, Directory $directory, Auth $auth, ProcessLock $processLock, Urls $urls)
    {
        parent::__construct($filesystem, $directory, $auth, $processLock);

        global $wpdb;

        $this->wpdb = $wpdb;
        $this->urls = $urls;
    }





    public function ajaxPrepare($data)
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            wp_send_json_error(null, 401);
        }

        try {
            $this->processLock->lockProcess();
        } catch (ProcessLockedException $e) {
            wp_send_json_error([
                'message' => esc_html__('A backup or restore process is already running.', 'wp-staging') . ' ' . $e->getMessage(),
                'title'   => esc_html__('Backup in Progress', 'wp-staging'),
                'code'    => ErrorCode::PROCESS_LOCKED,
            ], $e->getCode());
        }

        $response = $this->prepare($data);

        if ($response instanceof \WP_Error) {
            wp_send_json_error($response->get_error_message(), $response->get_error_code());
        } else {
            wp_send_json_success();
        }
    }





    public function prepare($data = null)
    {
        if (empty($data) && array_key_exists('wpstgBackupData', $_POST)) {
            $data = Sanitize::sanitizeArray($_POST['wpstgBackupData'], [
                'isExportingPlugins'             => 'bool',
                'isExportingMuPlugins'           => 'bool',
                'isExportingThemes'              => 'bool',
                'isExportingUploads'             => 'bool',
                'isExportingOtherWpContentFiles' => 'bool',
                'isExportingOtherWpRootFiles'    => 'bool',
                'isExportingDatabase'            => 'bool',
                'isAutomatedBackup'              => 'bool',
                'isBeforeUpdateBackup'           => 'bool',
                'repeatBackupOnSchedule'         => 'bool',
                'scheduleRotation'               => 'int',
                'isCreateScheduleBackupNow'      => 'bool',
                'isCreateBackupInBackground'     => 'bool',
                'isSmartExclusion'               => 'bool',
                'isExcludingSpamComments'        => 'bool',
                'isExcludingPostRevision'        => 'bool',
                'isExcludingDeactivatedPlugins'  => 'bool',
                'isExcludingUnusedThemes'        => 'bool',
                'isExcludingLogs'                => 'bool',
                'isExcludingCaches'              => 'bool',
                'isValidateBackupFiles'          => 'bool',
                'backupType'                     => 'string',
                'backupExcludedDirectories'      => 'string',
            ]);
            $data['name'] = isset($_POST['wpstgBackupData']['name']) ? htmlentities(sanitize_text_field($_POST['wpstgBackupData']['name']), ENT_QUOTES) : '';
        }

        try {
            $sanitizedData = $this->setupInitialJob($data);
        } catch (\Exception $e) {
            return new \WP_Error(400, $e->getMessage());
        }

        if (!$this->jobDataDto->getIsSyncRequest()) {
            $this->deleteSseCacheFiles();
        }

        return $sanitizedData;
    }





    protected function setupInitialData($sanitizedData): array
    {
        $sanitizedData = $this->validateAndSanitizeData($sanitizedData);
        $this->clearCacheFolder();

 
        $services = WPStaging::getInstance()->getContainer();
 
        $this->jobDataDto = $services->get(JobBackupDataDto::class);
 
        $this->jobBackup = $services->get(JobBackupProvider::class)->getJob();

        $this->jobDataDto->hydrate($sanitizedData);
        $this->jobDataDto->setInit(true);
        $this->jobDataDto->setFinished(false);
        $this->jobDataDto->setStartTime(time());
        $this->jobDataDto->setIsOnlyUpload(false);

        try {
            $this->jobDataDto->getId();
        } catch (\Exception $e) {
            $this->jobDataDto->setId(substr(md5(mt_rand() . time()), 0, 12));
        }

        if (!$this->jobDataDto->getIsSyncRequest()) {
            $this->jobBackup->getTransientCache()->startJob($this->jobDataDto->getId(), esc_html__('Backup in Progress', 'wp-staging'), JobTransientCache::JOB_TYPE_BACKUP, $this->queueId);
        }

        $this->jobBackup->setJobDataDto($this->jobDataDto);

        return $sanitizedData;
    }





    public function validateAndSanitizeData($data): array
    {
 
 
        if (isset($data['schedule']) && !empty($data['schedule'])) {
            $data['scheduleRecurrence'] = $data['schedule'];
            unset($data['schedule']); 
        }

 
        foreach ($data as $key => $value) {
            if (empty($value)) {
                unset($data[$key]);
            }
        }

        $sites = [];

        if (is_multisite()) {
 
            $sites = $this->wpdb->get_results("SELECT blog_id, site_id, domain, path FROM {$this->wpdb->base_prefix}blogs");
        }

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            $site->site_url = site_url();
            $site->home_url = home_url();
            restore_current_blog();
        }

        $defaults = [
            'id'                             => null,
            'name'                           => $this->urls->getBaseUrlWithoutScheme(),
            'isExportingPlugins'             => false,
            'isExportingMuPlugins'           => false,
            'isExportingThemes'              => false,
            'isExportingUploads'             => false,
            'isExportingOtherWpContentFiles' => false,
            'isExportingOtherWpRootFiles'    => false,
            'isExportingDatabase'            => false,
            'isAutomatedBackup'              => false,
            'isBeforeUpdateBackup'           => false,
            'repeatBackupOnSchedule'         => false,
            'scheduleRecurrence'             => '',
            'scheduleTime'                   => [0, 0],
            'scheduleRotation'               => 1,
 
            'scheduleId'                     => null,
            'storages'                       => [],
            'isCreateScheduleBackupNow'      => false,
            'isCreateBackupInBackground'     => false,
            'sitesToBackup'                  => $sites,
            'backupType'                     => is_multisite() ? BackupMetadata::BACKUP_TYPE_MULTISITE : BackupMetadata::BACKUP_TYPE_SINGLE,
            'subsiteBlogId'                  => get_current_blog_id(),
            'isSmartExclusion'               => false,
            'isExcludingSpamComments'        => false,
            'isExcludingPostRevision'        => false,
            'isExcludingDeactivatedPlugins'  => false,
            'isExcludingUnusedThemes'        => false,
            'isExcludingLogs'                => false,
            'isExcludingCaches'              => false,
            'isValidateBackupFiles'          => false,
            'isWpCliRequest'                 => false,
            'isRestRequest'                  => false,
            'isSyncRequest'                  => false,
            'backupExcludedDirectories'      => '',
            'pushPrepareData'                => [],
        ];

        $data = wp_parse_args($data, $defaults);

 
        $data = array_intersect_key($data, $defaults);

 
        foreach ($defaults as $expectedKey => $value) {
            if (!array_key_exists($expectedKey, $data)) {
                throw new \UnexpectedValueException("Invalid request. Missing '$expectedKey'.");
            }
        }

 
        $data['name'] = substr(sanitize_text_field(html_entity_decode($data['name'])), 0, 100);

 
        $data['name'] = str_replace('\\\'', '\'', $data['name']);

 
        $data['isExportingPlugins']             = $this->jsBoolean($data['isExportingPlugins']);
        $data['isExportingMuPlugins']           = $this->jsBoolean($data['isExportingMuPlugins']);
        $data['isExportingThemes']              = $this->jsBoolean($data['isExportingThemes']);
        $data['isExportingUploads']             = $this->jsBoolean($data['isExportingUploads']);
        $data['isExportingOtherWpContentFiles'] = $this->jsBoolean($data['isExportingOtherWpContentFiles']);
        $data['isExportingOtherWpRootFiles']    = $this->jsBoolean($data['isExportingOtherWpRootFiles']);
        $data['isExportingDatabase']            = $this->jsBoolean($data['isExportingDatabase']);

 
        $data['repeatBackupOnSchedule']    = $this->jsBoolean($data['repeatBackupOnSchedule']);
        $data['scheduleRecurrence']        = sanitize_text_field(html_entity_decode($data['scheduleRecurrence']));
        $data['scheduleRotation']          = absint($data['scheduleRotation']);
        $data['scheduleTime']              = $this->createScheduleTimeArray($data['scheduleTime']);
        $data['isCreateScheduleBackupNow'] = $this->jsBoolean($data['isCreateScheduleBackupNow']);

 
        $data['isValidateBackupFiles'] = $this->jsBoolean($data['isValidateBackupFiles']);

 
        $data['backupType']          = $this->validateAndSanitizeBackupType($data['backupType']);
        $data['isNetworkSiteBackup'] = (is_multisite() && $data['backupType'] !== BackupMetadata::BACKUP_TYPE_MULTISITE) ? true : false;
        if (is_multisite() && $data['backupType'] === BackupMetadata::BACKUP_TYPE_MULTISITE) {
            $data['sitesToBackup'] = $sites;
        }

        if ($data['isNetworkSiteBackup']) {
            $data['subsiteBlogId'] = $this->validateAndSanitizeSubsiteBlogId($data['subsiteBlogId']);
        }

        if (is_string($data['backupExcludedDirectories'])) {
            $data['backupExcludedDirectories'] = $this->directory->getExcludedDirectories($data['backupExcludedDirectories'], SlashMode::BOTH_SLASHES);
        }

 
        $data['isCreateBackupInBackground'] = $this->jsBoolean($data['isCreateBackupInBackground']);

        if (!is_array($data['pushPrepareData'])) {
            $data['pushPrepareData'] = [];
        }

        return $data;
    }





    protected function validateAndSanitizeSubsiteBlogId($subsiteBlogId): int
    {
        if (!is_multisite()) {
            return get_current_blog_id();
        }

        if (!is_numeric($subsiteBlogId)) {
            return get_current_blog_id();
        }

        if ($subsiteBlogId < 0) {
            return get_current_blog_id();
        }

        if (get_blog_details($subsiteBlogId) === false) {
            return get_current_blog_id();
        }

        return $subsiteBlogId;
    }





    protected function validateAndSanitizeBackupType($backupType): string
    {
        if (in_array($backupType, [BackupMetadata::BACKUP_TYPE_SINGLE, BackupMetadata::BACKUP_TYPE_NETWORK_SUBSITE, BackupMetadata::BACKUP_TYPE_MAIN_SITE, BackupMetadata::BACKUP_TYPE_MULTISITE])) {
            return $backupType;
        }

 
 
        if (!is_multisite()) {
            return BackupMetadata::BACKUP_TYPE_SINGLE;
        }

 
        if (!is_main_site()) {
            return BackupMetadata::BACKUP_TYPE_NETWORK_SUBSITE;
        }

 
        return BackupMetadata::BACKUP_TYPE_MULTISITE;
    }






    private function createScheduleTimeArray($scheduleTime): array
    {
        if (empty($scheduleTime)) {
            return [0, 0];
        }

 
        if (is_array($scheduleTime)) {
            $scheduleTime = implode(':', $scheduleTime);
        }

 
        if (preg_match('#\d+:\d+#', $scheduleTime)) {
            $scheduleTime = explode(':', $scheduleTime);
        } else {
            $scheduleTime = [0, 0];
        }

        return $scheduleTime;
    }






    public function getJob()
    {
        return $this->jobBackup;
    }






    public function persist(): bool
    {
        if (!$this->jobBackup instanceof JobBackup) {
            return false;
        }

        $this->jobBackup->persist();

        return true;
    }
}
