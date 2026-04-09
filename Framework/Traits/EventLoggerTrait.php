<?php
namespace WPStaging\Framework\Traits;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Logger\EventLoggerConst;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Security\Auth;
trait EventLoggerTrait
{
    protected $processStatusFailed = false;
    protected $wpstgMaintenanceFile = 'maintenance';
    private $filePath;
    private $filesystem;
    protected $backupSettingsIdentifiers = [
        'isExportingUploads'             => EventLoggerConst::BACKUP_SETTING_UPLOADS,
        'isExportingThemes'              => EventLoggerConst::BACKUP_SETTING_THEMES,
        'isExportingMuPlugins'           => EventLoggerConst::BACKUP_SETTING_MU_PLUGINS,
        'isExportingPlugins'             => EventLoggerConst::BACKUP_SETTING_PLUGINS,
        'isExportingOtherWpContentFiles' => EventLoggerConst::BACKUP_SETTING_OTHER_WP_CONTENT,
        'isExportingOtherWpRootFiles'    => EventLoggerConst::BACKUP_SETTING_OTHER_ROOT,
        'isExportingDatabase'            => EventLoggerConst::BACKUP_SETTING_DATABASE,
    ];
    protected $backupStoragesIdentifiers = [
        'googleDrive'         => EventLoggerConst::BACKUP_STORAGE_GOOGLE_DRIVE,
        'amazonS3'            => EventLoggerConst::BACKUP_STORAGE_AMAZON_S3,
        'dropbox'             => EventLoggerConst::BACKUP_STORAGE_DROPBOX,
        'sftp'                => EventLoggerConst::BACKUP_STORAGE_SFTP,
        'digitalocean-spaces' => EventLoggerConst::BACKUP_STORAGE_DIGITALOCEAN_SPACES,
        'wasabi-s3'           => EventLoggerConst::BACKUP_STORAGE_WASABI_S3,
        'generic-s3'          => EventLoggerConst::BACKUP_STORAGE_GENERIC_S3,
        'one-drive'           => EventLoggerConst::BACKUP_STORAGE_ONE_DRIVE,
        'pcloud'              => EventLoggerConst::BACKUP_STORAGE_PCLOUD,
    ];
    private $sanitize;
    private $process;
    private $processPrefixes;
    private $auth;

