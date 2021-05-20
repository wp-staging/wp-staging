<?php

namespace WPStaging\Framework\Filesystem\Filters;

use SplFileInfo;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Utils\ThirdParty\Glob;

/**
 * This helper is used in both RecursivePathExcludeFilter and PathExcludeFilter
 * This will help in keeping the code DRY
 */
class PathFilterHelper
{
    /**
     * @var array
     */
    private $absolutePathRules;

    /**
     * @var array
     */
    private $globRules;

    /**
     * @var string
     */
    private $wpRootPath;

    /**
     * @var Strings
     */
    private $strUtils;

    /**
     * @var boolean
     */
    private $isInclude;

    /**
     * @var int
     */
    private $rulesCount;

    public function __construct($isInclude = false)
    {
        $this->isInclude = $isInclude;
        $this->strUtils = new Strings();
        $this->setWpRootPath(ABSPATH);
    }

    /**
     * Set the WP Root Path
     * Use this method for staging site or mocking
     * @param string $wpRootPath
     */
    public function setWpRootPath($wpRootPath)
    {
        $this->wpRootPath = $this->strUtils->sanitizeDirectorySeparator($wpRootPath);
        $this->wpRootPath = rtrim($this->wpRootPath, '/');
    }

    /**
     * Get the WP Root Path
     * @return string
     */
    public function getWpRootPath()
    {
        return $this->wpRootPath;
    }

    /**
     * Categories Rules
     *
     * @param array $excludes
     */
    public function categorizeRules($rules)
    {
        $this->absolutePathRules = [];
        $this->globRules = [];
        $this->rulesCount = 0;
        foreach ($rules as $rule) {
            if (empty($rule) || $rule === '') {
                continue;
            }

            // Skip if include rule but helper is for exclude
            if (!$this->isInclude && $this->strUtils->startsWith($rule, '!')) {
                continue;
            }

            // Skip if exclude rule but helper is for include
            if ($this->isInclude && !$this->strUtils->startsWith($rule, '!')) {
                continue;
            }

            if ($this->isInclude) {
                $rule = ltrim($rule, '!');
            }

            // If the rule starts with / and doesn't have any glob character add it to absoluate path array and move to next rule
            if ($this->strUtils->startsWith($rule, '/') && !$this->isGlobPattern($rule)) {
                $this->absolutePathRules[] = $rule;
                $this->rulesCount++;
                continue;
            }

            // *. is to check whether the exclude is extension exclude or not,
            // If it is extension exclude then convert to glob pattern
            if ($this->strUtils->startsWith($rule, '*.')) {
                $rule = '/**/' . $rule;
            }

            // **/ is to check whether the exclude is anywhere/wildcard exclude or not,
            // If it is anywhere exclude then convert to glob pattern
            if ($this->strUtils->startsWith($rule, '**/')) {
                $rule = '/' . $rule;
            }

            // convert path to wildcard glob path if it doesn't have any glob character
            if (!$this->isGlobPattern($rule)) {
                $rule = '/**/' . $rule;
            }

            // make exclude works for its children if it is for directory
            if ($this->isChildrenMatchingAllow($rule)) {
                $this->globRules[] = $this->wildcardGlobToRegex(Glob::toRegex($rule . '/**'));
                $this->rulesCount++;
            }

            // If the exclude doesn't matches extension or anywhere exclude treat it as glob exclude
            $this->globRules[] = $this->wildcardGlobToRegex(Glob::toRegex($rule));
            $this->rulesCount++;
        }
    }

    /**
     * Check whether the file meets any of the exclude criteria
     * @param SplFileInfo $fileInfo
     *
     * @return boolean
     */
    public function isMatched($fileInfo)
    {
        $path = $fileInfo->getPathname();
        $path = $this->strUtils->sanitizeDirectorySeparator($path);
        $relpath = str_replace($this->wpRootPath, '', $path);

        // Check absolute path
        if ($this->isAbsolutePathMatched($relpath)) {
            return true;
        }

        // Check glob pattern but only check against in relative path to wordpress installation
        if ($this->isGlobPatternMatched($relpath)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether there is any rule or not.
     *
     * @return boolean
     */
    public function hasRules()
    {
        return $this->rulesCount > 0;
    }

    /**
     * Check whether the given path satisfy any absolute path exclusion
     *
     * @param string $path
     * @return boolean
     */
    protected function isAbsolutePathMatched($path)
    {
        // will check if path matches
        if (in_array($path, $this->absolutePathRules)) {
            return true;
        }

        // will check if path children match
        foreach ($this->absolutePathRules as $rule) {
            if ($this->strUtils->startsWith($path, $rule . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the given path satisfy any glob pattern
     *
     * @param string $path
     * @return boolean
     */
    protected function isGlobPatternMatched($path)
    {
        foreach ($this->globRules as $rule) {
            if (preg_match($rule, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the path is glob pattern
     *
     * @param string $pattern
     * @return boolean
     */
    protected function isGlobPattern($pattern)
    {
        return false !== strpos($pattern, '*') || false !== strpos($pattern, '{') || false !== strpos($pattern, '?') || false !== strpos($pattern, '[');
    }

    /**
     * Convert symfony glob pattern converted regex to support wildcard path regex
     *
     * @param string $pattern
     * @return string
     */
    protected function wildcardGlobToRegex($pattern)
    {
        // Make /**/ to work as anywhere exclude for Symfony Glob
        if (strpos($pattern, '/(?=[^\.])[^/]*[^/]*/(?=[^\.])') !== false) {
            $pattern = str_replace('/(?=[^\.])[^/]*[^/]*/(?=[^\.])', '/([^/]+/)*', $pattern);
        }

        // Make /** to allow exclude/include children as well
        if (strpos($pattern, '/(?=[^\.])[^/]*[^/]*') !== false) {
            $pattern = str_replace('/(?=[^\.])[^/]*[^/]*', '/(.*)', $pattern);
        }

        return $pattern;
    }

    /**
     * Is /** be appended to allow matching of children
     *
     * @param string $rule
     * @return boolean
     */
    protected function isChildrenMatchingAllow($rule)
    {
        $rule = rtrim($rule, '/');
        $segments = explode('/', $rule);
        $lastSegment = $segments[count($segments) - 1];
        // Already allowed
        if ($lastSegment === '**') {
            return false;
        }

        return (strpos($lastSegment, '.') === false);
    }
}
