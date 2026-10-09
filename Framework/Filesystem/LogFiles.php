<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Framework\Adapter\Directory;

class LogFiles
{



    private $logsDirectory;




    private $availableLogFileTypes;




    private $latestLogFiles;




    public function __construct(Directory $directory)
    {
        $this->logsDirectory         = $directory->getLogDirectory();
        $this->availableLogFileTypes = [
            'backup_job',
            'backup_restore',
            'cloning',
            'lowdisk_sync_backup',
            'pull_initiator',
            'push',
            'push_initiator',
            'staging_plugins_updater',
        ];
        $this->latestLogFiles        = [];
    }




    public function getLatestLogFiles(): array
    {
        $logFiles = scandir($this->logsDirectory);
        foreach ($logFiles as $logFile) {
            $this->findLatestLogFiles($logFile);
        }

        return $this->latestLogFiles;
    }





    private function findLatestLogFiles(string $fileName)
    {
        $logFileType = $this->getLogFileType($fileName);
        if ($logFileType === null) {
            return;
        }

        $logFilePath = trailingslashit($this->logsDirectory) . $fileName;
        if (!isset($this->latestLogFiles[$logFileType]) || filemtime($logFilePath) > filemtime($this->latestLogFiles[$logFileType])) {
            $this->latestLogFiles[$logFileType] = $logFilePath;
        }
    }







    private function getLogFileType(string $fileName)
    {
        $matchedType = null;

        foreach ($this->availableLogFileTypes as $logFileType) {
            if (strpos($fileName, $logFileType) !== 0) {
                continue;
            }

            if ($matchedType === null || strlen($logFileType) > strlen($matchedType)) {
                $matchedType = $logFileType;
            }
        }

        return $matchedType;
    }




    public function getLogsDirectory(): string
    {
        return trailingslashit($this->logsDirectory);
    }

    public function getAvailableLogFileTypes(): array
    {
        return $this->availableLogFileTypes;
    }





    public function getRetentionLogFiles(int $days = 14): array
    {
        $logFiles  = [];
        $dayStart  = time() - $days * DAY_IN_SECONDS;

        $dirIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->logsDirectory));
        foreach ($dirIterator as $fileInfo) {
            if (!$fileInfo->isFile() || $fileInfo->isLink() || $fileInfo->getExtension() !== 'log') {
                continue;
            }

            $filePath = $fileInfo->getRealPath();
            $fileName = $fileInfo->getFilename();

            $fileType = $this->getLogFileType($fileName);
            if ($fileType === null) {
                continue;
            }

            $fileMTime = $fileInfo->getMTime();
            if (!$fileMTime) {
                continue;
            }

            if ($fileMTime >= $dayStart) {
                $logFiles[$fileType][] = $filePath;
            }
        }

        return $logFiles;
    }
}
