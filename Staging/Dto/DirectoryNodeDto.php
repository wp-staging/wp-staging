<?php

namespace WPStaging\Staging\Dto;

/**
 * Class DirectoryNodeDto
 *
 * This is OOP representation of directory scanned in setup step
 *
 * @package WPStaging\Staging\Dto
 */
class DirectoryNodeDto
{
    /**
     * @var string
     */
    private $name = '';

    /**
     * @var string
     */
    private $path = '';

    /**
     * @var float
     */
    private $size = 0;

    /**
     * @var bool
     */
    private $isLink = false;

    /**
     * @var string
     */
    private $identifier = '';

    /**
     * @var string
     */
    private $basePath = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSize(): float
    {
        return $this->size;
    }

    public function isLink(): bool
    {
        return $this->isLink;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string $path
     * @return void
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * @param float $size
     * @return void
     */
    public function setSize(float $size)
    {
        $this->size = $size;
    }

    /**
     * @param bool $isLink
     * @return void
     */
    public function setIsLink(bool $isLink)
    {
        $this->isLink = $isLink;
    }

    /**
     * @param string $identifier
     * @return void
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @param string $basePath
     * @return void
     */
    public function setBasePath(string $basePath)
    {
        $this->basePath = $basePath;
    }
}
