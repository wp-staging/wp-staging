<?php

namespace WPStaging\Framework\Network;

use WPStaging\Backup\Exceptions\BackupRuntimeException;
use WPStaging\Backup\Service\BackupsFinder;

class AjaxBackupDownloader extends RemoteDownloader
{
    /**
     * @var BackupsFinder
     */
    private $backupsFinder;

    /**
     * @param BackupsFinder $backupsFinder
     * @throws \WPStaging\Backup\Exceptions\BackupRuntimeException
     */
    public function __construct(BackupsFinder $backupsFinder)
    {
        parent::__construct();
        $this->backupsFinder = $backupsFinder;
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
            return;
        }

        $startByte = (int)sanitize_text_field($_POST['startByte'] ?? 0);
        $fileSize = (int)sanitize_text_field($_POST['fileSize'] ?? 0);
        $this->setDownloadParameters($remoteFileUrl, $startByte, $fileSize);
        $this->initDownload();
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

        $this->downloadFileChunk();
        if (!$this->getProcessStatus()) {
            $this->updateStartByte();
        }

        $this->handleResponse();
    }
}
