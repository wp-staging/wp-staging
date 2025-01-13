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
    private $backupFile;

    /** @var int */
    private $totalFiles;

    /** @var int */
    private $filesFound;

    /** @var int */
    private $perPage;

    /** @var int */
    private $headerOffset = 0;

    /** @var int */
    private $indexPage = 0;

    /** @var int */
    private $currentOffset = 0;

    /** @var int */
    private $currentIndex = 0;

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

        $this->backupFile   = $backupFile;
        $this->indexLineDto = $indexLineDto;
        $this->totalFiles   = $backupMetadata->getTotalFiles();
        $this->headerOffset = $backupMetadata->getHeaderStart();
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
     * @param int $page
     * @return \Generator<BackupItemDto>
     */
    public function getFiles(int $page = 1)
    {
        if ($page < 1) {
            $page = 1;
        }

        $this->indexPage = $page;

        $offset    = ($page - 1) * $this->perPage;
        $wpstgFile = new FileObject($this->backupFile, 'rb');

        // We will read the file from the beginning
        $wpstgFile->fseek($this->headerOffset);

        $count            = 0;
        $this->filesFound = 0;
        while ($wpstgFile->valid()) {
            $this->currentOffset = $wpstgFile->ftell();
            $this->currentIndex  = $wpstgFile->key();

            $rawIndexFile = $wpstgFile->readAndMoveNext();
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

            $this->filesFound++;
            if ($this->filesFound < $offset || $count === $this->perPage) {
                continue;
            }

            yield $backupFile;
            $count++;
        }
    }

    public function getPagingData(): array
    {
        return [
            'totalIndex'  => $this->filesFound,
            'totalPage'   => ceil($this->filesFound / $this->perPage),
            'indexPage'   => $this->indexPage,
            'indexFilter' => $this->filters['filename'],
            'indexSortby' => $this->filters['sortby'],
        ];
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
