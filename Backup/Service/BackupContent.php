<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\Dto\File\BackupItemDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Interfaces\IndexLineInterface;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;

/**
 * Class BackupContent
 *
 * List files<BackupItemDto> in the backup with optional paging
 *
 * @package WPStaging\Backup
 */
class BackupContent
{
    /** @var string */
    private $backupFile = '';

    /** @var int */
    private $totalFiles = 0;

    /** @var int */
    private $filesFound = 0;

    /** @var int */
    private $perPage = 20;

    /** @var int */
    private $indexOffsetStart = 0;

    /** @var int */
    private $indexOffsetEnd = 0;

    /** @var int */
    private $indexPage = 0;

    /** @var int */
    private $currentOffset = 0;

    /** @var int */
    private $currentIndex = 0;

    /** @var int */
    private $indexOffset = 0;

    /** @var IndexLineInterface */
    private $indexLineDto;

    /** @var PathIdentifier */
    private $pathIdentifier;

    /** @var array */
    private $filters = [
        'filename' => '',
        'sortby'   => '',
    ];

    /** @var string[] */
    private $databaseFiles = [];

    /**
     * @param string $backupFile
     * @param IndexLineInterface $indexLineDto
     * @param BackupMetadata|null $backupMetadata
     * @return void
     */
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

    /**
     * @param int $perPage
     * @return void
     */
    public function setPerPage(int $perPage)
    {
        $this->perPage = $perPage;
    }

    /**
     * @param PathIdentifier $pathIdentifier
     * @return void
     */
    public function setPathIdentifier(PathIdentifier $pathIdentifier)
    {
        $this->pathIdentifier = $pathIdentifier;
    }

    /**
     * @param string[] $databaseFiles
     * @return void
     */
    public function setDatabaseFiles(array $databaseFiles)
    {
        $this->databaseFiles = $databaseFiles;
    }

    /**
     * @param array $filters { filename: string, sortby: string }
     * @return void
     */
    public function setFilters(array $filters)
    {
        $filters['filename'] = $filters['filename'] ?? '';
        $filters['sortby']   = $filters['sortby'] ?? '';

        $this->filters = $filters;
    }

    /**
     * @param int $indexOffset
     * @return void
     */
    public function setIndexOffset(int $indexOffset)
    {
        $this->indexOffset = $indexOffset;
    }

    /**
     * @param int $page
     * @return \Generator<BackupItemDto>
     */
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

    /**
     * @return array
     */
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

    /**
     * @return int
     */
    public function getIndexOffset(): int
    {
        return $this->indexOffset;
    }

    /**
     * @return int
     */
    public function getCurrentOffset(): int
    {
        return $this->currentOffset;
    }

    /**
     * @param int $currentOffset
     * @return int
     */
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

    /**
     * @param BackupItemDto $backupFile
     * @return bool
     */
    private function isFiltered(BackupItemDto $backupFile): bool
    {
        if ($this->filterByName($backupFile)) {
            return true;
        }

        return $this->filterBySortBy($backupFile);
    }

    /**
     * @param BackupItemDto $backupFile
     * @return bool
     */
    private function filterByName(BackupItemDto $backupFile): bool
    {
        if (empty($this->filters['filename'])) {
            return false;
        }

        return strpos($backupFile->getPath(), $this->filters['filename']) === false;
    }

    /**
     * @param BackupItemDto $backupFile
     * @return bool
     */
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
