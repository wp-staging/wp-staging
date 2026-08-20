<?php

namespace WPStaging\Framework\Job\Dto\Traits;

trait FilesystemScannerDtoTrait
{
 
    private $discoveringFilesRequests = 0;

 
    private $discoveredFiles = 0;

 
    private $discoveredFilesIdentifiers = [];

 
    private $totalDirectories = 0;

 
    private $filesystemSize = 0;

 
    private $excludedDirectoriesForScanner = [];

 
    private $tmpExcludedFullPaths = [];




    public function getDiscoveringFilesRequests(): int
    {
        return (int)$this->discoveringFilesRequests;
    }





    public function setDiscoveringFilesRequests(int $discoveringFilesRequests)
    {
        $this->discoveringFilesRequests = $discoveringFilesRequests;
    }




    public function getDiscoveredFiles(): int
    {
        return (int)$this->discoveredFiles;
    }





    public function setDiscoveredFiles(int $discoveredFiles)
    {
        $this->discoveredFiles = $discoveredFiles;
    }




    public function getDiscoveredFilesIdentifiers(): array
    {
        return (array)$this->discoveredFilesIdentifiers;
    }





    public function setDiscoveredFilesIdentifiers(array $discoveredFilesIdentifiers)
    {
        $this->discoveredFilesIdentifiers = $discoveredFilesIdentifiers;
    }






    public function getDiscoveredFilesByIdentifier(string $identifier): int
    {
        if (!array_key_exists($identifier, $this->discoveredFilesIdentifiers)) {
            return 0;
        }

        return (int)$this->discoveredFilesIdentifiers[$identifier];
    }






    public function setDiscoveredFilesByIdentifier(string $identifier, int $discoveredFiles)
    {
        $this->discoveredFilesIdentifiers[$identifier] = $discoveredFiles;
    }




    public function getTotalDirectories(): int
    {
        return (int)$this->totalDirectories;
    }





    public function setTotalDirectories(int $totalDirectories)
    {
        $this->totalDirectories = $totalDirectories;
    }




    public function getFilesystemSize(): int
    {
        return (int)$this->filesystemSize;
    }





    public function setFilesystemSize(int $filesystemSize)
    {
        $this->filesystemSize = $filesystemSize;
    }




    public function getExcludedDirectoriesForScanner(): array
    {
        return (array)$this->excludedDirectoriesForScanner;
    }





    public function setExcludedDirectoriesForScanner(array $excludedDirectoriesForScanner)
    {
        $this->excludedDirectoriesForScanner = $excludedDirectoriesForScanner;
    }





    public function setTmpExcludedFullPaths(array $tmpExcludedFullPaths)
    {
        $this->tmpExcludedFullPaths = $tmpExcludedFullPaths;
    }




    public function getTmpExcludedFullPaths(): array
    {
        return $this->tmpExcludedFullPaths;
    }





    public function mergeTmpExcludedFullPaths(array $tmpExcludedFullPaths)
    {
        $this->tmpExcludedFullPaths = array_merge($this->tmpExcludedFullPaths, $tmpExcludedFullPaths);
    }
}
