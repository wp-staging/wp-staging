<?php

namespace WPStaging\Framework\Network;

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
     * @param BackupsFinder $backupsFinder
     * @throws \WPStaging\Backup\Exceptions\BackupRuntimeException
     */
    public function __construct(BackupsFinder $backupsFinder)
    {
        parent::__construct();
        $this->backupsFinder = $backupsFinder;
        $this->filesystem    = WPStaging::make(Filesystem::class);
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
            $this->setResponse(__('Backup file URL is empty', 'wp-staging'));
            $this->setIsSuccess(false);
            $this->setIsProcessCompleted(true);
            $this->handleResponse();
            return;
        }

        $remoteFileUrl = strtok($remoteFileUrl, '?#');
        if (!$this->filesystem->isWpstgBackupFile($remoteFileUrl)) {
            $this->setResponse(sprintf(__('Invalid backup file extension: %s', 'wp-staging'), basename($remoteFileUrl)));
            $this->setIsSuccess(false);
            $this->setIsProcessCompleted(true);
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
        $startByte = 0;
        $endByte   = 100;
        $content   = $this->getRemoteFileContent($startByte, $endByte);

        // If content empty
        if (empty($content)) {
            $this->setResponse(__('Backup file content empty', 'wp-staging'));
            $this->setIsSuccess(false);
            $this->setIsProcessCompleted(true);
            return false;
        }

        // If soft 404 error page
        if (stripos($content, '<!DOCTYPE') !== false || stripos($content, '<html') !== false) {
            $this->setResponse(__('Invalid backup file content', 'wp-staging'));
            $this->setIsSuccess(false);
            $this->setIsProcessCompleted(true);
            return false;
        }

        // If wpstgBackupHeader.txt exists and compare it to the first 100 bytes of the remote content
        $wpstgBackupHeaderFile = WPSTG_PLUGIN_DIR . 'Backup/wpstgBackupHeader.txt';
        if (file_exists($wpstgBackupHeaderFile)) {
            $wpstgBackupHeaderFileContent = file_get_contents($wpstgBackupHeaderFile, false, null, $startByte, $endByte);
            if (!empty($wpstgBackupHeaderFileContent) && $wpstgBackupHeaderFileContent !== substr($content, $startByte, $endByte)) {
                $this->setResponse(__('Invalid backup file content', 'wp-staging'));
                $this->setIsSuccess(false);
                $this->setIsProcessCompleted(true);
                return false;
            }
        }

        return true;
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
}
