<?php

namespace WPStaging\Staging\Dto;








class DirectoryNodeDto
{



    private $name = '';




    private $path = '';




    private $size = 0;




    private $isLink = false;




    private $identifier = '';




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





    public function setName(string $name)
    {
        $this->name = $name;
    }





    public function setPath(string $path)
    {
        $this->path = $path;
    }





    public function setSize(float $size)
    {
        $this->size = $size;
    }





    public function setIsLink(bool $isLink)
    {
        $this->isLink = $isLink;
    }





    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }





    public function setBasePath(string $basePath)
    {
        $this->basePath = $basePath;
    }
}
