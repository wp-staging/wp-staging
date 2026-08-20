<?php

namespace WPStaging\Framework\Filesystem\Filters;

use SplFileInfo;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\Glob;
use WPStaging\Framework\Utils\Strings;





class PathFilterHelper
{



    private $absolutePathRules;




    private $globRules;




    private $wpRootPath;




    private $strUtils;




    private $isInclude;




    private $rulesCount;

    public function __construct($isInclude = false)
    {
        $this->isInclude = $isInclude;
        $this->strUtils = new Strings();
        $this->setWpRootPath(ABSPATH);
    }






    public function setWpRootPath($wpRootPath)
    {
        $this->wpRootPath = wp_normalize_path($wpRootPath);
        $this->wpRootPath = rtrim($this->wpRootPath, '/');
    }





    public function getWpRootPath()
    {
        return $this->wpRootPath;
    }






    public function categorizeRules($rules)
    {
        $this->absolutePathRules = [];
        $this->globRules = [];
        $this->rulesCount = 0;
        foreach ($rules as $rule) {
            if (empty($rule) || $rule === '') {
                continue;
            }

 
            if (!$this->isInclude && $this->strUtils->startsWith($rule, '!')) {
                continue;
            }

 
            if ($this->isInclude && !$this->strUtils->startsWith($rule, '!')) {
                continue;
            }

            if ($this->isInclude) {
                $rule = ltrim($rule, '!');
            }

 
            if ($this->strUtils->startsWith($rule, '/') && !$this->isGlobPattern($rule)) {
                $this->absolutePathRules[] = $rule;
                $this->rulesCount++;
                continue;
            }

 
            if ((strpos($rule, ':/') === 1 || strpos($rule, ':\\') === 1) && !$this->isGlobPattern($rule)) {
                $this->absolutePathRules[] = $rule;
                $this->rulesCount++;
                continue;
            }

 
            if (WPStaging::make('WPSTG_ALLOW_VFS') === true && $this->strUtils->startsWith($rule, 'vfs:/') && !$this->isGlobPattern($rule)) {
                $this->absolutePathRules[] = $rule;
                $this->rulesCount++;
                continue;
            }

 
 
            if ($this->strUtils->startsWith($rule, '*.')) {
                $rule = '/**/' . $rule;
            }

 
 
            if ($this->strUtils->startsWith($rule, '**/')) {
                $rule = '/' . $rule;
            }

 
            if (!$this->isGlobPattern($rule)) {
                $rule = '/**/' . $rule;
            }

 
            if ($this->isChildrenMatchingAllow($rule)) {
                $this->globRules[] = $this->wildcardGlobToRegex(Glob::toRegex($rule . '/**'));
                $this->rulesCount++;
            }

 
            $this->globRules[] = $this->wildcardGlobToRegex(Glob::toRegex($rule));
            $this->rulesCount++;
        }
    }







    public function isMatched($fileInfo)
    {
        $path = $fileInfo->getPathname();
        $path = wp_normalize_path($path);

        if ($this->isAbsolutePathMatched($path)) {
            return true;
        }

        $relpath = str_replace($this->wpRootPath, '', $path);
 
        if ($this->isAbsolutePathMatched($relpath)) {
            return true;
        }

 
        if ($this->isGlobPatternMatched($relpath)) {
            return true;
        }

        return false;
    }






    public function hasRules()
    {
        return $this->rulesCount > 0;
    }







    protected function isAbsolutePathMatched($path)
    {
 
        if (in_array($path, $this->absolutePathRules)) {
            return true;
        }

 
        foreach ($this->absolutePathRules as $rule) {
            if ($this->strUtils->startsWith($path, $rule . '/')) {
                return true;
            }
        }

        return false;
    }







    protected function isGlobPatternMatched($path)
    {
        foreach ($this->globRules as $rule) {
            if (preg_match($rule, $path)) {
                return true;
            }
        }

        return false;
    }







    protected function isGlobPattern($pattern)
    {
        return false !== strpos($pattern, '*') || false !== strpos($pattern, '{') || false !== strpos($pattern, '?') || false !== strpos($pattern, '[');
    }







    protected function wildcardGlobToRegex($pattern)
    {
 
        if (strpos($pattern, '/(?=[^\.])[^/]*[^/]*/(?=[^\.])') !== false) {
            $pattern = str_replace('/(?=[^\.])[^/]*[^/]*/(?=[^\.])', '/([^/]+/)*', $pattern);
        }

 
        if (strpos($pattern, '/(?=[^\.])[^/]*[^/]*') !== false) {
            $pattern = str_replace('/(?=[^\.])[^/]*[^/]*', '/(.*)', $pattern);
        }

        return $pattern;
    }







    protected function isChildrenMatchingAllow($rule)
    {
        $rule = rtrim($rule, '/');
        $segments = explode('/', $rule);
        $lastSegment = $segments[count($segments) - 1];
 
        if ($lastSegment === '**') {
            return false;
        }

        return (strpos($lastSegment, '.') === false);
    }
}
