<?php

namespace WPStaging\Framework\Filesystem\Filters;

use FilterIterator;
use Iterator;
use WPStaging\Framework\Traits\ExcludeFilterTrait;

class PathExcludeFilter extends FilterIterator
{
    use ExcludeFilterTrait;

    protected $exclude = [];

    public function __construct(Iterator $iterator, $exclude = [])
    {
        parent::__construct($iterator);
        $this->exclude = $exclude;
        $this->categorizeExcludes($exclude);
    }

    public function accept()
    {
        // Get the current SplFileInfo object
        $fileInfo = $this->getInnerIterator()->current();
        return !$this->isExcluded($fileInfo);
    }
}
