<?php

namespace WPStaging\Framework\Filesystem\Filters;

class DirectoryDotFilter extends \FilterIterator
{
    public function __construct(\Iterator $iterator)
    {
        parent::__construct($iterator);
    }

    #[\ReturnTypeWillChange]
    public function accept()
    {
        $current = $this->getInnerIterator()->current();

        if ($current->isDot()) {
            return false;
        }

        return true;
    }
}
