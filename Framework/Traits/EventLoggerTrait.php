<?php
namespace WPStaging\Framework\Traits;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Security\Auth;
trait EventLoggerTrait
{
    protected $processStatusFailed = false;
    protected $wpstgMaintenanceFile = 'maintenance';
    protected $backupProcessPrefixIdentifier = 'B';
    protected $restoreProcessPrefixIdentifier = 'R';
    protected $cloneProcessPrefixIdentifier = 'C';
    protected $pushProcessPrefixIdentifier = 'P';
    private $filePath;
    private $filesystem;
    protected $backupSettingsIdentifiers = [
        'isExportingUploads'             => 'U',
        'isExportingThemes'              => 'T',
        'isExportingMuPlugins'           => 'MU',
        'isExportingPlugins'             => 'P',
        'isExportingOtherWpContentFiles' => 'OW',
        'isExportingOtherWpRootFiles'    => 'OR',
        'isExportingDatabase'            => 'D'
    ];
    protected $backupStoragesIdentifiers = [
        'googleDrive'         => 'GD',
        'amazonS3'            => 'AS3',
        'dropbox'             => 'DB',
        'sftp'                => 'S',
        'digitalocean-spaces' => 'DOS',
        'wasabi-s3'           => 'WS3',
        'generic-s3'          => 'GS3',
        'one-drive'           => 'OD',
    ];
    protected $backupUploadPrefixIdentifier = 'BU';
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
        if ($this->process === $this->pushProcessPrefixIdentifier) {
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
        $processPrefix = $this->backupUploadPrefixIdentifier . '|' . $this->prepareJobSettings($this->backupStoragesIdentifiers, $storages);
        $this->writeEventStatus($processPrefix);
    }

    public function logBackupProcessCompleted(array $processSettings = [])
    {
        $processPrefix = $this->backupProcessPrefixIdentifier . '|' . $this->prepareJobSettings($this->backupSettingsIdentifiers, $processSettings);
        $this->writeEventStatus($processPrefix);
    }

    public function logBackupRestoreCompleted($jobBackupMetadata)
    {
        $processSettings = [
            'isExportingPlugins'             => $jobBackupMetadata->getIsExportingPlugins(),
            'isExportingMuPlugins'           => $jobBackupMetadata->getIsExportingMuPlugins(),
            'isExportingThemes'              => $jobBackupMetadata->getIsExportingThemes(),
            'isExportingUploads'             => $jobBackupMetadata->getIsExportingUploads(),
            'isExportingOtherWpContentFiles' => $jobBackupMetadata->getIsExportingOtherWpContentFiles(),
            'isExportingDatabase'            => $jobBackupMetadata->getIsExportingDatabase(),
            'isExportingOtherWpRootFiles'    => $jobBackupMetadata->getIsExportingOtherWpRootFiles(),
        ];
        $processPrefix = $this->restoreProcessPrefixIdentifier . '|' . $this->prepareJobSettings($this->backupSettingsIdentifiers, $processSettings);
        $this->writeEventStatus($processPrefix);
    }

    public function logCloneCompleted()
    {
        $this->writeEventStatus($this->cloneProcessPrefixIdentifier);
    }

    public function logPushCompleted(bool $afterReload = false)
    {
        $processPrefix = $this->pushProcessPrefixIdentifier;
        if ($afterReload) {
            $processPrefix .= 'R';
        }
        $this->writeEventStatus($processPrefix);
    }

    public function logPushCancelled()
    {
        $this->writeEventStatus($this->pushProcessPrefixIdentifier, $this->processStatusFailed);
    }

    public function updateFailedProcess(string $processPrefix = ''): bool
    {
        if (empty($processPrefix)) {
            return false;
        }
        return $this->writeEventStatus($processPrefix, $this->processStatusFailed);
    }

    private function initializeObjects()
    {
        $this->filePath   = WPStaging::make(Directory::class)->getPluginUploadsDirectory() . $this->wpstgMaintenanceFile;
        $this->filesystem = WPStaging::make(Filesystem::class);
    }

    private function writeEventStatus(string $process, bool $status = true): bool
    {
        $this->initializeObjects();
        $content = date('dm') . $process . ($status === $this->processStatusFailed ? '-' : '+');
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

    protected function getProcessPrefix(string $processName): string
    {
        return empty($this->processPrefixes[$processName]) ? '' : $this->processPrefixes[$processName];
    }

    protected function init()
    {
        $this->sanitize        = WPStaging::make(Sanitize::class);
        $this->auth            = WPStaging::make(Auth::class);
        $this->processPrefixes = [
            'backup'  => $this->backupProcessPrefixIdentifier,
            'restore' => $this->restoreProcessPrefixIdentifier,
            'clone'   => $this->cloneProcessPrefixIdentifier,
            'push'    => $this->pushProcessPrefixIdentifier,
        ];
    }
}
