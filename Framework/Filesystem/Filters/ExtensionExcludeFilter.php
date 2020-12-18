<?php

namespace WPStaging\Framework\Filesystem\Filters;

class ExtensionExcludeFilter extends \FilterIterator
{
    protected $exclude = [];

    public function __construct(\Iterator $iterator, $exclude = [])
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
}
