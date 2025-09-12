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
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Security\Auth;
use function WPStaging\functions\debug_log;
class BackupDownloader
{
    const FILTER_REMOTE_DOWNLOAD_CHUNK_SIZE = 'wpstg.framework.network.ajax_backup_downloader_chunk_size';
    const BACKUP_HEADER_V1_PATTERN = '01101000 01110100 01110100 01110000 01110011 00111010';
    const BACKUP_HEADER_V2_PATTERN = '@^wpstg(\x00)+([0-9a-fA-F]+)@';
    const BACKUP_HEADER_SIZE_FOR_QUICK_VERIFY = 100;
    const OPTION_UPLOAD_PREPARED = 'wpstg.backups.upload_from_url_prepared';
    private $backupsFinder;
    private $filesystem;
    private $otpService;
    private $remoteDownloader;
    private $auth;
    private $sanitize;

    public function __construct(BackupsFinder $backupsFinder, Filesystem $filesystem, Otp $otpService, RemoteDownloader $remoteDownloader, Auth $auth, Sanitize $sanitize)
    {
        $this->backupsFinder    = $backupsFinder;
        $this->filesystem       = $filesystem;
        $this->otpService       = $otpService;
        $this->remoteDownloader = $remoteDownloader;
        $this->auth             = $auth;
        $this->sanitize         = $sanitize;
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
        $remoteFileUrl = $this->sanitize->sanitizeString($_POST['backupUrl'] ?? '');
        if (empty($remoteFileUrl)) {
            $this->setFailResponse(__('Backup file URL is empty', 'wp-staging'));
            $this->remoteDownloader->writeResponse();
            return;
        }
        $remoteFileUrl = strtok($remoteFileUrl, '?#');
        if (!$this->filesystem->isWpstgBackupFile($remoteFileUrl)) {
            $this->setFailResponse(sprintf(__('Invalid backup file extension: %s', 'wp-staging'), basename($remoteFileUrl)));
            $this->remoteDownloader->writeResponse();
            return;
        }
        $startByte = $this->sanitize->sanitizeInt($_POST['startByte'] ?? 0);
        $fileSize  = $this->sanitize->sanitizeInt($_POST['fileSize'] ?? 0);
        $this->setDownloadParameters($remoteFileUrl, $startByte, $fileSize);
        try {
            $this->validateIsUploadPrepared();
        } catch (\Exception $e) {
            $this->setFailResponse(__('Invalid Request! Backup upload was not prepared...', 'wp-staging'));
            $this->remoteDownloader->writeResponse();
            return;
        }
        $this->downloadBackup();
    }

    protected function prepareUploadFromUrl(string $remoteFileUrl): bool
    {
        $this->setDownloadParameters($remoteFileUrl, 0, 0);
        $uploadPath = $this->remoteDownloader->getUploadPath();
        if (file_exists($uploadPath)) {
            return false;
        }
        delete_option(static::OPTION_UPLOAD_PREPARED);
        update_option(static::OPTION_UPLOAD_PREPARED, $this->remoteDownloader->getRemoteFileSize());
        $uploadParent = dirname($uploadPath);
        if (!is_dir($uploadParent)) {
            $this->filesystem->mkdir($uploadParent);
        }
        return @touch($uploadPath);
    }

    protected function setDownloadParameters(string $remoteFileUrl, int $startByte, int $fileSize)
    {
        $this->remoteDownloader->setRemoteUrl($remoteFileUrl);
        $fileName = basename($remoteFileUrl);
        $this->remoteDownloader->setFileName($fileName);
        $this->remoteDownloader->setStartByte($startByte);
        if ($fileSize === 0) {
            $fileSize = $this->remoteDownloader->fetchRemoteFileSize();
        }
        $this->remoteDownloader->setRemoteFileSize($fileSize);
        $localFilePath = $this->backupsFinder->getBackupsDirectory() . '/' . $this->remoteDownloader->getFileName();
        $this->remoteDownloader->setLocalPath($localFilePath);
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
            debug_log('Error applying filter ' . self::FILTER_REMOTE_DOWNLOAD_CHUNK_SIZE . ': ' . $e->getMessage());
        }
        $memoryLimit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $availableMemory = absint(($memoryLimit * 20 ) / 100);
        if ($newChunkSizeInBytes > $availableMemory) {
            $newChunkSizeInBytes = $availableMemory;
        }
        $this->remoteDownloader->setChunkSize($newChunkSizeInBytes);
    }

    private function hasValidBackupContentFromRemoteServer(): bool
    {
        if (!$this->isQuickValidateRemoteBackupHeader()) {
            $this->setFailResponse(__('Invalid backup file content', 'wp-staging'));
            return false;
        }
        return true;
    }

    private function isDownloadStarted(): bool
    {
        return empty($_POST['startByte']) || empty($_POST['fileSize']) ? false : true;
    }

    private function isQuickValidateRemoteBackupHeader(): bool
    {
        if ($this->isDownloadStarted()) {
            return true;
        }
        $startByte               = 0;
        $endByte                 = self::BACKUP_HEADER_SIZE_FOR_QUICK_VERIFY;
        $remoteFileHeaderContent = $this->remoteDownloader->fetchRemoteFileContent($startByte, $endByte);
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

    private function downloadBackup()
    {
        if (!$this->isDownloadStarted() && !$this->remoteDownloader->remoteFileExists()) {
            $this->remoteDownloader->writeResponse();
            return;
        }
        if (!$this->hasValidBackupContentFromRemoteServer()) {
            $this->remoteDownloader->writeResponse();
            return;
        }
        $this->remoteDownloader->downloadChunk();
        $this->remoteDownloader->closeFileHandle();
        if (!$this->remoteDownloader->getIsSuccess()) {
            $this->remoteDownloader->advanceStartByte();
        }
        if ($this->remoteDownloader->getIsCompleted()) {
            delete_option(static::OPTION_UPLOAD_PREPARED);
        }
        $this->remoteDownloader->writeResponse();
    }

    private function validateIsUploadPrepared()
    {
        $uploadPath = $this->remoteDownloader->getUploadPath();
        if (!file_exists($uploadPath)) {
            throw new \Exception('Upload file does not exist');
        }
        $remoteFileSize = (int)get_option(static::OPTION_UPLOAD_PREPARED, 0);
        if ($remoteFileSize !== $this->remoteDownloader->getRemoteFileSize()) {
            throw new \Exception('Remote file size does not match the prepared file size');
        }
    }

    private function setFailResponse(string $message)
    {
        $this->remoteDownloader->setResponse($message, false, true);
    }
}
