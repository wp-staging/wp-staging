<?php

namespace WPStaging\Framework\Filesystem;

use DirectoryIterator;
use Exception;
use FilesystemIterator;
use IteratorIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use WPStaging\Framework\Filesystem\Filters\DirectoryDotFilter;
use WPStaging\Framework\Filesystem\Filters\PathExcludeFilter;
use WPStaging\Framework\Filesystem\Filters\RecursivePathExcludeFilter;
use WPStaging\Framework\Filesystem\Filters\FileSizeFilter;
use WPStaging\Framework\Filesystem\Filters\RecursiveFileSizeFilter;

class FilterableDirectoryIterator
{
    /**
     * Root path of WP
     * @var string
     */
    private $wpRootPath;

    /**
     * The directory to iterate
     * @var string
     */
    private $directory;

    /**
     * list of files, directories or symlinks paths to be excluded
     * @var array
     */
    private $excludePaths = [];

    /**
     * list of files sizes exclude rules
     * @var array
     */
    private $sizes = [];

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
     * Skip directories with include rules
     * @var bool
     */
    private $skipDirectoriesWithIncludeRules = false;

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
        $this->iteratorMode = RecursiveIteratorIterator::LEAVES_ONLY;
        $this->wpRootPath = ABSPATH;
    }

    /**
     * @return string
     */
    public function getWpRootPath(): string
    {
        return $this->wpRootPath;
    }

    /**
     * @param string $wpRootPath
     * @return static
     */
    public function setWpRootPath(string $wpRootPath)
    {
        $this->wpRootPath = $wpRootPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * @param string $directory
     * @return static
     */
    public function setDirectory(string $directory)
    {
        $this->directory = $directory;
        return $this;
    }

    /**
     * @return bool
     */
    public function isIteratorRecursive(): bool
    {
        return $this->isRecursive;
    }

    /**
     * @param bool $isRecursive
     * @return static
     */
    public function setRecursive(bool $isRecursive = true)
    {
        $this->isRecursive = $isRecursive;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSkipDirectoriesWithIncludeRules(): bool
    {
        return $this->skipDirectoriesWithIncludeRules;
    }

    /**
     * @param bool $skipDirectoriesWithIncludeRules
     * @return static
     */
    public function setSkipDirectoriesWithIncludeRules(bool $skipDirectoriesWithIncludeRules = true)
    {
        $this->skipDirectoriesWithIncludeRules = $skipDirectoriesWithIncludeRules;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDotSkipped(): bool
    {
        return $this->isDotSkip;
    }

    /**
     * @param bool $isDotSkip
     * @return static
     */
    public function setDotSkip(bool $isDotSkip = true)
    {
        $this->isDotSkip = $isDotSkip;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getExcludePaths(): array
    {
        return $this->excludePaths;
    }

    /**
     * @param string[] $paths
     * @return static
     */
    public function setExcludePaths(array $paths)
    {
        $this->excludePaths = $paths;
        return $this;
    }

    /**
     * @param string $path
     * @return static
     */
    public function addExcludePath(string $path)
    {
        $this->excludePaths[] = $path;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getExcludeSizeRules(): array
    {
        return $this->sizes;
    }

    /**
     * @param string[] $rules
     * @return static
     */
    public function setExcludeSizeRules(array $rules)
    {
        $this->sizes = $rules;
        return $this;
    }

    /**
     * @param string $rule
     * @return static
     */
    public function addExcludeSizeRule(string $rule)
    {
        $this->sizes[] = $rule;
        return $this;
    }

    /**
     * @return int
     */
    public function getIteratorMode(): int
    {
        return $this->iteratorMode;
    }

    /**
     * @param int $iteratorMode
     * @return static
     */
    public function setIteratorMode(int $iteratorMode)
    {
        $this->iteratorMode = $iteratorMode;
        return $this;
    }

    /**
     * Get the final iterator for iterations
     * @return RecursiveIteratorIterator|IteratorIterator
     * @throws FilesystemExceptions
     */
    public function get()
    {
        if (!is_dir($this->directory)) {
            throw new FilesystemExceptions(sprintf(__('Directory not found on the given path: %s.', 'wp-staging'), $this->directory));
        }

        try {
            if ($this->isRecursive) {
                return $this->getRecursiveIterator();
            }

            return $this->getIterator();
        } catch (Exception $e) {
            throw new FilesystemExceptions($e->getMessage());
        }
    }

    /**
     * Get recursive iterator for iterations
     * @return RecursiveIteratorIterator
     */
    private function getRecursiveIterator(): RecursiveIteratorIterator
    {
        // force Dot Skip to avoid unlimited loop iteration.
        $this->isDotSkip = true;

        $iterator = new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS);

        if (count($this->sizes) !== 0) {
            $iterator = new RecursiveFileSizeFilter($iterator, $this->sizes);
        }

        if (count($this->excludePaths) !== 0) {
            $iterator = new RecursivePathExcludeFilter($iterator, $this->excludePaths, $this->wpRootPath);
        }

        $iterator = new RecursiveIteratorIterator($iterator, $this->iteratorMode);

        return $iterator;
    }

    /**
     * Get non recursive iterator for iterations
     * @return IteratorIterator
     */
    private function getIterator(): IteratorIterator
    {
        $iterator = new DirectoryIterator($this->directory);

        if ($this->isDotSkip) {
            $iterator = new DirectoryDotFilter($iterator);
        }

        if (count($this->sizes) !== 0) {
            $iterator = new FileSizeFilter($iterator, $this->sizes);
        }

        if (count($this->excludePaths) !== 0) {
            $iterator = new PathExcludeFilter($iterator, $this->excludePaths, $this->wpRootPath, $this->skipDirectoriesWithIncludeRules);
        }

        $iterator = new IteratorIterator($iterator);

        return $iterator;
    }
}
