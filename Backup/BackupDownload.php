<?php

namespace WPStaging\Backup;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Backup\Service\BackupsFinder;

class BackupDownload
{
    public function listenDownload()
    {
        // Early bail: Not a download request.
        if (!isset($_GET['wpstgBackupDownloadMd5'])) {
            return;
        }

        // Early bail: Not enough access to download.
        if (!current_user_can((new Capabilities())->manageWPSTG())) {
            die('Not enough access.');
        }

        // Early bail: Invalid nonce, request does not come from expected context.
        if (!isset($_GET['wpstgBackupDownloadNonce']) || !wp_verify_nonce($_GET['wpstgBackupDownloadNonce'], 'wpstg_download_nonce')) {
            die('Invalid nonce.');
        }

        // Early bail: Invalid MD5.
        $wpstgMd5 = Sanitize::sanitizeString($_GET['wpstgBackupDownloadMd5']);
        if (!isset($_GET['wpstgBackupDownloadMd5']) || !preg_match('/^[a-f0-9]{32}$/', $wpstgMd5)) {
            die('Invalid MD5.');
        }

        try {
            // Not using DI here since this runs on every request, so it can early bail without building dependencies.
            $backup = WPStaging::getInstance()->getContainer()->make(BackupsFinder::class)->findBackupByMd5Hash($wpstgMd5);
        } catch (\Exception $e) {
            die($e->getMessage());
        }

        // Clean the outbut buffer to avoid issues with the file content
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $backup->getBasename() . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $backup->getSize());
        readfile($backup->getPathname());
        exit;
    }
}
