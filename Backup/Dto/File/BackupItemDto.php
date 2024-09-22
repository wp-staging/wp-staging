<?php

namespace WPStaging\Backup\Dto\File;

use WPStaging\Backup\Interfaces\IndexLineInterface;

/**
 * This class is used for representation of item/file in the backup when listing that item/file in the UI.
 *
 * @package WPStaging\Backup\Dto\File
 */
class BackupItemDto
{
    /**
     * The offset of the file in the backup
     * @var int
     */
    private $offset;

    /**
     * The line index of the file in the backup
     * @var int
     */
    private $index;

    /** @var string */
    private $identifiablePath;

    /** @var string */
    private $path;

    /** @var string */
    private $size;

    /** @var bool */
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
        $backupFile->setSize($indexLineDto->getUncompressedSize());
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
            0 => $this->index,
            1 => $this->path,
            2 => $this->offset,
            3 => $this->size,
            4 => $this->isDatabase,
            'offset'     => $this->offset,
            'index'      => $this->index,
            'path'       => $this->path,
            'size'       => $this->size,
            'isDatabase' => $this->isDatabase,
        ];
    }
}
