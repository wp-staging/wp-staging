<?php

namespace WPStaging\Framework\Filesystem\Filters;

use FilterIterator;
use Iterator;
use WPStaging\Framework\Filesystem\Filters\PathFilterHelper;

class PathExcludeFilter extends FilterIterator
{
    /**
     * @var PathFilterHelper
     */
    protected $excludeFilter;

    /**
     * @var PathFilterHelper
     */
    protected $includeFilter;

    public function __construct(Iterator $iterator, $exclude = [], $wpRootPath = ABSPATH)
    {
        parent::__construct($iterator);
        $this->excludeFilter = new PathFilterHelper();
        $this->excludeFilter->setWpRootPath($wpRootPath);
        $this->excludeFilter->categorizeRules($exclude);
        $this->includeFilter = new PathFilterHelper($isInclude = true);
        $this->includeFilter->setWpRootPath($wpRootPath);
        $this->includeFilter->categorizeRules($exclude);
    }

    /**
     * Set the WP Root Path
     * @param string $wpRootPath
     */
    public function setWpRootPath($wpRootPath)
    {
        $this->excludeFilter->setWpRootPath($wpRootPath);
        $this->includeFilter->setWpRootPath($wpRootPath);
    }

    /**
     * Get the WP Root Path
     * @return string
     */
    public function getWpRootPath()
    {
        return $this->excludeFilter->getWpRootPath();
    }

    #[\ReturnTypeWillChange]
    public function accept()
    {
        // Get the current SplFileInfo object
        $fileInfo = $this->getInnerIterator()->current();
        if ($this->includeFilter->isMatched($fileInfo)) {
            return true;
        }

        if ($fileInfo->isDir() && $this->includeFilter->hasRules()) {
            return true;
        }

        return !$this->excludeFilter->isMatched($fileInfo);
    }
}
