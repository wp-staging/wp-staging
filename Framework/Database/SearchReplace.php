<?php

namespace WPStaging\Framework\Database;

use WPStaging\Framework\Traits\ApplyFiltersTrait;
use WPStaging\Framework\Traits\DebugLogTrait;
use WPStaging\Framework\Traits\SerializeTrait;
use WPStaging\Framework\Traits\UrlTrait;





class SearchReplace
{
    use ApplyFiltersTrait;
    use DebugLogTrait;
    use SerializeTrait;
    use UrlTrait;









    const FILTER_REPLACE_EXTENDED_DATA = 'wpstg.database.searchreplace.replace_extended_data';

 
    const SINGLE_PASS_PATTERN_MAX_LENGTH = 8192;

 
    private $search;

 
    private $replace;

 
    private $exclude;

 
    private $caseSensitive;

 
    private $currentSearch;

 
    private $currentReplace;

 
    private $isWpBakeryActive;

 
    private $singlePass = false;

 
    private $singlePassActive = false;

 
    private $replacementMap;

 
    private $replacementPattern;

    protected $smallerReplacement = PHP_INT_MAX;

    public function __construct(array $search = [], array $replace = [], $caseSensitive = true, array $exclude = [])
    {
        $this->search           = $search;
        $this->replace          = $replace;
        $this->caseSensitive    = $caseSensitive;
        $this->exclude          = $exclude;
        $this->isWpBakeryActive = false;
    }




    public function getSmallerSearchLength()
    {
        if ($this->smallerReplacement < PHP_INT_MAX) {
            return $this->smallerReplacement;
        }

        foreach ($this->search as $search) {
            if (strlen($search) < $this->smallerReplacement) {
                $this->smallerReplacement = strlen($search);
            }
        }

        return $this->smallerReplacement;
    }





    public function replace($data)
    {
        if (defined('DISABLE_WPSTG_SEARCH_REPLACE') && (bool)DISABLE_WPSTG_SEARCH_REPLACE) {
            return $data;
        }

        if (!$this->search || !$this->replace) {
            return $data;
        }

        $totalSearch  = count($this->search);
        $totalReplace = count($this->replace);
        if ($totalSearch !== $totalReplace) {
            throw new \RuntimeException(
                sprintf(
                    'Can not search and replace. There are %d items to search and %d items to replace',
                    $totalSearch,
                    $totalReplace
                )
            );
        }

        $this->singlePassActive = $this->canUseSinglePassReplacement();
        if ($this->singlePassActive) {
            $this->prepareSinglePassReplacement();
            return $this->walker($data);
        }

        for ($i = 0; $i < $totalSearch; $i++) {
            $this->currentSearch  = (string)$this->search[$i];
            $this->currentReplace = (string)$this->replace[$i];
            $data                 = $this->walker($data);
        }

        return $data;
    }








    public function replaceExtended($data)
    {
        if (defined('DISABLE_WPSTG_SEARCH_REPLACE') && (bool)DISABLE_WPSTG_SEARCH_REPLACE) {
            return $data;
        }

        if ($this->isWpBakeryActive) {
            $data = preg_replace_callback('/\[vc_raw_html\](.+?)\[\/vc_raw_html\]/S', [$this, 'replaceWpBakeryValues'], $data);
        }

        $data = $this->replace($data);

        if (!function_exists('has_filter') || has_filter(self::FILTER_REPLACE_EXTENDED_DATA) === false) {
            return $data;
        }

        return $this->applyFilters(self::FILTER_REPLACE_EXTENDED_DATA, $data, $this->search, $this->replace);
    }

    public function replaceWpBakeryValues($matched)
    {
        $data = $this->base64Decode($matched[1]);
        $data = $this->replace($data);
        return '[vc_raw_html]' . base64_encode($data) . '[/vc_raw_html]';
    }





    public function setSinglePass(bool $enabled)
    {
        $this->singlePass = $enabled;
        return $this;
    }

