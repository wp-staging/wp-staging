<?php

namespace WPStaging\Framework\Filesystem\Filters;

use RecursiveFilterIterator;
use RecursiveIterator;

class RecursiveExtensionExcludeFilter extends RecursiveFilterIterator
{
    protected $exclude = [];

    public function __construct(RecursiveIterator $iterator, $exclude = [])
    {
        parent::__construct($iterator);
        $this->exclude = $exclude;
    }

    public function accept()
    {
        $current = $this->getInnerIterator()->current();

        if ($current->isDir()) {
            return true;
        }

        return !in_array($current->getExtension(), $this->exclude);
    }

    public function getChildren()
    {
        return new self($this->getInnerIterator()->getChildren(), $this->exclude);
    }

}
