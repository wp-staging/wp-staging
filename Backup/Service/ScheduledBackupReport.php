<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\BackupScheduler;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Job\Jobs\JobBackup;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Utils\Times;

use function WPStaging\functions\debug_log;






class ScheduledBackupReport
{
 
    const MAX_LISTED_WARNINGS = 50;

 
    const TRANSIENT_LAST_REPORTED_JOB = 'wpstg.backup.schedules.last_reported_job';

 
    private $backupScheduler;

 
    private $directory;

 
    private $times;

    public function __construct(BackupScheduler $backupScheduler, Directory $directory, Times $times)
    {
        $this->backupScheduler = $backupScheduler;
        $this->directory       = $directory;
        $this->times           = $times;
    }








    public function sendReports($jobDataDto = null)
    {
        if (!$this->jobDataDtoDescribesBackupStartedBySchedule($jobDataDto)) {
            return;
        }

        try {
            if (get_transient(self::TRANSIENT_LAST_REPORTED_JOB) === $jobDataDto->getId()) {
                return;
            }

            set_transient(self::TRANSIENT_LAST_REPORTED_JOB, $jobDataDto->getId(), DAY_IN_SECONDS);
            $this->backupScheduler->sendGeneralReport($this->buildStatusMessage($jobDataDto));
            $this->sendLoggedWarningsReport($jobDataDto);
        } catch (\Throwable $e) {
            debug_log('The report of the scheduled backup could not be sent: ' . $e->getMessage());
        }
    }





    private function jobDataDtoDescribesBackupStartedBySchedule($jobDataDto): bool
    {
        return $jobDataDto instanceof JobBackupDataDto
            && !empty($jobDataDto->getScheduleId())
            && !$jobDataDto->getRepeatBackupOnSchedule()
            && !$jobDataDto->getIsBeforeUpdateBackup()
            && $jobDataDto->getTotalBackupSize() > 0;
    }







    private function sendLoggedWarningsReport(JobBackupDataDto $jobDataDto)
    {
        if (get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_WARNING_REPORT) !== 'true') {
            return;
        }

        $warnings = $this->readLoggedWarnings((string)$jobDataDto->getId());
        $this->backupScheduler->sendWarningReport($this->buildWarningMessage($jobDataDto, $warnings));
    }





    private function buildStatusMessage(JobBackupDataDto $jobDataDto): string
    {
        return implode(PHP_EOL, [
            __('The scheduled backup completed successfully.', 'wp-staging'),
            '',
            sprintf(__('Backup: %s', 'wp-staging'), $jobDataDto->getName()),
            sprintf(__('Size: %s', 'wp-staging'), size_format((int)$jobDataDto->getTotalBackupSize(), 2)),
            sprintf(__('Duration: %s', 'wp-staging'), $this->formatDuration($jobDataDto->getDuration())),
        ]);
    }






    private function buildWarningMessage(JobBackupDataDto $jobDataDto, \Generator $warnings): string
    {
        $warningCount   = 0;
        $listedWarnings = '';
        foreach ($warnings as $warning) {
            $warningCount++;
            if ($warningCount <= self::MAX_LISTED_WARNINGS) {
                $listedWarnings .= '- ' . $warning . PHP_EOL;
            }
        }

        if ($warningCount === 0) {
            return '';
        }

        $message = sprintf(
            _n('The scheduled backup "%1$s" completed with %2$d warning:', 'The scheduled backup "%1$s" completed with %2$d warnings:', $warningCount, 'wp-staging'),
            $jobDataDto->getName(),
            $warningCount
        ) . PHP_EOL . PHP_EOL . $listedWarnings;

        $unlistedCount = $warningCount - self::MAX_LISTED_WARNINGS;
        if ($unlistedCount <= 0) {
            return $message;
        }

        return $message . PHP_EOL . sprintf(_n('%d more warning is in the backup log.', '%d more warnings are in the backup log.', $unlistedCount, 'wp-staging'), $unlistedCount);
    }





    private function formatDuration(int $seconds): string
    {
        $duration = gmdate('i:s', $seconds);
        if ($seconds >= HOUR_IN_SECONDS) {
            $duration = intdiv($seconds, HOUR_IN_SECONDS) . ':' . $duration;
        }

        return (string)$this->times->getHumanReadableDuration($duration);
    }





    private function readLoggedWarnings(string $jobId): \Generator
    {
        foreach ($this->findLogFilesOfJob($jobId) as $logFile) {
            yield from $this->readWarningsFromLogFile($logFile);
        }
    }







    private function findLogFilesOfJob(string $jobId): array
    {
        $logDirectory = $this->directory->getLogDirectory();
        $fileNames    = is_dir($logDirectory) ? scandir($logDirectory) : false;
        if (!is_array($fileNames)) {
            return [];
        }

        $prefix   = JobBackup::getJobName() . '__';
        $suffix   = '__' . $jobId . '.log';
        $logFiles = [];
        foreach ($fileNames as $fileName) {
            if (strpos($fileName, $prefix) !== 0 || substr($fileName, -strlen($suffix)) !== $suffix) {
                continue;
            }

            $logFiles[] = $logDirectory . $fileName;
        }

        return $logFiles;
    }





    private function readWarningsFromLogFile(string $logFile): \Generator
    {
        $file = new FileObject($logFile, FileObject::MODE_READ);
        while ($file->valid()) {
            if (preg_match('/^\[warning\]-\[([^\]]*)\] (.*)$/i', rtrim($file->readAndMoveNext()), $matches) !== 1) {
                continue;
            }

            yield sprintf('[%s] %s', $matches[1], $matches[2]);
        }
    }
}
