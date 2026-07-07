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
use WPStaging\Framework\Network\SsrfProtection;
use function WPStaging\functions\debug_log;
class BackupDownloader
{
    const FILTER_REMOTE_DOWNLOAD_CHUNK_SIZE = 'wpstg.framework.network.ajax_backup_downloader_chunk_size';
    const FILTER_MINIMUM_BACKUP_SIZE_FOR_DYNAMIC_CHUNK_SIZE = 'wpstg.framework.network.ajax_backup_downloader_minimum_size_for_chunk_size_filter';
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
    private $ssrfProtection;
    private $remoteHeaderProbeWasEmpty = false;

    public function __construct(BackupsFinder $backupsFinder, Filesystem $filesystem, Otp $otpService, RemoteDownloader $remoteDownloader, Auth $auth, Sanitize $sanitize, SsrfProtection $ssrfProtection)
    {
        $this->backupsFinder    = $backupsFinder;
        $this->filesystem       = $filesystem;
        $this->otpService       = $otpService;
        $this->remoteDownloader = $remoteDownloader;
        $this->auth             = $auth;
        $this->sanitize         = $sanitize;
        $this->ssrfProtection   = $ssrfProtection;
    }

    public function ajaxPrepareUpload()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            wp_send_json_error([
                'message' => esc_html__('Invalid Request!', 'wp-staging'),
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
        $remoteFileUrl = (string)strtok($backupUrl, '?#');
        if (!$this->filesystem->isWpstgBackupFile($remoteFileUrl)) {
            wp_send_json_error([
                'message' => esc_html__('Not a valid wpstg backup file', 'wp-staging'),
            ], 403);
        }
        if ($this->ssrfProtection->isBlockedUrl($remoteFileUrl)) {
            wp_send_json_error([
                'message' => esc_html__('The URL resolves to a blocked IP address.', 'wp-staging'),
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
        $remoteFileUrl = $this->sanitize->sanitizeUrl($_POST['backupUrl'] ?? '');
        if (empty($remoteFileUrl)) {
            $this->setFailResponse(__('Backup file URL is empty', 'wp-staging'));
            $this->remoteDownloader->writeResponse();
            return;
        }
        $remoteFileUrl = (string)strtok($remoteFileUrl, '?#');
        if (!$this->filesystem->isWpstgBackupFile($remoteFileUrl)) {
            $this->setFailResponse(sprintf(__('Invalid backup file extension: %s', 'wp-staging'), basename($remoteFileUrl)));
            $this->remoteDownloader->writeResponse();
            return;
        }
        if ($this->ssrfProtection->isBlockedUrl($remoteFileUrl)) {
            $this->setFailResponse(__('The URL resolves to a blocked IP address.', 'wp-staging'));
            $this->remoteDownloader->writeResponse();
            return;
        }
        $startByte              = $this->sanitize->sanitizeInt($_POST['startByte'] ?? 0);
        $fileSize               = $this->sanitize->sanitizeInt($_POST['fileSize'] ?? 0);
        $preparedUploadMetadata = $this->getPreparedUploadMetadata();
        $fetchMissingFileSize   = true;
        if ($fileSize === 0 && $this->isPreparedUploadForUrl($preparedUploadMetadata, $remoteFileUrl)) {
            $fileSize             = $preparedUploadMetadata['fileSize'];
            $fetchMissingFileSize = false;
        }
        $this->setDownloadParameters($remoteFileUrl, $startByte, $fileSize, $fetchMissingFileSize);
        try {
            $this->validateIsUploadPrepared($remoteFileUrl);
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
        update_option(static::OPTION_UPLOAD_PREPARED, [
            'url'      => $remoteFileUrl,
            'fileSize' => $this->remoteDownloader->getRemoteFileSize(),
        ]);
        $uploadParent = dirname($uploadPath);
        if (!is_dir($uploadParent)) {
            $this->filesystem->mkdir($uploadParent);
        }
        return @touch($uploadPath);
    }

    protected function setDownloadParameters(
        string $remoteFileUrl,
        int $startByte,
        int $fileSize,
        bool $fetchMissingFileSize = true
    ) {
        $this->remoteDownloader->setAllowUnknownRemoteFileSize(true);
        $this->remoteDownloader->setFollowRedirects(false);
        $this->remoteDownloader->setRemoteUrl($remoteFileUrl);
        $fileName = basename($remoteFileUrl);
        $this->remoteDownloader->setFileName($fileName);
        $this->remoteDownloader->setStartByte($startByte);
        if ($fileSize === 0 && $fetchMissingFileSize) {
            $fileSize = $this->remoteDownloader->fetchRemoteFileSizeWithFallbacks();
        }
        $this->remoteDownloader->setRemoteFileSize($fileSize);
        $localFilePath = $this->backupsFinder->getBackupsDirectory() . '/' . $this->remoteDownloader->getFileName();
        $this->remoteDownloader->setLocalPath($localFilePath);
        $this->setDownloadChunkSize($fileSize);
    }

    private function setDownloadChunkSize(int $fileSize)
    {
        if ($fileSize === 0) {
            $this->setUnknownSizeDownloadChunkSize();
            return;
        }
        $fileSizeThreshold = Hooks::applyFilters(self::FILTER_MINIMUM_BACKUP_SIZE_FOR_DYNAMIC_CHUNK_SIZE, 500 * MB_IN_BYTES);
        if ($fileSize < $fileSizeThreshold) {
            return;
        }
        $newChunkSizeInBytes = 25 * MB_IN_BYTES;
        $newChunkSizeInBytes = $this->applyChunkSizeFilter($newChunkSizeInBytes);
        if (empty($newChunkSizeInBytes) || $newChunkSizeInBytes < MB_IN_BYTES) {
            return;
        }
        $newChunkSizeInBytes = $this->capChunkSizeByAvailableMemory($newChunkSizeInBytes);
        $this->remoteDownloader->setChunkSize($newChunkSizeInBytes);
    }

    private function setUnknownSizeDownloadChunkSize()
    {
        $newChunkSizeInBytes = $this->remoteDownloader->getChunkSize();
        if (empty($newChunkSizeInBytes) || $newChunkSizeInBytes < MB_IN_BYTES) {
            return;
        }
        $newChunkSizeInBytes = $this->applyChunkSizeFilter($newChunkSizeInBytes);
        if (empty($newChunkSizeInBytes) || $newChunkSizeInBytes < MB_IN_BYTES) {
            return;
        }
        $newChunkSizeInBytes = $this->capChunkSizeByAvailableMemory($newChunkSizeInBytes);
        $this->remoteDownloader->setChunkSize($newChunkSizeInBytes);
    }

    private function applyChunkSizeFilter(int $chunkSize): int
    {
        return absint(Hooks::applyFilters(self::FILTER_REMOTE_DOWNLOAD_CHUNK_SIZE, $chunkSize));
    }

    private function capChunkSizeByAvailableMemory(int $chunkSize): int
    {
        $memoryLimit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        if ($memoryLimit <= 0) {
            return $chunkSize;
        }
        $availableMemory = absint(($memoryLimit * 20) / 100);
        if ($availableMemory > 0 && $chunkSize > $availableMemory) {
            return $availableMemory;
        }
        return $chunkSize;
    }

    private function hasValidBackupContentFromRemoteServer(): bool
    {
        if (!$this->isQuickValidateRemoteBackupHeader()) {
            if ($this->remoteHeaderProbeWasEmpty) {
                if (!$this->remoteDownloader->remoteFileExists()) {
                    return false;
                }
                $this->setFailResponse(__('Could not reach or read the remote backup file.', 'wp-staging'));
                return false;
            }
            $this->setFailResponse(__('Invalid backup file content', 'wp-staging'));
            return false;
        }
        return true;
    }

    private function isDownloadStarted(): bool
    {
        $uploadPath = $this->remoteDownloader->getUploadPath();
        if (empty($uploadPath)) {
            return false;
        }
        clearstatcache(true, $uploadPath);
        if (!is_file($uploadPath)) {
            return false;
        }
        $uploadedBytes = filesize($uploadPath);
        return $uploadedBytes !== false && $uploadedBytes > 0;
    }

    private function isQuickValidateRemoteBackupHeader(): bool
    {
        $this->remoteHeaderProbeWasEmpty = false;
        if ($this->isDownloadStarted()) {
            return true;
        }
        $startByte               = 0;
        $endByte                 = self::BACKUP_HEADER_SIZE_FOR_QUICK_VERIFY;
        $remoteFileHeaderContent = $this->remoteDownloader->fetchRemoteFileContent($startByte, $endByte);
        $remoteFileHeaderContent = trim($remoteFileHeaderContent);
        if (empty($remoteFileHeaderContent)) {
            $this->remoteHeaderProbeWasEmpty = true;
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
        if (!$this->hasValidBackupContentFromRemoteServer()) {
            $this->remoteDownloader->writeResponse();
            return;
        }
        $this->remoteDownloader->downloadChunk();
        $this->remoteDownloader->closeFileHandle();
        if ($this->remoteDownloader->getIsSuccess()) {
            $this->remoteDownloader->advanceStartByte();
        }
        if ($this->remoteDownloader->getIsCompleted()) {
            delete_option(static::OPTION_UPLOAD_PREPARED);
        }
        $this->remoteDownloader->writeResponse();
    }

    private function validateIsUploadPrepared(string $remoteFileUrl)
    {
        $uploadPath = $this->remoteDownloader->getUploadPath();
        if (!file_exists($uploadPath)) {
            throw new \Exception('Upload file does not exist');
        }
        $preparedUploadMetadata = $this->getPreparedUploadMetadata();
        if (!empty($preparedUploadMetadata['url']) && $preparedUploadMetadata['url'] !== $remoteFileUrl) {
            throw new \Exception('Remote file URL does not match the prepared URL');
        }
        if ($preparedUploadMetadata['fileSize'] !== $this->remoteDownloader->getRemoteFileSize()) {
            throw new \Exception('Remote file size does not match the prepared file size');
        }
    }

    private function getPreparedUploadMetadata(): array
    {
        $preparedUpload = get_option(static::OPTION_UPLOAD_PREPARED, null);
        if ($preparedUpload === null) {
            return [
                'url'      => '',
                'fileSize' => -1,
            ];
        }
        if (!is_array($preparedUpload)) {
            $fileSize = is_scalar($preparedUpload) ? absint($preparedUpload) : 0;
            return [
                'url'      => '',
                'fileSize' => $fileSize > 0 ? $fileSize : -1,
            ];
        }
        $url      = isset($preparedUpload['url']) ? sanitize_url((string)$preparedUpload['url']) : '';
        $fileSize = isset($preparedUpload['fileSize']) && is_scalar($preparedUpload['fileSize']) ?
            absint($preparedUpload['fileSize']) :
            -1;
        return [
            'url'      => $url,
            'fileSize' => $fileSize === 0 && empty($url) ? -1 : $fileSize,
        ];
    }

    private function isPreparedUploadForUrl(array $preparedUploadMetadata, string $remoteFileUrl): bool
    {
        return !empty($preparedUploadMetadata['url']) && $preparedUploadMetadata['url'] === $remoteFileUrl;
    }

    private function setFailResponse(string $message)
    {
        $this->remoteDownloader->setResponse($message, false, true);
    }
}
