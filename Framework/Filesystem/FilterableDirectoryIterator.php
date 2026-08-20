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




    private $wpRootPath;





    private $directory;





    private $excludePaths = [];





    private $sizes = [];





    private $isRecursive = false;





    private $isDotSkip = true;





    private $skipDirectoriesWithIncludeRules = false;








    private $iteratorMode;



    public function __construct()
    {
        $this->iteratorMode = RecursiveIteratorIterator::LEAVES_ONLY;
        $this->wpRootPath = ABSPATH;
    }




    public function getWpRootPath(): string
    {
        return $this->wpRootPath;
    }





    public function setWpRootPath(string $wpRootPath)
    {
        $this->wpRootPath = $wpRootPath;
        return $this;
    }




    public function getDirectory(): string
    {
        return $this->directory;
    }





    public function setDirectory(string $directory)
    {
        $this->directory = $directory;
        return $this;
    }




    public function isIteratorRecursive(): bool
    {
        return $this->isRecursive;
    }





    public function setRecursive(bool $isRecursive = true)
    {
        $this->isRecursive = $isRecursive;
        return $this;
    }




    public function isSkipDirectoriesWithIncludeRules(): bool
    {
        return $this->skipDirectoriesWithIncludeRules;
    }





    public function setSkipDirectoriesWithIncludeRules(bool $skipDirectoriesWithIncludeRules = true)
    {
        $this->skipDirectoriesWithIncludeRules = $skipDirectoriesWithIncludeRules;
        return $this;
    }




    public function isDotSkipped(): bool
    {
        return $this->isDotSkip;
    }





    public function setDotSkip(bool $isDotSkip = true)
    {
        $this->isDotSkip = $isDotSkip;
        return $this;
    }




    public function getExcludePaths(): array
    {
        return $this->excludePaths;
    }





    public function setExcludePaths(array $paths)
    {
        $this->excludePaths = $paths;
        return $this;
    }





    public function addExcludePath(string $path)
    {
        $this->excludePaths[] = $path;
        return $this;
    }




    public function getExcludeSizeRules(): array
    {
        return $this->sizes;
    }





    public function setExcludeSizeRules(array $rules)
    {
        $this->sizes = $rules;
        return $this;
    }





    public function addExcludeSizeRule(string $rule)
    {
        $this->sizes[] = $rule;
        return $this;
    }




    public function getIteratorMode(): int
    {
        return $this->iteratorMode;
    }





    public function setIteratorMode(int $iteratorMode)
    {
        $this->iteratorMode = $iteratorMode;
        return $this;
    }






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





    private function getRecursiveIterator(): RecursiveIteratorIterator
    {
 
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
