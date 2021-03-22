<?php

namespace WPStaging\Framework\Filesystem\Filters;

class PathExcludeFilter extends \FilterIterator
{
    protected $exclude = [];

    public function __construct(\Iterator $iterator, $exclude = [])
    {
        parent::__construct($iterator);
        $this->exclude = $exclude;
    }

    public function accept()
    {
        $path = $this->getInnerIterator()->getPathname();

        //  new line character on linux
        if (strpos($path, "\n") !== false) {
            return false;
        }
        // new line character on Windows
        if (strpos($path, "\r") !== false) {
            return false;
        }

        if (in_array(wpstg_replace_windows_directory_separator($path), $this->exclude)) {
            return false;
        }

        return true;
    }
}
