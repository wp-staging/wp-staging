<?php

namespace WPStaging\Framework\Job\Interfaces;

interface FilesystemScannerDtoInterface
{
    /** @return int */
    public function getDiscoveringFilesRequests(): int;

    /**
     * @param int $discoveringFilesRequests
     * @return void
     */
    public function setDiscoveringFilesRequests(int $discoveringFilesRequests);

    /** @return int */
    public function getDiscoveredFiles(): int;

    /**
     * @param int $discoveredFiles
     * @return void
     */
    public function setDiscoveredFiles(int $discoveredFiles);

    /** @return array */
    public function getDiscoveredFilesIdentifiers(): array;

    /**
     * @param array $discoveredFilesIdentifiers
     * @return void
     */
    public function setDiscoveredFilesIdentifiers(array $discoveredFilesIdentifiers);

    /**
     * @param string $identifier
     * @return int
     */
    public function getDiscoveredFilesByIdentifier(string $identifier): int;

    /**
     * @param string $identifier
     * @param int $discoveredFiles
     * @return void
     */
    public function setDiscoveredFilesByIdentifier(string $identifier, int $discoveredFiles);

    /** @return int */
    public function getTotalDirectories(): int;

    /**
     * @param int $totalDirectories
     * @return void
     */
    public function setTotalDirectories(int $totalDirectories);

    /** @return int */
    public function getFilesystemSize(): int;

    /**
     * @param int $filesystemSize
     * @return void
     */
    public function setFilesystemSize(int $filesystemSize);

    /** @return string[] */
    public function getExcludedDirectoriesForScanner(): array;

    /**
     * @param string[] $excludedDirectories
     * @return void
     */
    public function setExcludedDirectoriesForScanner(array $excludedDirectories);
}
