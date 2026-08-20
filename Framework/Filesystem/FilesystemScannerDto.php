<?php

namespace WPStaging\Framework\Filesystem;

class FilesystemScannerDto
{
 
    private $totalDirectories = 0;

 
    private $filesystemSize = 0;

 
    private $discoveredFiles = 0;

 
    private $discoveredFilesArray = [];

 
    private $isExcludingLogs = false;

 
    private $isExcludingCaches = false;

 
    private $excludedDirectories = [];

 
    private $filesExcludedInRequest = [];





    public function setTotalDirectories(int $totalDirectories)
    {
        $this->totalDirectories = $totalDirectories;
    }





    public function setFilesystemSize(int $filesystemSize)
    {
        $this->filesystemSize = $filesystemSize;
    }





    public function setDiscoveredFiles(int $discoveredFiles)
    {
        $this->discoveredFiles = $discoveredFiles;
    }





    public function setDiscoveredFilesArray(array $discoveredFilesArray)
    {
        $this->discoveredFilesArray = $discoveredFilesArray;
    }





    public function setIsExcludingLogs(bool $isExcludingLogs)
    {
        $this->isExcludingLogs = $isExcludingLogs;
    }





    public function setIsExcludingCaches(bool $isExcludingCaches)
    {
        $this->isExcludingCaches = $isExcludingCaches;
    }





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






    public function setDiscoveredFilesByCategory(string $category, int $count)
    {
        $this->discoveredFilesArray[$category] = $count;
    }

    public function getDiscoveredFilesByCategory(string $category): int
    {
        return $this->discoveredFilesArray[$category] ?? 0;
    }




    public function incrementDiscoveredFiles()
    {
        $this->discoveredFiles++;
    }




    public function incrementTotalDirectories()
    {
        $this->totalDirectories++;
    }





    public function incrementDiscoveredFilesByCategory(string $category)
    {
        $this->discoveredFilesArray[$category] = ($this->discoveredFilesArray[$category] ?? 0) + 1;
    }





    public function addFilesystemSize(int $size)
    {
        $this->filesystemSize += $size;
    }

    public function getFilesExcludedInRequest(): array
    {
        return $this->filesExcludedInRequest;
    }





    public function setFilesExcludedInRequest(array $filesExcludedInRequest)
    {
        $this->filesExcludedInRequest = $filesExcludedInRequest;
    }





    public function addFileExcludedInRequest(string $filePath)
    {
        $this->filesExcludedInRequest[] = $filePath;
    }
}
