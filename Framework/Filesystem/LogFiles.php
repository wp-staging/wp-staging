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
        $this->availableLogFileTypes = ['push', 'backup_restore', 'cloning', 'backup_job'];
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
}
