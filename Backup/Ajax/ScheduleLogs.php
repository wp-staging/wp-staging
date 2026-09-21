<?php

namespace WPStaging\Backup\Ajax;

use SplFileInfo;
use Throwable;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Filesystem\Filesystem;
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
 
        $filesystem = WPStaging::make(Filesystem::class);

        $iterator = $filesystem->setRecursive(false)
            ->setDirectory(rtrim($logsDir, '/'))
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







    protected function parseLogContent(string $logPath): array
    {
        $maxBytes = 100 * 1024;
        $fileSize = (int)filesize($logPath);
        $truncated = $fileSize > $maxBytes;

        if (!$truncated) {
            $content = file_get_contents($logPath);
            $content = $content !== false ? $content : '';
        } else {
            $handle = fopen($logPath, 'rb');
            if ($handle === false) {
                return [];
            }

            fseek($handle, -$maxBytes, SEEK_END);
            $content = fread($handle, $maxBytes);
            fclose($handle);
            $content = $content !== false ? $content : '';
        }

        return $this->parseLogLines($content, $truncated);
    }










    protected function parseLogLines(string $content, bool $truncated): array
    {
        $entries = [];

        if ($truncated) {
            $entries[] = [
                'type'    => 'notice',
                'date'    => '',
                'message' => esc_html__('[Truncated — showing last 100 KB]', 'wp-staging'),
            ];
        }

        foreach (explode(PHP_EOL, $content) as $line) {
            $line = rtrim($line);
            if (empty($line)) {
                continue;
            }

            if (preg_match('/^\[([A-Za-z_]+)\]-\[([^\]]*)\]\s?(.*)$/', $line, $matches)) {
                $entries[] = [
                    'type'    => strtolower($matches[1]),
                    'date'    => $matches[2],
                    'message' => $matches[3],
                ];
            }
        }

        return $entries;
    }
}
