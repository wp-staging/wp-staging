<?php

namespace WPStaging\Framework\Filesystem\Filters;

use RecursiveFilterIterator;
use RecursiveDirectoryIterator;
use WPStaging\Framework\Traits\ExcludeFilterTrait;

class RecursivePathExcludeFilter extends RecursiveFilterIterator
{
    use ExcludeFilterTrait;

    protected $excludePaths = [];

    public function __construct(RecursiveDirectoryIterator $iterator, $excludePaths = [])
    {
        parent::__construct($iterator);
        $this->excludePaths = $excludePaths;
        $this->categorizeExcludes($excludePaths);
    }

    public function accept()
    {
        // Get the current SplFileInfo object
        $fileInfo = $this->getInnerIterator()->current();
        return !$this->isExcluded($fileInfo);
    }

    public function getChildren()
    {
        return new self($this->getInnerIterator()->getChildren(), $this->excludePaths);
    }
}
