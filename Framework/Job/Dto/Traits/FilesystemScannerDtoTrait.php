<?php

namespace WPStaging\Framework\Job\Dto\Traits;

trait FilesystemScannerDtoTrait
{
    /** @var int */
    private $discoveringFilesRequests = 0;

    /** @var int */
    private $discoveredFiles = 0;

    /** @var array The number of files the FilesystemScanner discovered in themes,plugins,muplugins,uploads,others */
    private $discoveredFilesIdentifiers = [];

    /** @var int */
    private $totalDirectories = 0;

    /** @var int */
    private $filesystemSize = 0;

    /** @var string[] */
    private $excludedDirectoriesForScanner = [];

    /**
     * @return int
     */
    public function getDiscoveringFilesRequests(): int
    {
        return (int)$this->discoveringFilesRequests;
    }

    /**
     * @param int $discoveringFilesRequests
     * @return void
     */
    public function setDiscoveringFilesRequests(int $discoveringFilesRequests)
    {
        $this->discoveringFilesRequests = $discoveringFilesRequests;
    }

    /**
     * @return int
     */
    public function getDiscoveredFiles(): int
    {
        return (int)$this->discoveredFiles;
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
     * @return array
     */
    public function getDiscoveredFilesIdentifiers(): array
    {
        return (array)$this->discoveredFilesIdentifiers;
    }

    /**
     * @param array $discoveredFilesIdentifiers
     * @return void
     */
    public function setDiscoveredFilesIdentifiers(array $discoveredFilesIdentifiers)
    {
        $this->discoveredFilesIdentifiers = $discoveredFilesIdentifiers;
    }


    /**
     * @param string $identifier
     * @return int
     */
    public function getDiscoveredFilesByIdentifier(string $identifier): int
    {
        if (!array_key_exists($identifier, $this->discoveredFilesIdentifiers)) {
            return 0;
        }

        return (int)$this->discoveredFilesIdentifiers[$identifier];
    }

    /**
     * @param string $identifier
     * @param int $discoveredFiles
     * @return void
     */
    public function setDiscoveredFilesByIdentifier(string $identifier, int $discoveredFiles)
    {
        $this->discoveredFilesIdentifiers[$identifier] = $discoveredFiles;
    }

    /**
     * @return int
     */
    public function getTotalDirectories(): int
    {
        return (int)$this->totalDirectories;
    }

    /**
     * @param int $totalDirectories
     * @return void
     */
    public function setTotalDirectories(int $totalDirectories)
    {
        $this->totalDirectories = $totalDirectories;
    }

    /**
     * @return int
     */
    public function getFilesystemSize(): int
    {
        return (int)$this->filesystemSize;
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
     * @return string[]
     */
    public function getExcludedDirectoriesForScanner(): array
    {
        return (array)$this->excludedDirectoriesForScanner;
    }

    /**
     * @param string[] $excludedDirectoriesForScanner
     * @return void
     */
    public function setExcludedDirectoriesForScanner(array $excludedDirectoriesForScanner)
    {
        $this->excludedDirectoriesForScanner = $excludedDirectoriesForScanner;
    }
}
