<?php

namespace WPStaging\Framework\Network;

use WPStaging\Backup\BackupHeader;
use WPStaging\Core\WPStaging;
use WPStaging\Backup\Exceptions\BackupRuntimeException;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Framework\Filesystem\Filesystem;

class AjaxBackupDownloader extends RemoteDownloader
{
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
     * @param BackupsFinder $backupsFinder
     * @param Filesystem $filesystem
     * @param BackupHeader $backupHeader
     * @throws \WPStaging\Backup\Exceptions\BackupRuntimeException
     */
    public function __construct(BackupsFinder $backupsFinder, Filesystem $filesystem, BackupHeader $backupHeader)
    {
        parent::__construct();
        $this->backupsFinder = $backupsFinder;
        $this->filesystem    = $filesystem;
        $this->backupHeader  = $backupHeader;
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
        $this->initDownload();
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
