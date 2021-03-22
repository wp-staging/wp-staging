<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Utils\Strings;

/**
 * This trait is used in both RecursivePathExcludeFilter and PathExcludeFilter
 * This will help in keeping the code DRY
 */
trait ExcludeFilterTrait
{
    /**
     * @var array
     */
    private $extensionExcludes;

    /**
     * @var array
     */
    private $absolutePathExcludes;

    /**
     * @var array
     */
    private $anywherePathExcludes;

    /**
     * Categories Exclude in array
     *
     * @param array $excludes
     */
    protected function categorizeExcludes($excludes)
    {
        $this->extensionExcludes = [];
        $this->absolutePathExcludes = [];
        $this->anywherePathExcludes = [];
        $strUtils = new Strings();
        foreach ($excludes as $exclude) {
            // *. is to check whether the exclude is extension exclude or not,
            // If it is extension exclude then add to extension exclude array and move to next exclude
            if ($strUtils->startsWith($exclude, '*.')) {
                $this->extensionExcludes[] = $exclude;
                continue;
            }

            // **/ is to check whether the exclude is anywhere/wildcard exclude or not,
            // If it is anywhere exclude then add to anywhere exclude array and move to next exclude
            if ($strUtils->startsWith($exclude, '**/')) {
                $this->anywherePathExcludes[] = $exclude;
                continue;
            }

            // If the exclude doesn't matches extension or anywhere exclude treat it as absolute path exclude
            $this->absolutePathExcludes[] = $exclude;
        }
    }

    /**
     * Check whether the file extension satisfy any extension exclusion
     *
     * @param string $fileExt
     * @return boolean
     */
    protected function isExcludedExtension($fileExt)
    {
        $fileExt = '*.' . $fileExt;
        return in_array($fileExt, $this->extensionExcludes);
    }

    /**
     * Check whether the given path satisfy any absolute path exclusion
     *
     * @param string $path
     * @return boolean
     */
    protected function isExcludedAbsolutePath($path)
    {
        foreach ($this->absolutePathExcludes as $exclude) {
            if ((new Strings())->startsWith($path, $exclude)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the given path satisfy any anywhere path exclusion
     *
     * @param string $path
     * @return boolean
     */
    protected function isExcludedAnywherePath($path)
    {
        if (is_dir($path)) {
            $path = trailingslashit($path);
        }

        foreach ($this->anywherePathExcludes as $exclude) {
            $exclude = ltrim($exclude, '**');
            if (is_dir($path)) {
                $exclude = trailingslashit($exclude);
            }

            if (strpos($path, $exclude) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the file meets any of the exclude criteria
     * @param SplFileInfo $fileInfo
     *
     * @return boolean
     */
    protected function isExcluded($fileInfo)
    {
        $path = $fileInfo->getPathname();

        // Check extension
        if ($this->isExcludedExtension($fileInfo->getExtension())) {
            return true;
        }

        // Check absolute path
        if ($this->isExcludedAbsolutePath($path)) {
            return true;
        }

        // Check anywhere path
        if ($this->isExcludedAnywherePath($path)) {
            return true;
        }

        return false;
    }
}
