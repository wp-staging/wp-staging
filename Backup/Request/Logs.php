<?php

namespace WPStaging\Backup\Request;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\DebugLogReader;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\Nonce;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backend\Modules\SystemInfo;

class Logs
{
 
    private $backupsFinder;

 
    private $nonce;

 
    private $logsDir;

    public function __construct(BackupsFinder $backupsFinder, Nonce $nonce)
    {
        $this->backupsFinder = $backupsFinder;
        $this->nonce = $nonce;
    }

    public function download()
    {
        if (!current_user_can(WPStaging::make(Capabilities::class)->manageWPSTG())) {
            return;
        }

        if (!$this->nonce->requestHasValidNonce('wpstg_log_nonce')) {
            return;
        }

        $md5 = isset($_REQUEST['md5']) ? sanitize_text_field($_REQUEST['md5']) : '';

        if (strlen($md5) !== 32) {
            return;
        }

        $backups = $this->backupsFinder->findBackups();

 
        if (empty($backups)) {
            return;
        }

 
        foreach ($backups as $backup) {
            if ($md5 === md5($backup->getBasename())) {
                $this->downloadLogs($backup);
            }
        }
    }

 
    protected function downloadLogs($backup)
    {
        $file = new FileObject($backup->getPathname(), FileObject::MODE_APPEND_AND_READ);
 
        $metaData = (new BackupMetadata())->hydrateByFile($file);
        $id = $metaData->getId();
        if (empty($id)) {
            $id = $this->extractIdFromBackup($backup->getBasename('.wpstg'));
        }





        $directory = WPStaging::make(Directory::class);
        $this->logsDir = $directory->getLogDirectory();

        $logFile = $this->getBackupLogFileName($id);

        $logPath = $this->logsDir . $logFile;

        $this->downloadHeader($metaData->getName() . '_' . $id);
        $this->downloadSystemInfo();
        echo esc_html("\n\n" . str_repeat("-", 25) . "\n\n");
        print('WP STAGING Backup Log: ' . esc_html($id) . PHP_EOL . PHP_EOL);
        if (!empty($logFile) && file_exists($logPath)) {
            readfile($logPath);
        }

        if (file_exists(WPSTG_DEBUG_LOG_FILE)) {
            print(PHP_EOL . PHP_EOL);
            $this->readDebugLog(8 * 1024 * 2014);
        }

        die();
    }

    private function downloadHeader($backupName)
    {
        header('Content-Description: File Download');
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $backupName . '"_logs.log');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        flush(); 
    }

    protected function readDebugLog($size)
    {
 
        $debugLog = WPStaging::make(DebugLogReader::class);

        $errors = $debugLog->getLastLogEntries($size, true, false);

        echo str_replace(['&quot;', '&#039;', '&amp;'], ['"', "'", "&"], esc_html($errors)); // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped
    }





    protected function extractIdFromBackup($backupName)
    {
        $backupMeta = explode('_', $backupName);
        return trim($backupMeta[count($backupMeta) - 1]);
    }





    protected function getBackupLogFileName($backupId)
    {
 
        $filesystem = WPStaging::make(Filesystem::class);

        $iterator = $filesystem->setRecursive(false)
            ->setDirectory(rtrim($this->logsDir, '/'))
            ->get();

 
        foreach ($iterator as $item) {
            if ($item->getExtension() !== 'log') {
                continue;
            }

            $logFile = $item->getBasename('.log');
            if (strpos($logFile, 'backup_job_') !== 0) {
                continue;
            }

            if (strpos(strrev($logFile), strrev($backupId) . '__') === 0) {
                return $item->getFilename();
            }
        }

        return '';
    }




    private function downloadSystemInfo()
    {
        $systemInfo = WPStaging::make(SystemInfo::class)->get("systemInfo");
        echo str_replace(['&quot;', '&#039;', '&amp;'], ['"', "'", "&"], esc_html(wp_strip_all_tags($systemInfo))); // phpcs:ignore WPStagingCS.Security.EscapeOutput.OutputNotEscaped
    }
}