    public function ajaxLogEventSuccess()
    {
        $this->init();
        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }
        $process       = isset($_POST['process']) ? $this->sanitize->sanitizeString($_POST['process']) : '';
        $this->process = $this->getProcessPrefix($process);
        if (empty($this->process)) {
            wp_send_json_error();
        }
        if ($this->process === EventLoggerConst::PROCESS_PREFIX_PUSH) {
            $this->logPushCompleted(true);
            wp_send_json_success();
        }
        $this->writeEventStatus($this->process);
        wp_send_json_success();
    }

    public function ajaxLogEventFailure()
    {
        $this->init();
        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }
        $process       = isset($_POST['process']) ? $this->sanitize->sanitizeString($_POST['process']) : '';
        $this->process = $this->getProcessPrefix($process);
        if (empty($this->process)) {
            wp_send_json_error();
        }
        $response = $this->updateFailedProcess($this->process);
        if ($response) {
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    public function logBackupUploadCompleted(array $storages = [])
    {
        $storages      = array_fill_keys($storages, true);
        $processPrefix = EventLoggerConst::PROCESS_PREFIX_BACKUP_UPLOAD . '|' . $this->prepareJobSettings($this->backupStoragesIdentifiers, $storages);
        $this->writeEventStatus($processPrefix);
    }

    public function logBackupProcessCompleted($backupMeta)
    {
        $this->logProcessFromBackupSettings(EventLoggerConst::PROCESS_PREFIX_BACKUP, $backupMeta);
    }

    public function logBackupRestoreCompleted($backupMeta)
    {
        $this->logProcessFromBackupSettings(EventLoggerConst::PROCESS_PREFIX_RESTORE, $backupMeta);
    }

    public function logRemoteSyncCompleted($backupMeta)
    {
        $this->logProcessFromBackupSettings(EventLoggerConst::PROCESS_PREFIX_REMOTE_SYNC, $backupMeta);
    }

    public function logCloneCompleted(string $processType = EventLoggerConst::PROCESS_PREFIX_CLONE)
    {
        $processType = empty($processType) ? EventLoggerConst::PROCESS_PREFIX_CLONE : $processType;
        $this->writeEventStatus($processType);
    }

    public function logPushCompleted(bool $afterReload = false)
    {
        $processPrefix = EventLoggerConst::PROCESS_PREFIX_PUSH;
        if ($afterReload) {
            $processPrefix = EventLoggerConst::PROCESS_PREFIX_PUSH_RELOAD;
        }
        $this->writeEventStatus($processPrefix);
    }

    public function logPushCancelled()
    {
        $this->writeEventStatus(EventLoggerConst::PROCESS_PREFIX_PUSH, $this->processStatusFailed);
    }

    public function updateFailedProcess(string $processPrefix = ''): bool
    {
        if (empty($processPrefix)) {
            return false;
        }
        return $this->writeEventStatus($processPrefix, $this->processStatusFailed);
    }

    public function logBackupExtractionCompleted()
    {
        $this->writeEventStatus(EventLoggerConst::PROCESS_PREFIX_BACKUP_EXTRACTION);
    }

    private function initializeObjects()
    {
        $this->filePath   = WPStaging::make(Directory::class)->getPluginUploadsDirectory() . $this->wpstgMaintenanceFile;
        $this->filesystem = WPStaging::make(Filesystem::class);
    }

    private function writeEventStatus(string $process, bool $status = true): bool
    {
        $this->initializeObjects();
        $content = date('dmy') . $process . ($status === $this->processStatusFailed ? '-' : '+');
        clearstatcache(true, $this->filePath);
        if (file_exists($this->filePath) && filesize($this->filePath) > 0) {
            $content = "\n" . $content;
        }
        return $this->filesystem->create($this->filePath, $content, 'ab');
    }

    private function prepareJobSettings(array $process, array $processSettings = []): string
    {
        $backupProcessPrefix = '';
        foreach ($process as $processIndex => $processPrefix) {
            if (array_key_exists($processIndex, $processSettings) && $processSettings[$processIndex] === true) {
                $backupProcessPrefix .= $processPrefix;
            }
        }
        return $backupProcessPrefix;
    }

    private function logProcessFromBackupSettings(string $processPrefixIdentifier, $backupMeta)
    {
        $processSettings = $this->extractBackupProcessSettings($backupMeta);
        $processPrefix   = $processPrefixIdentifier . '|' . $this->prepareJobSettings($this->backupSettingsIdentifiers, $processSettings);
        $this->writeEventStatus($processPrefix);
    }

    private function extractBackupProcessSettings($backupMeta): array
    {
        if (!is_object($backupMeta)) {
            return [];
        }
        $getterMap = [
            'isExportingPlugins'             => 'getIsExportingPlugins',
            'isExportingMuPlugins'           => 'getIsExportingMuPlugins',
            'isExportingThemes'              => 'getIsExportingThemes',
            'isExportingUploads'             => 'getIsExportingUploads',
            'isExportingOtherWpContentFiles' => 'getIsExportingOtherWpContentFiles',
            'isExportingDatabase'            => 'getIsExportingDatabase',
            'isExportingOtherWpRootFiles'    => 'getIsExportingOtherWpRootFiles',
        ];
        $settings = [];
        foreach ($getterMap as $settingKey => $getter) {
            if (!is_callable([$backupMeta, $getter])) {
                continue;
            }
            $settings[$settingKey] = $backupMeta->{$getter}();
        }
        return $settings;
    }

    protected function getProcessPrefix(string $processName): string
    {
        return empty($this->processPrefixes[$processName]) ? '' : $this->processPrefixes[$processName];
    }

    protected function init()
    {
        $this->sanitize        = WPStaging::make(Sanitize::class);
        $this->auth            = WPStaging::make(Auth::class);
        $this->processPrefixes = [
            'backup'  => EventLoggerConst::PROCESS_PREFIX_BACKUP,
            'restore' => EventLoggerConst::PROCESS_PREFIX_RESTORE,
            'clone'   => EventLoggerConst::PROCESS_PREFIX_CLONE,
            'push'    => EventLoggerConst::PROCESS_PREFIX_PUSH,
        ];
    }
}
