<?php

namespace WPStaging\Framework\Filesystem\Filters;

use Iterator;
use WPStaging\Framework\Filesystem\Filters\PathFilterHelper;

class PathExcludeFilter extends AbstractFilterIterator
{



    protected $excludeFilter;




    protected $includeFilter;




    protected $skipDirectoriesWithIncludeRules = false;

    public function __construct(Iterator $iterator, $exclude = [], $wpRootPath = ABSPATH, $skipDirectoriesWithIncludeRules = false)
    {
        parent::__construct($iterator);
        $this->excludeFilter = new PathFilterHelper();
        $this->excludeFilter->setWpRootPath($wpRootPath);
        $this->excludeFilter->categorizeRules($exclude);
        $this->includeFilter = new PathFilterHelper($isInclude = true);
        $this->includeFilter->setWpRootPath($wpRootPath);
        $this->includeFilter->categorizeRules($exclude);

        $this->skipDirectoriesWithIncludeRules = $skipDirectoriesWithIncludeRules;
    }





    public function setWpRootPath($wpRootPath)
    {
        $this->excludeFilter->setWpRootPath($wpRootPath);
        $this->includeFilter->setWpRootPath($wpRootPath);
    }





    public function getWpRootPath()
    {
        return $this->excludeFilter->getWpRootPath();
    }

    #[\ReturnTypeWillChange]
    public function accept()
    {
 
        $fileInfo = $this->getInnerIterator()->current();
        if ($this->includeFilter->isMatched($fileInfo)) {
            return true;
        }

        $isDir = $this->isDirSafely($fileInfo);
        if ($isDir === null) {
            return false;
        }

        if ($isDir && !$this->skipDirectoriesWithIncludeRules && $this->includeFilter->hasRules()) {
            return true;
        }

        return !$this->excludeFilter->isMatched($fileInfo);
    }
}
