<?php

namespace WPStaging\Iterators;

use RecursiveFilterIterator;
use RecursiveIterator;

class RecursiveFilterExclude extends RecursiveFilterIterator
{
    protected $exclude = array();

    public function __construct(RecursiveIterator $iterator, $exclude = array())
    {
        parent::__construct( $iterator );
        $this->exclude = $exclude;
    }

    public function accept()
    {
        $subPath = $this->getInnerIterator()->getSubPathname();

        //  new line character on linux
        if (false !== strpos($subPath, "\n")) {
            return false;
        }
        // new line character on Windows
        if (false !== strpos($subPath, "\r")) {
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
