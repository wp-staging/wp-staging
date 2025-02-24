<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Framework\Adapter\Directory;

class LogFiles
{
    /**
     * @var string
     */
    private $logsDirectory;

    /**
     * @var array
     */
    private $availableLogFileTypes;

    /**
     * @var array
     */
    private $latestLogFiles;

    /**
     * @param Directory $directory
     */
    public function __construct(Directory $directory)
    {
        $this->logsDirectory         = $directory->getLogDirectory();
        $this->availableLogFileTypes = ['push', 'backup_restore', 'cloning', 'backup_job', 'staging_plugins_updater'];
        $this->latestLogFiles        = [];
    }

    /**
     * @return array
     */
    public function getLatestLogFiles(): array
    {
        $logFiles = scandir($this->logsDirectory);
        foreach ($logFiles as $logFile) {
            $this->findLatestLogFiles($logFile);
        }

        return $this->latestLogFiles;
    }

    /**
     * @param string $fileName
     * @return void
     */
    private function findLatestLogFiles(string $fileName)
    {
        foreach ($this->availableLogFileTypes as $logFilePrefix) {
            if (strpos($fileName, $logFilePrefix) !== 0) {
                continue;
            }

            $logFilePath = trailingslashit($this->logsDirectory) . $fileName;
            if (!isset($this->latestLogFiles[$logFilePrefix]) || filemtime($logFilePath) > filemtime($this->latestLogFiles[$logFilePrefix])) {
                $this->latestLogFiles[$logFilePrefix] = $logFilePath;
            }

            break;
        }
    }

    /**
     * @return string
     */
    public function getLogsDirectory(): string
    {
        return trailingslashit($this->logsDirectory);
    }

    public function getAvailableLogFileTypes(): array
    {
        return $this->availableLogFileTypes;
    }

    /**
     * @param int $days
     * @return array
     */
    public function getRetentionLogFiles(int $days = 14): array
    {
        $logPrefix = implode('|', $this->availableLogFileTypes);
        $logFiles  = [];
        $dayStart  = strtotime('-' . $days);

        $dirIterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->logsDirectory));
        foreach ($dirIterator as $fileInfo) {
            if (!$fileInfo->isFile() || $fileInfo->isLink() || $fileInfo->getExtension() !== 'log') {
                continue;
            }

            $filePath = $fileInfo->getRealPath();
            $fileName = $fileInfo->getFilename();

            if (!preg_match('@^(' . $logPrefix . ')@', $fileName, $matches)) {
                continue;
            }

            $fileType  = $matches[1];
            $fileMTime = $fileInfo->getMTime();
            if (!$fileMTime) {
                continue;
            }

            if ($dayStart >= $fileMTime) {
                $logFiles[$fileType][] = $filePath;
            }
        }

        return $logFiles;
    }
}
