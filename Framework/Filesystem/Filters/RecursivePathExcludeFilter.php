<?php

namespace WPStaging\Framework\Filesystem\Filters;

use RecursiveDirectoryIterator;

class RecursivePathExcludeFilter extends AbstractRecursiveFilterIterator
{



    protected $excludePaths = [];




    protected $excludeFilter;




    protected $includeFilter;




    protected $wpRootPath = ABSPATH;

    public function __construct(RecursiveDirectoryIterator $iterator, $excludePaths = [], $wpRootPath = ABSPATH)
    {
        parent::__construct($iterator);
        $this->excludePaths = $excludePaths;
        $this->excludeFilter = new PathFilterHelper();
        $this->includeFilter = new PathFilterHelper(true);
        $this->setWpRootPath($wpRootPath);
        $this->excludeFilter->categorizeRules($excludePaths);
        $this->includeFilter->categorizeRules($excludePaths);
    }





    public function setWpRootPath($wpRootPath)
    {
        $this->wpRootPath = $wpRootPath;
        $this->excludeFilter->setWpRootPath($wpRootPath);
        $this->includeFilter->setWpRootPath($wpRootPath);
    }





    public function getWpRootPath()
    {
        return $this->wpRootPath;
    }

    #[\ReturnTypeWillChange]
    public function accept()
    {
 
        $fileInfo = $this->getInnerIterator()->current();
        if ($this->includeFilter->hasRules()) {
            if ($this->includeFilter->isMatched($fileInfo)) {
                return true;
            }

            $isDir = $this->isDirSafely($fileInfo);
            if ($isDir === null) {
                return false;
            }

            if ($isDir) {
                return true;
            }
        }

        return !$this->excludeFilter->isMatched($fileInfo);
    }

    #[\ReturnTypeWillChange]
    public function getChildren()
    {
        return new self($this->getInnerIterator()->getChildren(), $this->excludePaths, $this->getWpRootPath());
    }
}
