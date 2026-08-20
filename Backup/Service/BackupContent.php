<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\Dto\File\BackupItemDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Interfaces\IndexLineInterface;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;








class BackupContent
{
 
    private $backupFile = '';

 
    private $totalFiles = 0;

 
    private $filesFound = 0;

 
    private $perPage = 20;

 
    private $indexOffsetStart = 0;

 
    private $indexOffsetEnd = 0;

 
    private $indexPage = 0;

 
    private $currentOffset = 0;

 
    private $currentIndex = 0;

 
    private $indexOffset = 0;

 
    private $indexLineDto;

 
    private $pathIdentifier;

 
    private $filters = [
        'filename' => '',
        'sortby'   => '',
    ];

 
    private $databaseFiles = [];







    public function setBackup(string $backupFile, IndexLineInterface $indexLineDto, $backupMetadata = null)
    {
        if ($backupMetadata === null) {
            $backupMetadata = new BackupMetadata();
            $backupMetadata = $backupMetadata->hydrateByFilePath($backupFile);
        }

        $this->backupFile       = $backupFile;
        $this->indexLineDto     = $indexLineDto;
        $this->totalFiles       = $backupMetadata->getTotalFiles();
        $this->indexOffsetStart = $backupMetadata->getHeaderStart();
        $this->indexOffsetEnd   = $backupMetadata->getHeaderEnd();
    }





    public function setPerPage(int $perPage)
    {
        $this->perPage = $perPage;
    }





    public function setPathIdentifier(PathIdentifier $pathIdentifier)
    {
        $this->pathIdentifier = $pathIdentifier;
    }





    public function setDatabaseFiles(array $databaseFiles)
    {
        $this->databaseFiles = $databaseFiles;
    }





    public function setFilters(array $filters)
    {
        $filters['filename'] = $filters['filename'] ?? '';
        $filters['sortby']   = $filters['sortby'] ?? '';

        $this->filters = $filters;
    }





    public function setIndexOffset(int $indexOffset)
    {
        $this->indexOffset = $indexOffset;
    }





    public function getFiles(int $page = 1)
    {
        if ($page < 1) {
            $page = 1;
        }

        $indexOffset     = $this->getIndexOffset();
        $this->indexPage = $page;

        $hasFilter  = !empty($this->filters['sortby']) || !empty($this->filters['filename']);
        $maxLine    = ($page - 1) * $this->perPage;
        $objectFile = new FileObject($this->backupFile, 'rb');

        $indexOffsetStart = $this->indexOffsetStart;

        if (!empty($indexOffset) && !$hasFilter) {
            $indexOffsetStart = $indexOffset;
        }

        $objectFile->fseek($indexOffsetStart);

        $countLine        = 0;
        $this->filesFound = $hasFilter ? 0 : $this->totalFiles;
        while ($objectFile->valid()) {
            $this->currentOffset = $objectFile->ftell();
            $this->currentIndex  = $objectFile->key();

            $rawIndexFile = $objectFile->readAndMoveNext();
            if (!$this->indexLineDto->isIndexLine($rawIndexFile)) {
                break;
            }

            $indexLineDto = $this->indexLineDto->readIndexLine($rawIndexFile);
            $backupFile   = BackupItemDto::fromIndexLineDto($indexLineDto);
            $backupFile->setPath($this->pathIdentifier->transformIdentifiableToRelativePath($backupFile->getIdentifiablePath()));
            $backupFile->setOffset($this->currentOffset);
            $backupFile->setIndex($this->currentIndex);

            if ($this->isFiltered($backupFile)) {
                continue;
            }

            if ($hasFilter) {
                $this->filesFound++;
            }

            if ($this->filesFound < $maxLine || $countLine === $this->perPage) {
                if ($hasFilter) {
                    continue;
                } else {
                    break;
                }
            }

            yield $backupFile;
            $countLine++;
        }

        $objectFile = null;
    }




    public function getPagingData(): array
    {
        return [
            'totalIndex'      => $this->filesFound,
            'totalPage'       => ceil($this->filesFound / $this->perPage),
            'indexPage'       => $this->indexPage,
            'indexFilter'     => $this->filters['filename'],
            'indexSortby'     => $this->filters['sortby'],
            'indexOffset'     => $this->getIndexOffset(),
            'indexNextOffset' => $this->getNextOffset($this->getCurrentOffset()),
        ];
    }




    public function getIndexOffset(): int
    {
        return $this->indexOffset;
    }




    public function getCurrentOffset(): int
    {
        return $this->currentOffset;
    }





    public function getNextOffset(int $currentOffset): int
    {
        $objectFile = new FileObject($this->backupFile, 'rb');
        $objectFile->fseek($currentOffset);
        $objectFile->readAndMoveNext();
        $nextOffset = $objectFile->ftell();
        $objectFile = null;

        switch (true) {
            case ($nextOffset > $this->indexOffsetEnd):
                $nextOffset = $this->indexOffsetEnd;
                break;
            case (empty($nextOffset) || $nextOffset < 0):
                $nextOffset = $this->indexOffsetStart;
                break;
        }

        return $nextOffset;
    }





    private function isFiltered(BackupItemDto $backupFile): bool
    {
        if ($this->filterByName($backupFile)) {
            return true;
        }

        return $this->filterBySortBy($backupFile);
    }





    private function filterByName(BackupItemDto $backupFile): bool
    {
        if (empty($this->filters['filename'])) {
            return false;
        }

        return strpos($backupFile->getPath(), $this->filters['filename']) === false;
    }





    private function filterBySortBy(BackupItemDto $backupFile): bool
    {
        if (empty($this->filters['sortby'])) {
            return false;
        }

        if ($this->filters['sortby'] === PartIdentifier::DATABASE_PART_IDENTIFIER) {
            return !in_array($backupFile->getIdentifiablePath(), $this->databaseFiles);
        }

        if ($this->filters['sortby'] === PartIdentifier::UPLOAD_PART_IDENTIFIER && in_array($backupFile->getIdentifiablePath(), $this->databaseFiles)) {
            return true;
        }

        if ($this->filters['sortby'] === PartIdentifier::DROPIN_PART_IDENTIFIER) {
            return !$this->pathIdentifier->hasDropinsFile($backupFile->getIdentifiablePath());
        }

        $identifier = $this->pathIdentifier->getIdentifierByPartName($this->filters['sortby']);

        return $identifier !== $this->pathIdentifier->getIdentifierFromPath($backupFile->getIdentifiablePath());
    }
}
