<?php

namespace WPStaging\Backup\Ajax;

use SplFileInfo;
use Throwable;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Logger\LogFileReader;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\Nonce;
use WPStaging\Framework\TemplateEngine\TemplateEngine;

use function WPStaging\functions\debug_log;




class ScheduleLogs
{
 
    private $backupsFinder;

 
    private $directory;

 
    private $templateEngine;






    public function __construct(BackupsFinder $backupsFinder, Directory $directory, TemplateEngine $templateEngine)
    {
        $this->backupsFinder  = $backupsFinder;
        $this->directory      = $directory;
        $this->templateEngine = $templateEngine;
    }




    public function ajaxGetScheduleLogs()
    {
        if (!current_user_can((new Capabilities())->manageWPSTG())) {
            wp_send_json_error(null, 403);
            return;
        }

        if (!(new Nonce())->requestHasValidNonce(Nonce::WPSTG_NONCE)) {
            wp_send_json_error(null, 403);
            return;
        }

        if (empty($_POST['scheduleId'])) {
            wp_send_json_error(['message' => esc_html__('Missing schedule ID.', 'wp-staging')]);
            return;
        }

        $scheduleId   = Sanitize::sanitizeString($_POST['scheduleId']);
        $lastRunJobId = Sanitize::sanitizeString($_POST['lastRunJobId'] ?? '');
        $logsDir      = $this->directory->getLogDirectory();

        if ($this->sendLogsForJobId($lastRunJobId, $logsDir)) {
            return;
        }

        $backups = $this->backupsFinder->findBackupByScheduleId($scheduleId);

        if (empty($backups)) {
            wp_send_json_error(['message' => esc_html__('No log file found for this schedule.', 'wp-staging')]);
            return;
        }

        $metadata = $this->hydrateMetadataFromLatestBackup($backups);

        if ($metadata === null) {
            wp_send_json_error(['message' => esc_html__('Could not read backup metadata.', 'wp-staging')]);
            return;
        }

        $backupId   = $metadata->getId();
        $backupName = $metadata->getName();

        $logFile = $this->findLogFileName($logsDir, $backupId);
        $logPath = $logsDir . $logFile;

        if (empty($logFile) || !file_exists($logPath)) {
            wp_send_json_error(['message' => esc_html__('No log file found for this schedule.', 'wp-staging')]);
            return;
        }

        wp_send_json_success([
            'logs'         => $this->parseLogContent($logPath),
            'backupName'   => esc_html($backupName),
            'templateHtml' => $this->renderLogsTemplate(),
        ]);
    }









    protected function sendLogsForJobId(string $lastRunJobId, string $logsDir): bool
    {
        if (empty($lastRunJobId)) {
            return false;
        }

        $logFile = $this->findLogFileName($logsDir, $lastRunJobId);
        $logPath = $logsDir . $logFile;

        if (empty($logFile) || !file_exists($logPath)) {
            return false;
        }

        $metadata = $this->findBackupMetadataById($lastRunJobId);
        wp_send_json_success([
            'logs'         => $this->parseLogContent($logPath),
            'backupName'   => $metadata !== null ? esc_html($metadata->getName()) : '',
            'templateHtml' => $this->renderLogsTemplate(),
        ]);

        return true;
    }







    protected function renderLogsTemplate(): string
    {
        return $this->templateEngine->render('logs/logs-template.php', ['logType' => 'schedule']);
    }









    protected function findBackupMetadataById(string $backupId)
    {
        foreach ($this->backupsFinder->findBackups() as $backupFile) {
            try {
                $metadata = (new BackupMetadata())->hydrateByFilePath($backupFile->getPathname());
            } catch (Throwable $ex) {
                debug_log('WP STAGING ScheduleLogs: Could not read metadata - ' . $ex->getMessage());
                continue;
            }

            if ($metadata->getId() === $backupId) {
                return $metadata;
            }
        }

        return null;
    }





    protected function hydrateMetadataFromLatestBackup(array $backups)
    {
        usort($backups, function (SplFileInfo $a, SplFileInfo $b) {
            return $b->getMTime() - $a->getMTime();
        });

 
        $latestBackup = reset($backups);

        try {
            return (new BackupMetadata())->hydrateByFilePath($latestBackup->getPathname());
        } catch (Throwable $ex) {
            debug_log('WP STAGING ScheduleLogs: Could not read metadata - ' . $ex->getMessage());
            return null;
        }
    }






    protected function findLogFileName(string $logsDir, string $backupId): string
    {
        return WPStaging::make(LogFileReader::class)->findLogFileOfJob($logsDir, $backupId, 'backup_job_');
    }







    protected function parseLogContent(string $logPath): array
    {
        return WPStaging::make(LogFileReader::class)->parseLogContent($logPath);
    }
}