    public function setSearch(array $search)
    {
        $this->search = $search;
        $this->replacementMap = null;
        return $this;
    }

    public function setReplace(array $replace)
    {
        $this->replace = $replace;
        $this->replacementMap = null;
        return $this;
    }











    public function appendSearchReplacePair(string $search, string $replace)
    {
        $this->search[] = $search;
        $this->replacementMap = null;
        $this->replace[] = $replace;
        $this->smallerReplacement = PHP_INT_MAX;
        return $this;
    }

    public function setCaseSensitive($caseSensitive)
    {
        $this->caseSensitive = $caseSensitive;
        return $this;
    }

    public function setExclude(array $exclude)
    {
        $this->exclude = $exclude;
        return $this;
    }






    public function setWpBakeryActive($isActive = true)
    {
        $this->isWpBakeryActive = $isActive;
        return $this;
    }





    private function walker($data)
    {
        switch (gettype($data)) {
            case "string":
                return $this->replaceString($data);
            case "array":
                return $this->replaceArray($data);
            case "object":
                return $this->replaceObject($data);
        }

        return $data;
    }





    private function replaceString($data)
    {
        if (!$this->isSerialized($data)) {
            return $this->strReplace($data);
        }

        $rejected     = false;
        $unserialized = $this->safeMaybeUnserialize($data, [\stdClass::class], $rejected);

        if ($rejected || $unserialized === false) {
            return $data;
        }

        return serialize($this->walker($unserialized));
    }

    private function replaceArray(array $data)
    {
        foreach ($data as $key => $value) {
            $data[$key] = $this->walker($value);
        }

        return $data;
    }

    private function replaceObject($data)
    {
 
 
 
        $props = get_object_vars($data);
        if (!empty($props['__PHP_Incomplete_Class_Name'])) {
            return $data;
        }

        foreach ($props as $key => $value) {
            if ($key === '' || (isset($key[0]) && ord($key[0]) === 0)) {
                continue;
            }

            $data->{$key} = $this->walker($value);
        }

        return $data;
    }

 
    private function replaceSinglePass(string $data): string
    {
        if ($this->replacementPattern === '') {
            return strtr($data, $this->replacementMap);
        }

        $result = preg_replace_callback($this->replacementPattern, function ($match) {
            return (string)$this->replacementMap[$match[0]];
        }, $data);

        if ($result === null) {
            throw new \RuntimeException('Could not search and replace database value. PCRE error: ' . preg_last_error());
        }

        return $result;
    }

 
    private function prepareSinglePassReplacement()
    {
        if ($this->replacementMap !== null) {
            return;
        }

        $replacementMap = array_combine($this->search, $this->replace);
        if (array_key_exists('', $replacementMap)) {
            throw new \InvalidArgumentException('Single-pass search strings must not be empty.');
        }

        uksort($replacementMap, function ($first, $second) {
            return strlen($second) <=> strlen($first);
        });

        $this->replacementMap = $replacementMap;
        $pattern = '#' . implode('|', array_map(function ($search) {
            return preg_quote((string)$search, '#');
        }, array_keys($this->replacementMap))) . '#';
        $this->replacementPattern = strlen($pattern) <= self::SINGLE_PASS_PATTERN_MAX_LENGTH ? $pattern : '';
    }

    private function strReplace($data = '')
    {
        if ($this->singlePassActive) {
            return $this->replaceSinglePass($data);
        }

        $regexExclude = '';
        foreach ($this->exclude as $excludeString) {
 
            $regexExclude .= $excludeString . '(*SKIP)(*FAIL)|';
        }

        $pattern = '#' . $regexExclude . preg_quote($this->currentSearch, '#') . '#';
        if (!$this->caseSensitive) {
            $pattern .= 'i';
        }

        return preg_replace($pattern, $this->currentReplace, $data);
    }

 
    private function canUseSinglePassReplacement(): bool
    {
        return $this->singlePass && $this->caseSensitive && !$this->exclude;
    }
}
