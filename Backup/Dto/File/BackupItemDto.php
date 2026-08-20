<?php

namespace WPStaging\Backup\Dto\File;

use WPStaging\Backup\Interfaces\IndexLineInterface;






class BackupItemDto
{




    private $offset;





    private $index;

 
    private $identifiablePath;

 
    private $path;

 
    private $size;

 
    private $isDatabase;

    public function __construct()
    {
        $this->offset     = 0;
        $this->index      = 0;
        $this->path       = '';
        $this->size       = '';
        $this->isDatabase = false;
    }

    public static function fromIndexLineDto(IndexLineInterface $indexLineDto): BackupItemDto
    {
        $backupFile = new BackupItemDto();
        $backupFile->setIdentifiablePath($indexLineDto->getIdentifiablePath());
        $backupFile->setSize((string)$indexLineDto->getUncompressedSize());
        $backupFile->setIsDatabase(false);

        return $backupFile;
    }

    public function setOffset(int $offset)
    {
        $this->offset = $offset;
    }

    public function setIndex(int $index)
    {
        $this->index = $index;
    }

    public function setIdentifiablePath(string $identifiablePath)
    {
        $this->identifiablePath = $identifiablePath;
    }

    public function setPath(string $path)
    {
        $this->path = $path;
    }

    public function setSize(string $size)
    {
        $this->size = $size;
    }

    public function setIsDatabase(bool $isDatabase)
    {
        $this->isDatabase = $isDatabase;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getIdentifiablePath(): string
    {
        return $this->identifiablePath;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function isDatabase(): bool
    {
        return $this->isDatabase;
    }

    public function toArray(): array
    {
        return [
            0            => $this->index,
            1            => $this->path,
            2            => $this->offset,
            3            => $this->size,
            4            => $this->isDatabase,
            'offset'     => $this->offset,
            'index'      => $this->index,
            'path'       => $this->path,
            'size'       => $this->size,
            'isDatabase' => $this->isDatabase,
        ];
    }
}
