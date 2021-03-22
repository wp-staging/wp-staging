<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Core\Iterators\RecursiveFilterExclude;
use WPStaging\Framework\Filesystem\Filters\DirectoryDotFilter;
use WPStaging\Framework\Filesystem\Filters\PathExcludeFilter;
use WPStaging\Framework\Filesystem\Filters\ExtensionExcludeFilter;
use WPStaging\Framework\Filesystem\Filters\RecursiveExtensionExcludeFilter;

class FilterableDirectoryIterator
{
    /**
     * The directory to iterate
     * @var string 
     */
    private $directory;

    /**
     * list of files, directories or symlinks paths to be excluded
     * @var array 
     */
    private $paths = [];

    /**
     * list of file extensions to be excluded
     * @var array 
     */
    private $extensions = [];

    /**
     * Iterator recursively including sub folders or only items located in root of $this->$directory
     * @var bool 
     */
    private $isRecursive = false;

    /**
     * skip dot in non recursive iterator depending on value
     * @var bool 
     */
    private $isDotSkip = true;

    /**
     * Possible iterator parameters are
     * RecursiveIteratorIterator::LEAVES_ONLY - The default. Will only fetch items which are files or empty dirs, meaning items which have no child
     * RecursiveIteratorIterator::SELF_FIRST - Lists leaves and parents in iteration with parents coming first. List directory and then the files in there
     * RecursiveIteratorIterator::CHILD_FIRST - Lists leaves and parents in iteration with leaves coming first.List files in subdirectory first, then the directory
     * @var int
     */
    private $iteratorMode;

    /**
     */
    public function __construct()
    {
        $this->iteratorMode = \RecursiveIteratorIterator::LEAVES_ONLY;
    }

    /**
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param string $directory
     * @return self
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
        return $this;
    }

    /**
     * @return bool
     */
    public function isIteratorRecursive()
    {
        return $this->isRecursive;
    }

    /**
     * @param bool $isRecursive
     * @return self
     */
    public function setRecursive($isRecursive = true)
    {
        $this->isRecursive = $isRecursive;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDotSkipped()
    {
        return $this->isDotSkip;
    }

    /**
     * @param bool $isDotSkip
     * @return self
     */
    public function setDotSkip($isDotSkip = true)
    {
        $this->isDotSkip = $isDotSkip;
        return $this;
    }

    /**
     * @return array
     */
    public function getExcludePaths()
    {
        return $this->paths;
    }

    /**
     * @param array $paths
     * @return self
     */
    public function setExcludePaths($paths)
    {
        $this->paths = $paths;
        return $this;
    }

    /**
     * @param string $path
     * @return self
     */
    public function addExcludePath($path)
    {
        $this->paths[] = $path;
        return $this;
    }

    /**
     * @return array
     */
    public function getExcludeExtensions()
    {
        return $this->extensions;
    }

    /**
     * @param array $extensions
     * @return self
     */
    public function setExcludeExtensions($extensions)
    {
        $this->extensions = $extensions;
        return $this;
    }

    /** 
     * @param string $extension
     * @return self
     */
    public function addExcludeExtension($extension)
    {
        $this->extensions[] = $extension;
        return $this;
    }

    /**
     * @return int
     */
    public function getIteratorMode()
    {
        return $this->iteratorMode;
    }

    /**
     * @param int $iteratorMode
     * @return self
     */
    public function setIteratorMode($iteratorMode)
    {
        $this->iteratorMode = $iteratorMode;
        return $this;
    }

    /**
     * Get the final iterator for iterations
     * @return \RecursiveIteratorIterator|\IteratorIterator
     * @throws FilesystemExceptions
     */
    public function get() 
    {
        if (!is_dir($this->directory)) {
            throw new FilesystemExceptions('Directory not found on the given path');
        }

        if ($this->isRecursive) {
            return $this->getRecursiveIterator();
        }

        return $this->getIterator();
    }
	
	/**
     * Get recursive iterator for iterations
     * @return \RecursiveIteratorIterator
     */
    private function getRecursiveIterator()
	{
        // force Dot Skip to avoid unlimited loop iteration.
        $this->isDotSkip = true;

        $iterator = new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS);
        
        if (count($this->paths) !== 0) {
            $iterator = new RecursiveFilterExclude($iterator, $this->paths);
        }

        if (count($this->extensions) !== 0) {
            $iterator = new RecursiveExtensionExcludeFilter($iterator, $this->extensions);
        }

        $iterator = new \RecursiveIteratorIterator($iterator, $this->iteratorMode);
		
		return $iterator;
    }

    /**
     * Get non recursive iterator for iterations
     * @return \IteratorIterator
     */
	private function getIterator() 
	{
        $iterator = new \DirectoryIterator($this->directory);

        if ($this->isDotSkip) {
            $iterator = new DirectoryDotFilter($iterator);
        }
        
        if (count($this->paths) !== 0) {
            $iterator = new PathExcludeFilter($iterator, $this->paths);
        }

        if (count($this->extensions) !== 0) {
            $iterator = new ExtensionExcludeFilter($iterator, $this->extensions);
        }

        $iterator = new \IteratorIterator($iterator);
		
		return $iterator;
    }

    
    
}