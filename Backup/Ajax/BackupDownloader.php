<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\BackupHeader;
use WPStaging\Core\WPStaging;
use WPStaging\Backup\Exceptions\BackupRuntimeException;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Network\RemoteDownloader;
use WPStaging\Framework\Security\Otp\Otp;
use WPStaging\Framework\Security\Otp\OtpDisabledException;
use WPStaging\Framework\Security\Otp\OtpException;

use function WPStaging\functions\debug_log;

class BackupDownloader extends RemoteDownloader
{
    /**
     * @var string
     */
    const OPTION_UPLOAD_PREPARED = 'wpstg.backups.upload_from_url_prepared';

    /**
     * @var BackupsFinder
     */
    private $backupsFinder;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var BackupHeader
     */
    private $backupHeader;

    /**
     * @var Otp
     */
    private $otpService;

    /**
     * @param BackupsFinder $backupsFinder
     * @param Filesystem $filesystem
     * @param BackupHeader $backupHeader
     * @throws \WPStaging\Backup\Exceptions\BackupRuntimeException
     */
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

        $backupUrl = empty($_REQUEST['backupUrl']) ? '' : sanitize_url($_REQUEST['backupUrl']);
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

    /**
     * Initiate the download process
     *
     * @return void
     * @throws \WPStaging\Backup\Exceptions\BackupRuntimeException
     */
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

    /**
     * @return void
     * @throws \Exception
     */
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

    /**
     * @param string $remoteFileUrl
     * @param int $startByte
     * @param int $fileSize
     * @return void
     * @throws BackupRuntimeException
     */
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
    }

    /**
     * @return bool
     */
    private function hasValidBackupContentFromRemoteServer(): bool
    {
        if (!$this->isValidRemoteBackupHeader()) {
            $this->setFailResponse(__('Invalid backup file content', 'wp-staging'));
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    private function isValidRemoteBackupHeader(): bool
    {
        $startByte               = 0;
        $endByte                 = BackupHeader::HEADER_SIZE;
        $remoteFileHeaderContent = $this->getRemoteFileContent($startByte, $endByte);

        // New backup header verification
        try {
            $this->backupHeader->setupBackupHeaderFromRaw($remoteFileHeaderContent);
            if ($this->backupHeader->isValidBackupHeader()) {
                return true;
            }
        } catch (\Throwable $th) {
        }

        if ($this->backupHeader->verifyV1FormatHeader($remoteFileHeaderContent)) {
            return true;
        }

        return false;
    }

    /**
     * Download remote file in chunks using wp_remote_post
     *
     * @return void
     */
    private function initDownload()
    {
        // Early bail if remote file does not exist
        if (!$this->remoteFileExists()) {
            $this->handleResponse();
            return;
        }

        // Early bail if the remote file exists but its contents are not backup files
        if (!$this->hasValidBackupContentFromRemoteServer()) {
            $this->handleResponse();
            return;
        }

        $this->downloadFileChunk();
        if (!$this->getProcessStatus()) {
            $this->updateStartByte();
        }

        // On finish let delete the prepare upload option
        if ($this->getProcessStatus()) {
            delete_option(static::OPTION_UPLOAD_PREPARED);
        }

        $this->handleResponse();
    }

    /**
     * @param  string $message
     * @return void
     */
    private function setFailResponse(string $message)
    {
        $this->setResponse($message);
        $this->setIsSuccess(false);
        $this->setIsProcessCompleted(true);
    }
}
