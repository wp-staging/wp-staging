<?php

namespace WPStaging\Core\Iterators;

use RecursiveFilterIterator;
use RecursiveIterator;

// todo refactor this class and its tests to RecursivePathExcludeFilter, move this class and its tests to WPStaging\Framework\Filesystem\Filters  
class RecursiveFilterExclude extends RecursiveFilterIterator
{
    protected $exclude = [];

    public function __construct(RecursiveIterator $iterator, $exclude = [])
    {
        parent::__construct( $iterator );
        $this->exclude = $exclude;
    }

    public function accept()
    {
        $subPath = $this->getInnerIterator()->getSubPathname();

        //  new line character on linux
        if (strpos($subPath, "\n") !== false) {
            return false;
        }
        // new line character on Windows
        if (strpos($subPath, "\r") !== false) {
            return false;
        }

        if (in_array(wpstg_replace_windows_directory_separator($subPath), $this->exclude)) {
            return false;
        }

        return true;
    }

    public function getChildren()
    {
        return new self($this->getInnerIterator()->getChildren(), $this->exclude);
    }

}
