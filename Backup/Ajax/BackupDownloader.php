<?php
namespace WPStaging\Backup\Ajax;
use WPStaging\Backup\BackupHeader;
use WPStaging\Core\WPStaging;
use WPStaging\Backup\Exceptions\BackupRuntimeException;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Network\RemoteDownloader;
use WPStaging\Framework\Security\Otp\Otp;
use WPStaging\Framework\Security\Otp\OtpDisabledException;
use WPStaging\Framework\Security\Otp\OtpException;
use function WPStaging\functions\debug_log;
class BackupDownloader extends RemoteDownloader
{
    const FILTER_REMOTE_DOWNLOAD_CHUNK_SIZE = 'wpstg.framework.network.ajax_backup_downloader_chunk_size';
    const BACKUP_HEADER_V1_PATTERN = '01101000 01110100 01110100 01110000 01110011 00111010';
    const BACKUP_HEADER_V2_PATTERN = '@^wpstg(\x00)+([0-9a-fA-F]+)@';
    const BACKUP_HEADER_SIZE_FOR_QUICK_VERIFY = 100;
    const OPTION_UPLOAD_PREPARED = 'wpstg.backups.upload_from_url_prepared';
    private $backupsFinder;
    private $filesystem;
    private $backupHeader;
    private $otpService;

    public function __construct(BackupsFinder $backupsFinder, Filesystem $filesystem, BackupHeader $backupHeader, Otp $otpService)
    {
        parent::__construct();
        $this->backupsFinder = $backupsFinder;
        $this->filesystem    = $filesystem;
        $this->backupHeader  = $backupHeader;
        $this->otpService    = $otpService;
    }

