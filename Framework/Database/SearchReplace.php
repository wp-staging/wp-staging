<?php

namespace WPStaging\Framework\Database;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Traits\DebugLogTrait;
use WPStaging\Framework\Traits\SerializeTrait;
use WPStaging\Framework\Traits\UrlTrait;





class SearchReplace
{
    use DebugLogTrait;
    use SerializeTrait;
    use UrlTrait;









    const FILTER_REPLACE_EXTENDED_DATA = 'wpstg.database.searchreplace.replace_extended_data';

 
    private $search;

 
    private $replace;

 
    private $exclude;

 
    private $caseSensitive;

 
    private $currentSearch;

 
    private $currentReplace;

 
    private $isWpBakeryActive;

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

        return Hooks::applyFilters(self::FILTER_REPLACE_EXTENDED_DATA, $data, $this->search, $this->replace);
    }

    public function replaceWpBakeryValues($matched)
    {
        $data = $this->base64Decode($matched[1]);
        $data = $this->replace($data);
        return '[vc_raw_html]' . base64_encode($data) . '[/vc_raw_html]';
    }

    public function setSearch(array $search)
    {
        $this->search = $search;
        return $this;
    }

    public function setReplace(array $replace)
    {
        $this->replace = $replace;
        return $this;
    }











    public function appendSearchReplacePair(string $search, string $replace)
    {
        $this->search[] = $search;
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

 
        if (strpos($data, 'O:3:"PDO":0:') !== false) {
            return $data;
        }

 
 
 
        if (strpos($data, 'O:8:"DateTime":0:') !== false) {
            return $data;
        }

 
 
 
        if (strpos($data, 'O:') !== false && preg_match_all('@O:\d+:"([^"]+)"@', $data, $match) && !empty($match) && !empty($match[1])) {
            foreach ($match[1] as $value) {
                if ($value !== 'stdClass') {
                    return $data;
                }
            }

            unset($match);
        }

        $unserialized = false;
        try {
            $unserialized = @unserialize($data);
        } catch (\Throwable $e) {
            $this->debugLog('replaceString. Can not unserialize data. Error: ' . $e->getMessage() . ' Data: ' . $data);
        }

        if ($unserialized !== false) {
            return serialize($this->walker($unserialized));
        }

        return $data;
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

    private function strReplace($data = '')
    {
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
}
