<?php

namespace WPStaging\Framework\Filesystem;

class FilesystemScannerDto
{
    /** @var int */
    private $totalDirectories = 0;

    /** @var int */
    private $filesystemSize = 0;

    /** @var int */
    private $discoveredFiles = 0;

    /** @var array */
    private $discoveredFilesArray = [];

    /** @var bool */
    private $isExcludingLogs = false;

    /** @var bool */
    private $isExcludingCaches = false;

    /** @var array */
    private $excludedDirectories = [];

    /**
     * @param int $totalDirectories
     * @return void
     */
    public function setTotalDirectories(int $totalDirectories)
    {
        $this->totalDirectories = $totalDirectories;
    }

    /**
     * @param int $filesystemSize
     * @return void
     */
    public function setFilesystemSize(int $filesystemSize)
    {
        $this->filesystemSize = $filesystemSize;
    }

    /**
     * @param int $discoveredFiles
     * @return void
     */
    public function setDiscoveredFiles(int $discoveredFiles)
    {
        $this->discoveredFiles = $discoveredFiles;
    }

    /**
     * @param array $discoveredFilesArray
     * @return void
     */
    public function setDiscoveredFilesArray(array $discoveredFilesArray)
    {
        $this->discoveredFilesArray = $discoveredFilesArray;
    }

    /**
     * @param bool $isExcludingLogs
     * @return void
     */
    public function setIsExcludingLogs(bool $isExcludingLogs)
    {
        $this->isExcludingLogs = $isExcludingLogs;
    }

    /**
     * @param bool $isExcludingCaches
     * @return void
     */
    public function setIsExcludingCaches(bool $isExcludingCaches)
    {
        $this->isExcludingCaches = $isExcludingCaches;
    }

    /**
     * @param array $excludedDirectories
     * @return void
     */
    public function setExcludedDirectories(array $excludedDirectories)
    {
        $this->excludedDirectories = $excludedDirectories;
    }

    public function getTotalDirectories(): int
    {
        return $this->totalDirectories;
    }

    public function getFilesystemSize(): int
    {
        return $this->filesystemSize;
    }

    public function getDiscoveredFiles(): int
    {
        return $this->discoveredFiles;
    }

    public function getDiscoveredFilesArray(): array
    {
        return $this->discoveredFilesArray;
    }

    public function getIsExcludingLogs(): bool
    {
        return $this->isExcludingLogs;
    }

    public function getIsExcludingCaches(): bool
    {
        return $this->isExcludingCaches;
    }

    public function getExcludedDirectories(): array
    {
        return $this->excludedDirectories;
    }

    /**
     * @param string $category
     * @param int $count
     * @return void
     */
    public function setDiscoveredFilesByCategory(string $category, int $count)
    {
        $this->discoveredFilesArray[$category] = $count;
    }

    public function getDiscoveredFilesByCategory(string $category): int
    {
        return $this->discoveredFilesArray[$category] ?? 0;
    }

    /**
     * @return void
     */
    public function incrementDiscoveredFiles()
    {
        $this->discoveredFiles++;
    }

    /**
     * @return void
     */
    public function incrementTotalDirectories()
    {
        $this->totalDirectories++;
    }

    /**
     * @param string $category
     * @return void
     */
    public function incrementDiscoveredFilesByCategory(string $category)
    {
        $this->discoveredFilesArray[$category] = ($this->discoveredFilesArray[$category] ?? 0) + 1;
    }

    /**
     * @param int $size
     * @return void
     */
    public function addFilesystemSize(int $size)
    {
        $this->filesystemSize += $size;
    }
}