    public function ajaxPrepareUpload()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            wp_send_json_error([
                'message' => esc_html__('Invalid Request!', 'wp-staging')
            ], 401);
        }
        try {
            $this->otpService->validateOtpRequest();
        } catch (OtpDisabledException $ex) {
            debug_log($ex->getMessage());
        } catch (OtpException $ex) {
            wp_send_json_error([
                'message' => esc_html($ex->getMessage()),
            ], $ex->getCode());
        }
        $backupUrl     = empty($_REQUEST['backupUrl']) ? '' : sanitize_url($_REQUEST['backupUrl']);
        $remoteFileUrl = strtok($backupUrl, '?#');
        if (!$this->filesystem->isWpstgBackupFile($remoteFileUrl)) {
            wp_send_json_error([
                'message' => esc_html__('Not a valid wpstg backup file', 'wp-staging') . $ex->getMessage(),
            ], 403);
        }
        if ($this->prepareUploadFromUrl($remoteFileUrl)) {
            wp_send_json_success(esc_html__('Backup upload is prepared from url', 'wp-staging'));
        }
        wp_send_json_error([
            'message' => esc_html__('Unable to prepare backup upload from url', 'wp-staging'),
        ], 500);
    }

    public function ajaxDownloadBackupFromRemoteServer()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            return;
        }
        $remoteFileUrl = sanitize_text_field($_POST['backupUrl'] ?? '');
        if (empty($remoteFileUrl)) {
            $this->setFailResponse(__('Backup file URL is empty', 'wp-staging'));
            $this->handleResponse();
            return;
        }
        $remoteFileUrl = strtok($remoteFileUrl, '?#');
        if (!$this->filesystem->isWpstgBackupFile($remoteFileUrl)) {
            $this->setFailResponse(sprintf(__('Invalid backup file extension: %s', 'wp-staging'), basename($remoteFileUrl)));
            $this->handleResponse();
            return;
        }
        $startByte = (int)sanitize_text_field($_POST['startByte'] ?? 0);
        $fileSize  = (int)sanitize_text_field($_POST['fileSize'] ?? 0);
        $this->setDownloadParameters($remoteFileUrl, $startByte, $fileSize);
        try {
            $this->validateIsUploadPrepared();
        } catch (\Exception $e) {
            $this->setFailResponse(__('Invalid Request! Backup upload was not prepared...', 'wp-staging'));
            $this->handleResponse();
            return;
        }
        $this->initDownload();
    }

    protected function prepareUploadFromUrl(string $remoteFileUrl): bool
    {
        $startByte = 0;
        $fileSize  = 0;
        $this->setDownloadParameters($remoteFileUrl, $startByte, $fileSize);
        $uploadPath = $this->localFilePath . '.uploading';
        if (file_exists($uploadPath)) {
            return false;
        }
        delete_option(static::OPTION_UPLOAD_PREPARED);
        update_option(static::OPTION_UPLOAD_PREPARED, $this->fileSize);
        $uploadParent = dirname($uploadPath);
        if (!is_dir($uploadParent)) {
            $this->filesystem->mkdir($uploadParent);
        }
        return @touch($uploadPath);
    }

    private function validateIsUploadPrepared()
    {
        $uploadPath = $this->localFilePath . '.uploading';
        if (!file_exists($uploadPath)) {
            throw new \Exception('Upload file does not exist');
        }
        $remoteFileSize = (int)get_option(static::OPTION_UPLOAD_PREPARED, 0);
        if ($remoteFileSize !== $this->fileSize) {
            throw new \Exception('Remote file size does not match the prepared file size');
        }
    }

    protected function setDownloadParameters(string $remoteFileUrl, int $startByte, int $fileSize)
    {
        $this->setRemoteFileUrl($remoteFileUrl);
        $fileName = basename($remoteFileUrl);
        $this->setFileName($fileName);
        $this->setStartByte($startByte);
        if ($fileSize === 0) {
            $fileSize = $this->getRemoteFileSize();
        }
        $this->setFileSize($fileSize);
        $localFilePath = $this->backupsFinder->getBackupsDirectory() . '/' . $this->getFileName();
        $this->setLocalFilePath($localFilePath);
        $this->setDownloadChunkSize($fileSize);
    }

    private function setDownloadChunkSize(int $fileSize)
    {
        if ($fileSize === 0) {
            return;
        }
        $fileSizeThreshold = 500 * MB_IN_BYTES;
        if ($fileSize < $fileSizeThreshold) {
            return;
        }
        $newChunkSizeInBytes = 25 * MB_IN_BYTES;
        try {
            $newChunkSizeInBytes = Hooks::applyFilters(self::FILTER_REMOTE_DOWNLOAD_CHUNK_SIZE, $newChunkSizeInBytes);
            $newChunkSizeInBytes = absint($newChunkSizeInBytes);
            if (empty($newChunkSizeInBytes) || $newChunkSizeInBytes < MB_IN_BYTES) {
                return;
            }
        } catch (\Throwable $e) {
            }
        $memoryLimit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $availableMemory = absint(($memoryLimit * 20 ) / 100);
        if ($newChunkSizeInBytes > $availableMemory) {
            $newChunkSizeInBytes = $availableMemory;
        }
        $this->setChunkSize($newChunkSizeInBytes);
    }

    private function hasValidBackupContentFromRemoteServer(): bool
    {
        if (!$this->isQuickValidateRemoteBackupHeader()) {
            $this->setFailResponse(__('Invalid backup file content', 'wp-staging'));
            return false;
        }
        return true;
    }

    private function isDownloadHasStarted(): bool
    {
        return empty($_POST['startByte']) || empty($_POST['fileSize']) ? false : true;
    }

    private function isQuickValidateRemoteBackupHeader(): bool
    {
        if ($this->isDownloadHasStarted()) {
            return true;
        }
        $startByte               = 0;
        $endByte                 = self::BACKUP_HEADER_SIZE_FOR_QUICK_VERIFY;
        $remoteFileHeaderContent = $this->getRemoteFileContent($startByte, $endByte);
        $remoteFileHeaderContent = trim($remoteFileHeaderContent);
        if (empty($remoteFileHeaderContent)) {
            return false;
        }
        $sqlDumpHeader = substr(trim(BackupHeader::WPSTG_SQL_BACKUP_DUMP_HEADER), 0, self::BACKUP_HEADER_SIZE_FOR_QUICK_VERIFY);
        if (strpos($remoteFileHeaderContent, $sqlDumpHeader) === 0) {
            return true;
        }
        if (strpos($remoteFileHeaderContent, self::BACKUP_HEADER_V1_PATTERN) === 0) {
            return true;
        }
        if (preg_match(self::BACKUP_HEADER_V2_PATTERN, $remoteFileHeaderContent)) {
            return true;
        }
        return false;
    }

    private function initDownload()
    {
        if (!$this->isDownloadHasStarted() && !$this->remoteFileExists()) {
            $this->handleResponse();
            return;
        }
        if (!$this->hasValidBackupContentFromRemoteServer()) {
            $this->handleResponse();
            return;
        }
        $this->downloadFileChunk();
        if (!$this->getProcessStatus()) {
            $this->updateStartByte();
        }
        if ($this->getProcessStatus()) {
            delete_option(static::OPTION_UPLOAD_PREPARED);
        }
        $this->handleResponse();
    }

    private function setFailResponse(string $message)
    {
        $this->setResponse($message);
        $this->setIsSuccess(false);
        $this->setIsProcessCompleted(true);
    }
}
