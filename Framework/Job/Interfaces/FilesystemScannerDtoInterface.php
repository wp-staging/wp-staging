<?php

namespace WPStaging\Framework\Job\Interfaces;

interface FilesystemScannerDtoInterface
{
 
    public function getDiscoveringFilesRequests(): int;





    public function setDiscoveringFilesRequests(int $discoveringFilesRequests);

 
    public function getDiscoveredFiles(): int;





    public function setDiscoveredFiles(int $discoveredFiles);

 
    public function getDiscoveredFilesIdentifiers(): array;





    public function setDiscoveredFilesIdentifiers(array $discoveredFilesIdentifiers);





    public function getDiscoveredFilesByIdentifier(string $identifier): int;






    public function setDiscoveredFilesByIdentifier(string $identifier, int $discoveredFiles);

 
    public function getTotalDirectories(): int;





    public function setTotalDirectories(int $totalDirectories);

 
    public function getFilesystemSize(): int;





    public function setFilesystemSize(int $filesystemSize);

 
    public function getExcludedDirectoriesForScanner(): array;





    public function setExcludedDirectoriesForScanner(array $excludedDirectories);

 
    public function getTmpExcludedFullPaths(): array;





    public function setTmpExcludedFullPaths(array $tmpExcludedFullPaths);





    public function mergeTmpExcludedFullPaths(array $tmpExcludedFullPaths);
}
