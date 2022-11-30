<?php

namespace WPStaging\Framework\Filesystem\Filters;

use RecursiveFilterIterator;
use RecursiveDirectoryIterator;

class RecursivePathExcludeFilter extends RecursiveFilterIterator
{
    /**
     * @var array
     */
    protected $excludePaths = [];

    /**
     * @var PathFilterHelper
     */
    protected $excludeFilter;

    /**
     * @var PathFilterHelper
     */
    protected $includeFilter;

    /**
     * @var string
     */
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

    /**
     * Set the WP Root Path
     * @param string $wpRootPath
     */
    public function setWpRootPath($wpRootPath)
    {
        $this->wpRootPath = $wpRootPath;
        $this->excludeFilter->setWpRootPath($wpRootPath);
        $this->includeFilter->setWpRootPath($wpRootPath);
    }

    /**
     * Get the WP Root Path
     * @return string
     */
    public function getWpRootPath()
    {
        return $this->wpRootPath;
    }

    #[\ReturnTypeWillChange]
    public function accept()
    {
        // Get the current SplFileInfo object
        $fileInfo = $this->getInnerIterator()->current();
        if ($this->includeFilter->hasRules()) {
            if ($this->includeFilter->isMatched($fileInfo)) {
                return true;
            }

            if ($fileInfo->isDir()) {
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
